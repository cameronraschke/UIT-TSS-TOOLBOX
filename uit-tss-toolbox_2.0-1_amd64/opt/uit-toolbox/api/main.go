// Don't forget - $ go mod init api; go mod tidy; 
package main

import (
  "context"
  "fmt"
  "os"
  "io"
  "math"
  "net"
  "net/http"
  // "net/http/httputil"
  "time"
  "encoding/json"
  // "unicode/utf8"
  "strings"
	// "crypto/sha256"
  "crypto/rand"
  "crypto/tls"
  "net/url"
  "errors"
  "database/sql"
  "sync"
  "sync/atomic"
  "runtime/debug"
  "slices"
  "strconv"
  // "log"
  // "log/slog"
  "api/database"
  "api/logger"
  "api/post"

  _ "net/http/pprof"
  _ "github.com/jackc/pgx/v5/stdlib"
)


// Structs for JSON responses
type LiveImage struct {
  TimeFormatted   *string    `json:"time_formatted"`
  Screenshot      *string    `json:"screenshot"`
}

// Mux handlers
type muxChain []func(http.Handler) http.Handler
func (chain muxChain) thenFunc(handle http.HandlerFunc) http.Handler {
    return chain.then(handle)
}

func (chain muxChain) then(handle http.Handler) http.Handler {
    for _, fn := range slices.Backward(chain) {
        handle = fn(handle)
    }
    return handle
}


type HttpHeaders struct {
  Authorization AuthHeader
  ContentType   *string
  Accept        *string
  Origin        *string
  UserAgent     *string
  Referer       *string
  Host          *string
}

type AuthHeader struct {
  Basic *string
  Bearer *string
}

type Locations struct {
  Time            *time.Time  `json:"time"`
  Tagnumber       *int32      `json:"tagnumber"`
  SystemSerial    *string     `json:"system_serial"`
  Location        *string     `json:"location"`
  Status          *bool       `json:"status"`
  DiskRemoved     *bool       `json:"disk_removed"`
  Department      *string     `json:"department"`
  Domain          *string     `json:"domain"`
  Note            *string     `json:"note"`
}

type JobQueue struct {
  PresentBool     *bool   `json:"present_bool"`
  KernelUpdated   *bool   `json:"kernel_updated"`
  BiosUpdated     *bool   `json:"bios_updated"`
  RemoteStatus    *string   `json:"remote_status"`
  RemoteTimeFormatted *string `json:"remote_time_formatted"`
}

type TagLookup struct {
  Tagnumber *string `json:"tagnumber"`
}

type TestQuery struct {
  Message   *string   `json:"message"`
}

type BasicToken struct {
	Token       string    `json:"token"`
  Expiry      time.Time `json:"expiry"`
  NotBefore   time.Time `json:"not_before"`
  TTL         float64   `json:"ttl"`
  IP          string    `json:"ip"`
  Valid       bool      `json:"valid"`
}

type BearerToken struct {
	Token       string    `json:"token"`
	Expiry      time.Time `json:"expiry"`
	NotBefore   time.Time `json:"not_before"`
	TTL         float64   `json:"ttl"`
	IP          string    `json:"ip"`
	Valid       bool      `json:"valid"`
}

type AuthSession struct {
    Basic  BasicToken
    Bearer BearerToken
}

type returnedJsonToken struct {
  Token string  `json:"token"`
  TTL   float64 `json:"ttl"`
  Valid bool    `json:"valid"`
}

type httpErrorCodes struct {
  Message string `json:"message"`
}

type RateLimiter struct {
  Requests         int
  LastSeen         time.Time
  MapLastUpdated   time.Time
  BannedUntil      time.Time
  Banned           bool
}


var (
	dbCTX context.Context
	webCTX context.Context
  eventType string
  db *sql.DB
  authMap sync.Map
  authMapEntryCount int64
  ipMap sync.Map
  log = logger.LoggerFactory("console")
)



func ParseHeaders(header http.Header) HttpHeaders {
  var headers HttpHeaders
  var authHeader AuthHeader
  for _, value := range header.Values("Authorization") {
    value = strings.TrimSpace(value)
    if strings.HasPrefix(value, "Basic ") {
      basic := strings.TrimSpace(strings.TrimPrefix(value, "Basic "))
      authHeader.Basic = &basic
    }
    if strings.HasPrefix(value, "Bearer ") {
      bearer := strings.TrimSpace(strings.TrimPrefix(value, "Bearer "))
      authHeader.Bearer = &bearer
    }
  }
  headers.Authorization = authHeader
  return headers
}


func rateLimitCheck(ipAddrChan <-chan string, bannedChan chan<- bool, rateLimitChan chan<- int) {
  // float64 has to be used in order to compare to the request rate which is calculated as a float64 value
  var requestLimit float64
  var bannedTimeout time.Duration

  // Limit how many requests per second and how long a client gets timed out for
  requestLimit = 200
  bannedTimeout = time.Second * 10

  requestIPAddr := <-ipAddrChan

  ipMap.Range(func(k, v interface{}) bool {
    key := k.(string)
    value := v.(RateLimiter)

    var banned bool
    var numOfRequests int
    var timeDiff float64
    var rate float64
    var requestRate float64

    if key == requestIPAddr {
      timeDiff = math.Abs(value.LastSeen.Sub(time.Now()).Seconds())
      numOfRequests = value.Requests + 1
      rate = float64(numOfRequests) / timeDiff
      requestRate = rate * (1 / timeDiff)

      if value.Banned == true && value.BannedUntil.Sub(time.Now()).Seconds() > 0 {
        banned = true
        if timeDiff > 1 {
          bannedChan <- banned
          rateLimitChan <- int(math.Round(requestRate))
        }
        close(bannedChan)
        close(rateLimitChan)
        return false
      }

      banned = false // Default value
      if timeDiff > 1 {
        if requestRate > requestLimit {
          banned = true
        } else {
          numOfRequests = 0
          banned = false
        }
        ipMap.Store(key, RateLimiter{Requests: numOfRequests, LastSeen: time.Now(), MapLastUpdated: time.Now(), BannedUntil: time.Now().Add(bannedTimeout), Banned: banned})
          bannedChan <- banned
          rateLimitChan <- int(math.Round(requestRate))
          close(bannedChan)
          close(rateLimitChan)
        return false
      } else if timeDiff < 1 {
        ipMap.Store(key, RateLimiter{Requests: numOfRequests, LastSeen: value.LastSeen, MapLastUpdated: value.MapLastUpdated, BannedUntil: time.Now().Add(bannedTimeout), Banned: value.Banned})
          bannedChan <- banned
          rateLimitChan <- int(math.Round(requestRate))
          close(bannedChan)
          close(rateLimitChan)
        return false
      } else {
        return true
      }
    }
    return true
  })
  return
}


func formatHttpError (errorString string) (jsonErrStr string) {
  var err error
  var jsonStr httpErrorCodes
  var jsonErr []byte

  jsonStr = httpErrorCodes{Message: errorString}
  jsonErr, err = json.Marshal(jsonStr)
  if err != nil {
    log.Error("Cannot parse JSON: " + err.Error())
    return
  }

  jsonErrStr = string(jsonErr)

  return string(jsonErrStr)
}


func remoteAPI(w http.ResponseWriter, req *http.Request) {
  var parsedURL *url.URL
  var err error

  if req.Method != http.MethodGet {
    log.Info("Method " + req.Method + " not allowed")
    http.Error(w, formatHttpError("Method not allowed"), http.StatusMethodNotAllowed)
    return
  }

  // Check database connection
  if db == nil {
    log.Error("Connection to database failed while attempting API Auth")
    http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
    return
  }

  // Parse URL
  parsedURL, err = url.Parse(req.URL.RequestURI())
  if err != nil {
    log.Warning("Cannot parse URL ( " + req.RemoteAddr + "): " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
    return
  }
  // path := parsedURL.Path
  RawQuery := parsedURL.RawQuery
  queries, _ := url.ParseQuery(RawQuery)

  tag := queries.Get("tagnumber")
  var tagnumber int
  if len(tag) > 0 {
    tagnumber, err = strconv.Atoi(tag)
    if err != nil {
      log.Warning("Tagnumber cannot be converted to integer: " + queries.Get("tagnumber"))
      return
    }
  }
  // if len(tagnumber) != 6 {
  //   log.Warning("Tagnumber not 6 digits long")
  //   http.Error(w, formatHttpError("Bad Request"), http.StatusBadRequest)
  //   return
  // }
  // systemSerial = queries.Get("system_serial")
  // sqlTime = queries.Get("time")
  queryType := queries.Get("type")

  switch queryType {
  case "all_tags":
    var allTagsJson string
    allTagsJson, err = database.GetAllTags(db)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, allTagsJson)
    return
  case "remote_present": 
    var remoteTableJson string
    remoteTableJson, err = database.GetRemoteOnlineTable(db)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, remoteTableJson)
    return
  case "live_image":
    var liveImageTableJson string
    liveImageTableJson, err = database.GetLiveImage(db, tagnumber)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, liveImageTableJson)
    return
  case "remote_present_header":
    var remoteTableHeaderJson string
    remoteTableHeaderJson, err = database.GetRemotePresentHeader(db)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, remoteTableHeaderJson)
    return
  case "remote_offline":
    var remoteOfflineTableJson string
    remoteOfflineTableJson, err = database.GetRemoteOfflineTable(db)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, remoteOfflineTableJson)
    return
  case "tagnumber_data": 
    var tagnumberDataJson string
    tagnumberDataJson, err = database.GetTagnumberData(db, tagnumber)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, tagnumberDataJson)
    return
  case "job_queue_by_tag":
    var remoteJobQueueByTagJson string
    remoteJobQueueByTagJson, err = database.GetJobQueueByTagnumber(db, tagnumber)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, remoteJobQueueByTagJson)
    return
  case "available_jobs":
    var availableJobsJson string
    availableJobsJson, err = database.GetAvailableJobs(db, tagnumber)
    if err != nil {
      log.Warning("Query error: " + err.Error());
      return
    }
    io.WriteString(w, availableJobsJson)
    return
  default:
    log.Warning("No query type defined")
    return
  }
}


func postAPI(w http.ResponseWriter, req *http.Request) {
  var parsedURL *url.URL
  var err error

  if req.Method != http.MethodPost {
    log.Info("Method " + req.Method + " not allowed")
    http.Error(w, formatHttpError("Method not allowed"), http.StatusMethodNotAllowed)
    return
  }

  // Check database connection
  if db == nil {
    log.Error("Connection to database failed while attempting API Auth")
    http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
    return
  }

  // Parse URL
  parsedURL, err = url.Parse(req.URL.RequestURI())
  if err != nil {
    log.Warning("Cannot parse URL ( " + req.RemoteAddr + "): " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
    return
  }
  // path := parsedURL.Path
  RawQuery := parsedURL.RawQuery
  queries, _ := url.ParseQuery(RawQuery)

  // tag := queries.Get("tagnumber")
  // var tagnumber int
  // if len(tag) > 0 {
  //   tagnumber, err = strconv.Atoi(tag)
  //   if err != nil {
  //     log.Warning("Tagnumber cannot be converted to integer: " + queries.Get("tagnumber"))
  //     return
  //   }
  // }

  queryType := queries.Get("type")

  switch queryType {
  case "job_queued":
    err = post.UpdateRemoteJobQueued(req, db, queryType)
    if err != nil {
      log.Error("Cannot update DB: " + err.Error())
      return
    }
    return
  case "client_image":
    err = post.UpdateClientImages(req, db, queryType)
    if err != nil {
      log.Error("Cannot update DB: " + err.Error())
      return
    }
    return
  default:
    log.Warning("No POST type defined: " + queryType)
    return
  }
}



func queryResults(sqlCode string, tagnumber string, systemSerial string) (jsonDataStr string, err error) {
  var results any
  // var sqlTime string
  var rows *sql.Rows
  var jsonData []byte

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()



  switch eventType {
    case "locations":
      rows, err = db.QueryContext(dbCTX, sqlCode)
      if err != nil {
        return "", errors.New("Error querying locations")
      }
      defer rows.Close()
      
      var locations []Locations // Initialize Locations slice
      locations = make([]Locations, 0) // Ensure Locations is initialized
      for rows.Next() {
        var result Locations
        if dbCTX.Err() != nil {
          log.Error("Context error: " + dbCTX.Err().Error())
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          log.Error("Context error: " + dbCTX.Err().Error())
          return "", errors.New("Error with rows: " + err.Error())
        }
        err = rows.Scan(
          &result.Time,
          &result.Tagnumber,
          &result.SystemSerial,
          &result.Location,
          &result.Status,
          &result.DiskRemoved,
          &result.Department,
          &result.Domain,
          &result.Note,
        )
        if err != nil {
          return "", errors.New("Error scanning row: " + err.Error())
        }
        locations = append(locations, result) // Append result to locations
      }

      if err = rows.Err(); err != nil {
        return "", errors.New("Error with rows: " + err.Error())
      }

      if err != nil {
        return "", errors.New("Error querying locations")
      }
      results = locations // Assign results to Locations

    case "tag_lookup":
      rows, err = db.QueryContext(dbCTX, sqlCode, systemSerial)
      if err != nil {
        return "", errors.New("Error querying tag lookup")
      }
      defer rows.Close()

      var tagLookup []TagLookup // Initialize tagLookup slice
      tagLookup = make([]TagLookup, 0) // Ensure tagLookup is initialized
      for rows.Next() {
        var result TagLookup
        if dbCTX.Err() != nil {
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          return "", errors.New("Error with rows: " + err.Error())  
        }
        err = rows.Scan(
          &result.Tagnumber,
        )
        if err != nil {
          return "", errors.New("Error scanning row: " + err.Error())
        }
        tagLookup = append(tagLookup, result)
      }

      results = tagLookup // Assign results to tagLookup

    case "job_queue":
      rows, err = db.QueryContext(dbCTX, sqlCode, tagnumber)
      if err != nil {
        return "", errors.New("Error querying tag lookup")
      }
      defer rows.Close()

      var jobQueue []JobQueue
      jobQueue = make([]JobQueue, 0)
      for rows.Next() {
        var result JobQueue
        if dbCTX.Err() != nil {
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          return "", errors.New("Error with rows: " + err.Error())  
        }
        err = rows.Scan(
          &result.PresentBool,
          &result.KernelUpdated,
          &result.BiosUpdated,
          &result.RemoteStatus,
          &result.RemoteTimeFormatted,
        )
        if err != nil {
          return "", errors.New("Error scanning row: " + err.Error())
        }
        jobQueue = append(jobQueue, result)
      }

      results = jobQueue

    case "test":
      rows, err = db.QueryContext(dbCTX, sqlCode)
      if err != nil {
        return "", errors.New("Error querying test query")
      }
      defer rows.Close()

      var testQuery []TestQuery
      testQuery = make([]TestQuery, 0)
      for rows.Next() {
        var result TestQuery
        if dbCTX.Err() != nil {
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          return "", errors.New("Error with rows: " + err.Error())  
        }
        err = rows.Scan(
          &result.Message,
        )
        if err != nil {
          return "", errors.New("Error scanning row: " + err.Error())
        }
        testQuery = append(testQuery, result)
      }

      results = testQuery

    case "err":
      return "", errors.New("Query type is not valid (error query type)")
    default:
      return "", errors.New("Unknown query type: " + eventType)
  }



  jsonData, err = json.Marshal(results)
  if err != nil {
    return "", errors.New("Error creating JSON data: " + err.Error())
  }  
  if len(jsonData) < 1 {
    return "", errors.New("No results found for query: " + sqlCode)
  }


  // Convert jsonData to string
  if len(jsonData) > 0 {
    jsonDataStr = string(jsonData)
  } else {
    return "", errors.New("No results found for query: " + sqlCode)
  }


  return jsonDataStr, nil
}


func apiMiddleWare (next http.Handler) http.Handler {
  return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
    var parsedURL *url.URL
    var queries url.Values
    var bearerToken string
    var basicToken string
    var token string
    var err error

    // Check CORS policy
    cors := http.NewCrossOriginProtection()
    cors.AddTrustedOrigin("https://WAN_IP_ADDRESS:1411")
    if err = cors.Check(req); err != nil {
      log.Warning("Request to " + req.URL.RequestURI() + " blocked from " + req.RemoteAddr)
      return
    }

    // Check rate limiting map
    ipAddrChan := make(chan string)
    bannedChan := make(chan bool)
    rateLimitChan := make(chan int)

    ip, _, err := net.SplitHostPort(req.RemoteAddr)
    _, _ = ipMap.LoadOrStore(ip, RateLimiter{Requests: 1, LastSeen: time.Now(), MapLastUpdated: time.Now(), Banned: false})

    //log.Info("Received request (" + req.RemoteAddr + "): " + req.Method + " " + req.URL.RequestURI())

    go func() {
      rateLimitCheck(ipAddrChan, bannedChan, rateLimitChan)
    }()

    ipAddrChan <- ip
    close(ipAddrChan)

    select {
    case bannedBool := <-bannedChan:
      if bannedBool == true {
        reqsPerSec := <-rateLimitChan
        log.Warning("Banned (" + ip + ")" + ", too many requests (" + fmt.Sprintf("%d", reqsPerSec) + "/s)")
        // http.Error(w, formatHttpError("Too many requests"), http.StatusTooManyRequests)
        return
      }
    case <-time.After(5 * time.Second):
      log.Error("Ban check timed out")
    }

    // Check if TLS connection is valid
    if req.TLS.HandshakeComplete == false && req.TLS.DidResume == false {
      log.Warning("TLS handshake failed for client " + req.RemoteAddr)
      return
    }

    // Check if request content length exceeds 64 MB
    if req.ContentLength > 64 << 20 {
      log.Warning("Request content length exceeds limit: " + fmt.Sprintf("%.2f%s", float64(float64(req.ContentLength) / 1000000), "MB"))
      return
    }


    // Check if request method is valid
    if req.Method != http.MethodOptions && req.Method != http.MethodGet && req.Method != http.MethodPost && req.Method != http.MethodPut && req.Method != http.MethodDelete {
      log.Warning("Invalid request method (" + req.RemoteAddr + "): " + req.Method)
      return
    }


    // Check if MIME type and Content-Type is valid
    // mimeType := http.DetectContentType(req.Body)
    // if mimeType == "application/octet-stream" || (mimeType != "application/x-www-form-urlencoded" && mimeType != "application/json") {
    //  log.Sprintf("%s %s\n", "Invalid Mime Type: ", mimeType)
    //  http.Error(w, formatHttpError("Invalid MIME type"), http.StatusUnsupportedMediaType)
    //  return
    // }
    if req.Method != http.MethodOptions && req.Method != http.MethodPost && req.Method != http.MethodPut {
      if req.Header.Get("Content-Type") != "application/x-www-form-urlencoded" && req.Header.Get("Content-Type") != "application/json" {
        log.Warning("Invalid Content-Type header: " + req.Header.Get("Content-Type") + " (" + req.RemoteAddr + ": " + req.Method + " " + req.URL.RequestURI() + ")")
        http.Error(w, formatHttpError("Invalid content type"), http.StatusUnsupportedMediaType)
        return
      }
    }


    // Parse URL to get path and queries
    parsedURL, err = url.Parse(req.URL.RequestURI())
    if err != nil {
      log.Warning("Cannot parse URL ( " + req.RemoteAddr + "): " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
      return
    }
    RawQuery := parsedURL.RawQuery
    queries, _ = url.ParseQuery(RawQuery)


    // Check if headers exist
    headerCount := 0
    for key, _ := range req.Header {
      if len(strings.TrimSpace(key)) > 0 {
        headerCount++
      }
    }
    if headerCount == 0 {
      log.Warning("Empty header request from: " + req.RemoteAddr)
    }


    // Headers for OPTIONS request
    if req.Method == http.MethodOptions {
      w.Header().Set("Access-Control-Allow-Origin", "https://WAN_IP_ADDRESS:1411")
      w.Header().Set("Access-Control-Allow-Credentials", "true")
      w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
      w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, Set-Cookie, credentials")
      w.WriteHeader(http.StatusNoContent)
      return
    }
    
    // Headers for all other requests
    if queries.Get("sse") == "true" {
      w.Header().Set("Content-Type", "text/event-stream")
    } else {
      w.Header().Set("Content-Type", "application/json; charset=utf-8")
    }

    w.Header().Set("Access-Control-Allow-Origin", "https://WAN_IP_ADDRESS:1411")
    w.Header().Set("Access-Control-Allow-Credentials", "true")
    w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
    w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, Set-Cookie, credentials")
    w.Header().Set("Content-Security-Policy", "img-src 'none'")
    w.Header().Set("Content-Security-Policy", "img-src https://WAN_IP_ADDRESS:31411/api/remote/")
    w.Header().Set("X-Content-Type-Options", "nosniff")
    w.Header().Set("Cache-Control", "no-cache, no-store, must-revalidate")
    w.Header().Set("Pragma", "no-cache")
    w.Header().Set("Expires", "0")
    w.Header().Set("Host", "WAN_IP_ADDRESS:31411")
    w.Header().Set("X-Frame-Options", "DENY")
    w.Header().Set("Content-Security-Policy", "frame-ancestors 'self'")
    w.Header().Set("Strict-Transport-Security", "max-age=86400; includeSubDomains")
    w.Header().Set("X-Accel-Buffering", "no")
    w.WriteHeader(http.StatusOK)


    // Check if Authorization header exists
    authHeaderMap := req.Header.Values("authorization")
    if len(authHeaderMap) >= 1 {
      for _, value := range authHeaderMap {
        if strings.HasPrefix(value, "Bearer ") {
          bearerToken = strings.TrimPrefix(value, "Bearer ")
        } else if strings.HasPrefix(value, "Basic ") {
          basicToken = strings.TrimPrefix(value, "Basic ")
        } else {
          log.Info("Missing/Malformed Authorization header")
          // http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
          // return
        }

        if bearerToken != "" {
          token = bearerToken
        } else if basicToken != "" {
          token = basicToken
        }

        // Check if the token is empty
        if len(strings.TrimSpace(token)) == 0 {
          log.Warning("Empty value for Authorization header")
          http.Error(w, formatHttpError("Empty Authorization header"), http.StatusUnauthorized)
          return
        }
      }
    } else {
      log.Info("Authorization header missing: " + req.URL.RequestURI())
    }

    // Don't call next.ServeHTTP(w, req) because this is first function in muxChain
    next.ServeHTTP(w, req)
  })
}

func getNewBearerToken(w http.ResponseWriter, req *http.Request) {
  var basicToken string
  requestIP, _, _ := net.SplitHostPort(req.RemoteAddr)

  headers := ParseHeaders(req.Header)
  if headers.Authorization.Basic != nil {
    basicToken = *headers.Authorization.Basic
  }

  if strings.TrimSpace(basicToken) == "" {
    log.Warning("Empty value for Basic Authorization header")
    http.Error(w, formatHttpError("Empty Basic Authorization header"), http.StatusUnauthorized)
    return
  }

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second)
  defer cancel()

  // Check if DB connection is valid
  if db == nil {
    log.Error("Connection to database failed while attempting API Auth")
    http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
    return
  }

  // Check if the Basic token exists in the database
  sqlCode := `SELECT ENCODE(SHA256(CONCAT(username, ':', password)::bytea), 'hex') as token FROM logins WHERE ENCODE(SHA256(CONCAT(username, ':', password)::bytea), 'hex') = $1`
  rows, err := db.QueryContext(dbCTX, sqlCode, basicToken)
  if err != nil {
    log.Error("Cannot query database for API Auth: " + err.Error())
    http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
    return
  }
  defer rows.Close()

  for rows.Next() {
    var dbToken string

    if err := rows.Scan(&dbToken); err != nil {
      log.Error("Error scanning token: " + err.Error())
      http.Error(w, "Internal server error", http.StatusInternalServerError)
      return
    }
    if dbCTX.Err() != nil {
      log.Error("Context error: " + dbCTX.Err().Error())
      http.Error(w, "Internal server error", http.StatusInternalServerError)
      return
    }
    if strings.TrimSpace(dbToken) == "" {
      log.Info("DB token has 0 length")
      http.Error(w, formatHttpError("Internal server error"), http.StatusUnauthorized)
      return
    }

    if dbToken != basicToken {
      log.Info("Incorrect credentials provided for token refresh: " + req.RemoteAddr)
      http.Error(w, formatHttpError("Forbidden"), http.StatusUnauthorized)
      return
    }

    // hash := sha256.New()
    // hash.Write([]byte(dbToken))
    // bearerToken := fmt.Sprintf("%x", hash.Sum(nil))
    hash := make([]byte, 32)
    _, err := rand.Read(hash)
    if err != nil {
      log.Error("Cannot generate token: " + err.Error())
      http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
      return
    }
    bearerToken := fmt.Sprintf("%x", hash)
    if strings.TrimSpace(bearerToken) == "" {
      log.Error("Failed to generate bearer token")
      http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
      return
    }

    sessionID := fmt.Sprintf("%s:%s", requestIP, bearerToken)

    // Set expiry time
    basicTTL := 60 * time.Minute
    bearerTTL := 60 * time.Second
    basicExpiry := time.Now().Add(basicTTL)
    bearerExpiry := time.Now().Add(bearerTTL)

    basic := BasicToken{Token: basicToken, Expiry: basicExpiry, NotBefore: time.Now(), TTL: basicExpiry.Sub(time.Now()).Seconds(), IP: requestIP, Valid: true}
    bearer := BearerToken{Token: bearerToken, Expiry: bearerExpiry, NotBefore: time.Now(), TTL: bearerExpiry.Sub(time.Now()).Seconds(), IP: requestIP, Valid: true}
    _, exists := authMap.Load(sessionID)
    authMap.Store(sessionID, AuthSession{Basic: basic, Bearer: bearer})

    value, ok := authMap.Load(sessionID)
    if !ok {
      log.Error("Cannot load auth session from authMap")
      http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
      return
    }
    authSession := value.(AuthSession)

    if authSession.Bearer.Token != bearerToken || authSession.Bearer.TTL <= 0 || 
        authSession.Bearer.Valid == false || authSession.Bearer.IP != requestIP || 
        time.Now().After(authSession.Bearer.Expiry) || time.Now().Before(authSession.Bearer.NotBefore) {
      log.Error("Error while creating new bearer token: " + requestIP)
      authMap.Delete(sessionID)
      atomic.AddInt64(&authMapEntryCount, -1)
      http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
      return
    }

    authMapEntryCount := atomic.LoadInt64(&authMapEntryCount)
    if authMapEntryCount < 0 {
      authMapEntryCount = 0
    }

    if exists {
      log.Info("Auth session exists: " + requestIP + " (Sessions: " + strconv.Itoa(int(authMapEntryCount)) + " TTL: " + fmt.Sprintf("%.2f", authSession.Bearer.TTL) + "s)")
    } else {
      atomic.AddInt64(&authMapEntryCount, 1)
      authMapEntryCount := atomic.LoadInt64(&authMapEntryCount)
      if authMapEntryCount < 0 {
        authMapEntryCount = 0
      }
      log.Info("New auth session created: " + requestIP + " (Sessions: " + strconv.Itoa(int(authMapEntryCount)) + " TTL: " + fmt.Sprintf("%.2f", authSession.Bearer.TTL) + "s)")
    }

    returnedJsonStruct := returnedJsonToken{
      Token: authSession.Bearer.Token,
      TTL:   authSession.Bearer.TTL,
      Valid: authSession.Bearer.Valid,
    }

    jsonData, err := json.Marshal(returnedJsonStruct)
    if err != nil {
      log.Error("Cannot marshal Token to JSON: " + err.Error())
      http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
      return
    }

    io.WriteString(w, string(jsonData))
    return
  }
}


func checkAuthSession(authMap *sync.Map, requestIP string, requestBasicToken string, requestBearerToken string) (basicValid bool, bearerValid bool, basicTTL float64, bearerTTL float64, matchedSession *AuthSession) {
  basicValid = false
  bearerValid = false
  basicTTL = 0.0
  bearerTTL = 0.0
  matchedSession = nil

  authMap.Range(func(k, v interface{}) bool {
    sessionID := k.(string)
    authSession := v.(AuthSession)
    sessionIP := strings.SplitN(sessionID, ":", 2)[0]

    basicExists := strings.TrimSpace(authSession.Basic.Token) != ""
    bearerExists := strings.TrimSpace(authSession.Bearer.Token) != ""

    if strings.TrimSpace(sessionID) == "" || strings.TrimSpace(sessionIP) == "" {
      authMap.Delete(sessionID)
      atomic.AddInt64(&authMapEntryCount, -1)
      return true
    }
    if (requestIP != sessionIP) {
      authMap.Delete(sessionID)
      atomic.AddInt64(&authMapEntryCount, -1)
      return true
    }
    if !basicExists && !bearerExists {
      authMap.Delete(sessionID)
      atomic.AddInt64(&authMapEntryCount, -1)
      return true
    }

    if basicExists && strings.TrimSpace(requestBasicToken) == strings.TrimSpace(authSession.Basic.Token) && sessionIP != authSession.Basic.IP {
      if time.Now().Before(authSession.Basic.Expiry) && authSession.Basic.Valid && authSession.Basic.IP == sessionIP {
        basicValid = true
        basicTTL = authSession.Basic.Expiry.Sub(time.Now()).Seconds()
        matchedSession = &authSession
      } else {
        log.Debug("Basic token found but expired/invalid for IP: " + sessionIP)
      }
    }

    if bearerExists && strings.TrimSpace(requestBearerToken) == strings.TrimSpace(authSession.Bearer.Token) && sessionIP != authSession.Bearer.IP {
      if time.Now().Before(authSession.Bearer.Expiry) && authSession.Bearer.Valid && authSession.Bearer.IP == sessionIP {
        bearerValid = true
        bearerTTL = authSession.Bearer.Expiry.Sub(time.Now()).Seconds()
        matchedSession = &authSession
      } else {
        log.Debug("Bearer token found but expired/invalid for IP: " + sessionIP)
      }
    }

    if basicValid || bearerValid {
      return false
    }
    return true
  })

  return
}

func apiAuth(next http.Handler) http.Handler {
  return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
    var requestBasicToken string
    var requestBearerToken string

    // Delete expired tokens & malformed entries out of authMap
    authMap.Range(func(k, v interface{}) bool {
      sessionID := k.(string)
      authSession := v.(AuthSession)
      sessionIP := strings.SplitN(sessionID, ":", 2)[0]

      // basicExpiry := authSession.Basic.Expiry.Sub(time.Now())
      bearerExpiry := authSession.Bearer.Expiry.Sub(time.Now())

      // Auth cache entry expires once countdown reaches zero
      if bearerExpiry.Seconds() <= 0 {
        authMap.Delete(sessionID)
        atomic.AddInt64(&authMapEntryCount, -1)
        authMapEntryCount := atomic.LoadInt64(&authMapEntryCount)
        if authMapEntryCount < 0 {
          authMapEntryCount = 0
        }
        log.Info("Auth session expired: " + sessionIP + " (TTL: " + fmt.Sprintf("%.2f", bearerExpiry.Seconds()) + ", " + strconv.Itoa(int(authMapEntryCount)) + " session(s))")
      }
      return true
    })

    requestIP, _, _ := net.SplitHostPort(req.RemoteAddr)
    queryType := strings.TrimSpace(req.URL.Query().Get("type"))
    if strings.TrimSpace(queryType) == "" {
      log.Warning("No query type defined for request: " + req.RemoteAddr)
      http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
      return
    }

    headers := ParseHeaders(req.Header)
    if headers.Authorization.Basic != nil {
      requestBasicToken = *headers.Authorization.Basic
    }
    if headers.Authorization.Bearer != nil {
      requestBearerToken = *headers.Authorization.Bearer
    }

    if strings.TrimSpace(requestBearerToken) == "" && strings.TrimSpace(requestBasicToken) == "" {
      log.Warning("Empty value for Authorization header")
      http.Error(w, formatHttpError("Unauthorized"), http.StatusUnauthorized)
      return
    }
    
    basicValid, bearerValid, _, bearerTTL, matchedSession := checkAuthSession(&authMap, requestIP, requestBasicToken, requestBearerToken)
    
    if (basicValid && bearerValid) || bearerValid {
      if queryType == "check-token" {
        jsonData, err := json.Marshal(returnedJsonToken{
          Token: matchedSession.Bearer.Token,
          TTL:   bearerTTL,
          Valid: true,
        })
        if err != nil {
          log.Error("Cannot marshal Token to JSON: " + err.Error())
          return
        }
        io.WriteString(w, string(jsonData))
        return
      } else if strings.TrimSpace(queryType) != "" {
        next.ServeHTTP(w, req)
      } else {
        log.Warning("No query type defined for bearer token: " + requestIP)
        http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
        return
      }
    } else if basicValid && !bearerValid {
      authMapEntryCount := atomic.LoadInt64(&authMapEntryCount)
      log.Debug("Auth cache miss: " + requestIP + " (Sessions: " + strconv.Itoa(int(authMapEntryCount)) + ")")
      if queryType == "new-token" && strings.TrimSpace(requestBasicToken) != "" {
        next.ServeHTTP(w, req)
      } else {
        http.Error(w, formatHttpError("Unauthorized"), http.StatusUnauthorized)
        return
      }
    } else {
      log.Warning("No valid authentication found for request: " + requestIP)
      http.Error(w, formatHttpError("Unauthorized"), http.StatusUnauthorized)
      return
    }
  })
}


// func GetInfoHandler(w http.ResponseWriter, r *http.Request) {
//     w.Header().Set("Content-Type", "application/json")
//     if err := json.NewEncoder(w).Encode(db.Stats()); err != nil {
//         http.Error(w, "Error encoding response", http.StatusInternalServerError)
//         return
//     }
// }


func startAuthMapCleanup(interval time.Duration) {
  go func() {
    for {
      time.Sleep(interval)
      authMap.Range(func(k, v interface{}) bool {
        sessionID := k.(string)
        authSession := v.(AuthSession)
        sessionIP := strings.SplitN(sessionID, ":", 2)[0]

        // basicExpiry := authSession.Basic.Expiry.Sub(time.Now())
        bearerExpiry := authSession.Bearer.Expiry.Sub(time.Now())

        // Auth cache entry expires once countdown reaches zero
        if bearerExpiry.Seconds() <= 0 {
          authMap.Delete(sessionID)
          atomic.AddInt64(&authMapEntryCount, -1)
          authMapEntryCount := atomic.LoadInt64(&authMapEntryCount)
          if authMapEntryCount < 0 {
            authMapEntryCount = 0
          }
          log.Info("(Cleanup) Auth session expired: " + sessionIP + " (TTL: " + fmt.Sprintf("%.2f", bearerExpiry.Seconds()) + ", " + strconv.Itoa(int(authMapEntryCount)) + " session(s))")
        }
        return true
      })
    }
  }()
}


func main() {
  var err error

  debug.PrintStack()
  log.Info("Server time: " + time.Now().Format("01-02-2006 15:04:05"))
  log.Info("UIT API Starting...")

  // Recover from panics
  defer func() {
    if pan := recover(); pan != nil {
        log.Error("Recovered. Error: \n" + fmt.Sprintf("%v", pan))
        log.Error("Trace: \n" + string(debug.Stack()))
    }
  }()

  startAuthMapCleanup(15 * time.Second)

  // go func() {
	//   err := http.ListenAndServe("localhost:6060", nil)
  //   if err != nil {
  //     log.Error("Profiler error: " + err.Error())
  //   }
  // }()

  // Connect to db with pgx
  log.Info("Attempting connection to database...")
  const dbConnString = "postgres://uitweb:WEB_SVC_PASSWD@127.0.0.1:5432/uitdb?sslmode=disable"
  db, err = sql.Open("pgx", dbConnString)
  if err != nil  {
    log.Error("Unable to connect to database: \n" + err.Error())
    os.Exit(1)
  }
  defer db.Close()

  // Check if the database connection is valid
  if err = db.Ping(); err != nil {
    log.Error("Cannot ping database: \n" + err.Error())
    os.Exit(1)
  }
  log.Info("Connected to database successfully")

  // Set defaults for db connection
  db.SetMaxOpenConns(30)
  db.SetMaxIdleConns(10)
  db.SetConnMaxIdleTime(1 * time.Minute)


  webCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()


  // Check if the web context is valid
  if webCTX.Err() != nil {
    log.Error("Web context error: " + webCTX.Err().Error())
    panic("Web context error")
  }

  if dbCTX.Err() != nil {
    log.Error("DB context error: " + dbCTX.Err().Error())
    panic("DB context error")
  }


  // Route to correct function
  baseMuxChain := muxChain{apiMiddleWare, apiAuth}
  // refreshTokenMuxChain := muxChain{apiMiddleWare}
  mux := http.NewServeMux()
  mux.Handle("/api/auth", baseMuxChain.thenFunc(getNewBearerToken))
  mux.Handle("/api/remote", baseMuxChain.thenFunc(remoteAPI))
  mux.Handle("/api/post", baseMuxChain.thenFunc(postAPI))
  mux.Handle("/api/locations", baseMuxChain.thenFunc(remoteAPI))
  // mux.HandleFunc("/dbstats/", GetInfoHandler)

	log.Info("Starting web server")

  tlsConfig := &tls.Config{
    MinVersion: tls.VersionTLS12, //0x0303
  }

  httpServer := http.Server{
		Addr: ":31411",
    Handler: mux,
    TLSConfig: tlsConfig,
    ReadTimeout: 10 * time.Second,
    WriteTimeout: 10 * time.Second,
    IdleTimeout: 120 * time.Second,
    MaxHeaderBytes: 1 << 20, // 1MB header size max
	}

  httpServer.Protocols = new(http.Protocols)
  httpServer.Protocols.SetHTTP1(false)
  httpServer.Protocols.SetHTTP2(true)

  log.Info("Web server ready and listening for requests on https://*:31411")

	log.Error(httpServer.ListenAndServeTLS("/usr/local/share/ca-certificates/uit-web.crt", "/usr/local/share/ca-certificates/uit-web.key").Error())
  if err != nil {
    log.Error("Cannot start web server: " + err.Error())
    os.Exit(1)
  }
  defer httpServer.Close()
}