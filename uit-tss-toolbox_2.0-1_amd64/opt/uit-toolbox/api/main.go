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
  "time"
  "encoding/json"
  // "unicode/utf8"
  "strings"
	// "crypto/sha256"
  "crypto/rand"
  "net/url"
  "errors"
  "database/sql"
  "sync"
  "runtime/debug"
  "slices"
  "strconv"

  "api/database"
  "api/logger"

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

type Auth struct {
	Token string `json:"token"`
}

type StoredAuth struct {
  Expires time.Time
  Hash    string
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
  ipMap sync.Map
  log = logger.LoggerFactory("console")
)


func rateLimitCheck(ipAddrChan <-chan string, bannedChan chan<- bool, rateLimitChan chan<- int) {
  var totalEntries int
  // var entryExists bool
  var banned bool
  var numOfRequests int
  var requestLimit float64

  // Limit how many requests per second
  requestLimit = 100

  requestIPAddr := <-ipAddrChan

  ipMap.Range(func(k, v interface{}) bool {
    key := k.(string)
    value := v.(RateLimiter)
    var timeDiff float64
    var rate float64
    var requestRate float64

    totalEntries++

    if key == requestIPAddr {
      timeDiff = math.Abs(value.MapLastUpdated.Sub(time.Now()).Seconds())
      numOfRequests = value.Requests + 1
      rate = float64(numOfRequests) / timeDiff
      requestRate = rate * (1 / timeDiff)

      if value.Banned == true && value.BannedUntil.Sub(time.Now()).Seconds() > 0 {
          banned = true
          bannedChan <- banned
          rateLimitChan <- int(math.Round(requestRate))
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
        ipMap.Store(key, RateLimiter{Requests: numOfRequests, LastSeen: time.Now(), MapLastUpdated: time.Now(), BannedUntil: time.Now().Add(time.Second * 10), Banned: banned})
          bannedChan <- banned
          rateLimitChan <- int(math.Round(requestRate))
          close(bannedChan)
          close(rateLimitChan)
        return false
      } else if timeDiff < 1 {
        ipMap.Store(key, RateLimiter{Requests: numOfRequests, LastSeen: value.LastSeen, MapLastUpdated: value.MapLastUpdated, BannedUntil: time.Now().Add(time.Second * 10), Banned: value.Banned})
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

func getRequestToSQL(requestURL string) (sql string, tagnumber string, systemSerial string, sqlTime string, err error) {
    var path string
    var parsedURL *url.URL
    var queries url.Values

    parsedURL, err = url.Parse(requestURL)
    if err != nil {
      log.Warning("Cannot parse URL: " + " " + err.Error() + " (" + requestURL + ")")
      return "", "", "", "", errors.New("Cannot parse URL: " + requestURL)
    }

    path = parsedURL.Path

    RawQuery := parsedURL.RawQuery
    queries, _ = url.ParseQuery(RawQuery)

    tagnumber = queries.Get("tagnumber")
    systemSerial = queries.Get("system_serial")
    sqlTime = queries.Get("time")

    // Query type determination
    if path == "/api/remote" && queries.Get("type") == "live_image" && len(queries.Get("tagnumber")) == 6 {
      sql = ``
      eventType = "live_image"
    } else if path == "/api/remote" && queries.Get("type") == "remote_present" {
      eventType = "remote_present"
    } else if path == "/api/remote" && queries.Get("type") == "remote_offline" {
      eventType = "remote_offline"
    } else if path == "/api/locations" && queries.Get("type") == "all_tags" {
      eventType = "all_tags"
    } else if path == "/api/remote" && queries.Get("type") == "remote_present_header" {
      eventType = "remote_present_header"
    } else if path == "/api/test" && queries.Get("type") == "test" {
      sql = `SELECT 'test'`
      eventType = "test"
    } else if path == "/api/remote" && queries.Get("type") == "tag_lookup" && len(queries.Get("system_serial")) >= 1 {
      sql = `SELECT tagnumber FROM locations WHERE system_serial = $1 ORDER BY time DESC LIMIT 1`
      eventType = "tag_lookup"
    } else if path == "/api/remote" && queries.Get("type") == "job_queue" && len(queries.Get("tagnumber")) == 6 {
      sql = `SELECT remote.present_bool, remote.kernel_updated, client_health.bios_updated, 
              remote.status AS remote_status, TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS remote_time_formatted 
              FROM remote 
              LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.tagnumber = $1`
      eventType = "job_queue"
    } else if len(queries.Get("type")) <= 0 {
      eventType = "err"
      return "", "", "", "", errors.New("Bad URL request (empty 'type' key in URL): " + requestURL)
    } else {
      eventType = "err"
      return "", "", "", "", errors.New("Bad URL request (unknown error): " + requestURL)
    }

    return sql, tagnumber, systemSerial, sqlTime, nil
}


func remoteAPI (w http.ResponseWriter, req *http.Request) {
  var parsedURL *url.URL
  var err error


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
      log.Error("Cannot query all tags: " + err.Error())
      return
    }
    io.WriteString(w, allTagsJson)
    return
  case "remote_present": 
    var remoteTableJson string
    remoteTableJson, err = database.GetRemoteOnlineTable(db)
    if err != nil {
      log.Error("Cannot query present clients: " + err.Error());
    }
    io.WriteString(w, remoteTableJson)
    return
  case "live_image":
    var liveImageTableJson string
    liveImageTableJson, err = database.GetLiveImage(db, tagnumber)
    if err != nil {
      log.Error("Cannot query present clients: " + err.Error());
    }
    io.WriteString(w, liveImageTableJson)
    return
  case "remote_present_header":
    var remoteTableHeaderJson string
    remoteTableHeaderJson, err = database.GetRemotePresentHeader(db)
    if err != nil {
      log.Error("Cannot query job queue table header")
      return
    }
    io.WriteString(w, remoteTableHeaderJson)
    return
  case "remote_offline":
    var remoteOfflineTableJson string
    remoteOfflineTableJson, err = database.GetRemoteOfflineTable(db)
    if err != nil {
      return
    }
    io.WriteString(w, remoteOfflineTableJson)
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

    ipAddrChan := make(chan string)
    bannedChan := make(chan bool)
    rateLimitChan := make(chan int)

    ip, _, err := net.SplitHostPort(req.RemoteAddr)
    _, _ = ipMap.LoadOrStore(ip, RateLimiter{Requests: 1, LastSeen: time.Now(), MapLastUpdated: time.Now(), Banned: false})

    log.Info("Received request (" + req.RemoteAddr + "): " + req.Method + " " + req.URL.RequestURI())

    go func() {
      rateLimitCheck(ipAddrChan, bannedChan, rateLimitChan)
    }()

    ipAddrChan <- ip
    close(ipAddrChan)

    select {
    case bannedBool := <-bannedChan:
      if bannedBool == true {
        reqsPerSec := <-rateLimitChan
        log.Warning("Banned [" + ip + "]" + ", too many requests (" + fmt.Sprintf("%d", reqsPerSec) + "/s)")
        // http.Error(w, formatHttpError("Too many requests"), http.StatusTooManyRequests)
        return
      }
    case <-time.After(5 * time.Second):
      fmt.Println("Ban check timed out :(")
    }

    // Check if TLS connection is valid
    // if req.HandshakeComplete == false {
    //   log.Warning("TLS handshake failed for client " + req.RemoteAddr)
    //   return
    // }

    // Check if request content length exceeds 64 MB
    if req.ContentLength > 64 << 20 {
      log.Warning("Request content length exceeds limit: " + fmt.Sprint(req.ContentLength))
      return
    }


    // Check if request method is valid
    if req.Method != http.MethodOptions && req.Method != http.MethodGet && req.Method != http.MethodPost && req.Method != http.MethodPut && req.Method != http.MethodPatch && req.Method != http.MethodDelete {
      log.Warning("Invalid request method (" + req.RemoteAddr + "): " + req.Method)
      return
    }


    // Check if Content-Type is valid
    // if req.Header.Get("Content-Type") != "application/x-www-form-urlencoded" && req.Header.Get("Content-Type") != "application/json" {
    //   log.Warning("Invalid Content-Type: " + req.Header.Get("Content-Type"))
    //   http.Error(w, formatHttpError("Invalid content type"), http.StatusUnsupportedMediaType)
    //   return
    // }


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

    
    // Set headers
    if queries.Get("sse") == "true" {
      w.Header().Set("Content-Type", "text/event-stream")
    } else {
      w.Header().Set("Content-Type", "application/json")
    }

    w.Header().Set("Access-Control-Allow-Origin", "https://WAN_IP_ADDRESS:1411")
    w.Header().Set("Access-Control-Allow-Credentials", "true")
    w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
    w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, Set-Cookie")
    w.Header().Set("Cache-Control", "no-cache, no-store, must-revalidate")
    w.Header().Set("Pragma", "no-cache")
    w.Header().Set("Expires", "0")
    w.Header().Set("Connection", "keep-alive")
    w.Header().Set("X-Accel-Buffering", "no")
    w.WriteHeader(http.StatusOK)


    // Check if Authorization header exists
    headerMap := req.Header.Values("Authorization")
    if len(headerMap) >= 1 {
      for _, value := range headerMap {
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

func refreshClientToken(w http.ResponseWriter, req *http.Request) {
  var token string
  var rows *sql.Rows
  var TTLDuration time.Duration
  var jsonData []byte
  var jsonDataStr string
  var basicToken string
  var bearerToken string
  var apiAuthDBRowCount int
  var err error

  // TTL for tokens
  TTLDuration = time.Second * 60

  // Get BASIC token from Authorization header
  headerMap := req.Header.Values("Authorization")
  for _, value := range headerMap {
    if strings.HasPrefix(value, "Bearer ") {
      bearerToken = strings.TrimPrefix(value, "Bearer ")
    } else if strings.HasPrefix(value, "Basic ") {
      basicToken = strings.TrimPrefix(value, "Basic ")
    } else {
      log.Warning("Malformed Authorization header")
      http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
      return
    }
  }

  if bearerToken != "" {
    bearerToken = bearerToken
  } else if basicToken != "" {
    token = basicToken
  } else {
    log.Warning("Malformed Basic Authorization header")
    http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
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
  rows, err = db.QueryContext(dbCTX, sqlCode, token)
  if err != nil {
    log.Error("Cannot query database for API Auth: " + err.Error())
    http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
    return
  }
  defer rows.Close()

  apiAuthDBRowCount = 0
  for rows.Next() {
    var dbToken string

    if err = rows.Scan(&dbToken); err != nil {
      log.Error("Error scanning token: " + err.Error())
      http.Error(w, "Internal server error", http.StatusInternalServerError)
      return
    }
    if dbCTX.Err() != nil {
      log.Error("Context error: " + dbCTX.Err().Error())
      http.Error(w, "Internal server error", http.StatusInternalServerError)
      return
    }
    if len(strings.TrimSpace(dbToken)) == 0 {
      log.Info("DB token has 0 length")
      http.Error(w, formatHttpError("Internal server error"), http.StatusUnauthorized)
      return
    }

    if dbToken == token {
      // hash := sha256.New()
      // hash.Write([]byte(dbToken))
      // hashedTokenStr := fmt.Sprintf("%x", hash.Sum(nil))
      hash := make([]byte, 32)
      _, err = rand.Read(hash)
      if err != nil {
        log.Error("Cannot generate token: " + err.Error())
        http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
        return
      }
      hashedTokenStr := fmt.Sprintf("%x", hash)

      authMap.Store(hashedTokenStr, time.Now().Add(TTLDuration))
      if hashedTokenStr != "" {
        apiAuthDBRowCount++
        // cookie := http.Cookie{
        //   Name:     "authCookie",
        //   Value:    hashedTokenStr,
        //   Path:     "/",
        //   MaxAge:   120,
        //   HttpOnly: false, //false = accessible to JS
        //   Secure:   true,
        //   SameSite: http.SameSiteLaxMode,
        // }
        // http.SetCookie(w, &cookie)
        jsonData, err = json.Marshal(Auth{Token: hashedTokenStr})
        if err != nil {
          log.Error("Cannot marshal Token to JSON: " + err.Error())
          return
        }

        jsonDataStr = string(jsonData)
        io.WriteString(w, jsonDataStr)
        return
      }
    } else {
        log.Info("DB returned no token for given auth")
        http.Error(w, formatHttpError("Incorrect credentials"), http.StatusForbidden)
        return
    }
  }
}


func apiAuth (next http.Handler) http.Handler {
  return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
    var token string
    var matches int
    var timeDiff time.Duration
    var bearerToken string
    var basicToken string
    var jsonData []byte
    var jsonDataStr string
    var err error

    // Delete expired tokens & malformed entries out of authMap
    authMap.Range(func(k, v interface{}) bool {
      value := v.(time.Time)
      key := k.(string)
      timeDiff = value.Sub(time.Now())

      // Auth cache entry expires once countdown reaches zero
      if timeDiff.Seconds() < 0 {
        authMap.Delete(key)
        log.Debug("Auth session expired: " + key + " (TTL: " + fmt.Sprintf("%.2f", timeDiff.Seconds()) + ")")
      }
      return true
    })

    
    // Get token from Authorization header
    headerMap := req.Header.Values("Authorization")
    for _, value := range headerMap {
      if strings.HasPrefix(value, "Bearer ") {
        bearerToken = strings.TrimPrefix(value, "Bearer ")
      } else if strings.HasPrefix(value, "Basic ") {
        basicToken = strings.TrimPrefix(value, "Basic ")
      } else {
        log.Warning("Malformed Authorization header")
        http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
        return
      }
    }

    if bearerToken != "" {
      token = bearerToken
    } else if basicToken != "" {
      token = basicToken
    }
    
    var totalArrEntries int
    authMap.Range(func(k, _ interface{}) bool {
      key := k.(string)
      totalArrEntries++

      if key == token {
        matches++
        // Uncomment to return early
        // return false
      }
      return true
    })

    if matches >= 1 {
      log.Debug("Auth Cached for " + req.RemoteAddr + " (TTL: " + fmt.Sprintf("%.2f", timeDiff.Seconds()) + ", " + strconv.Itoa(totalArrEntries) + " session(s))")
      if req.URL.Query().Get("type") == "check-token" {
        jsonData, err = json.Marshal(Auth{Token: token})
        if err != nil {
          log.Error("Cannot marshal Token to JSON: " + err.Error())
          return
        }
        jsonDataStr = string(jsonData)
        io.WriteString(w, jsonDataStr)
        return
      }
      next.ServeHTTP(w, req)
    } else {
      log.Debug("Reauthentication required for " + req.RemoteAddr)
      next.ServeHTTP(w, req)
      // http.Redirect(w, req, "/api/auth", http.StatusFound)
      // http.Error(w, formatHttpError("Forbidden"), http.StatusForbidden)
      // return
    }
  })
}


func GetInfoHandler(w http.ResponseWriter, r *http.Request) {
    w.Header().Set("Content-Type", "application/json")
    if err := json.NewEncoder(w).Encode(db.Stats()); err != nil {
        http.Error(w, "Error encoding response", http.StatusInternalServerError)
        return
    }
}


func main() {
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

  go func() {
	  err := http.ListenAndServe("localhost:6060", nil)
    if err != nil {
      log.Error("Profiler error: " + err.Error())
    }
  }()

  // Connect to db with pgx
  log.Info("Attempting connection to database...")
  const dbConnString = "postgres://uitweb:WEB_SVC_PASSWD@127.0.0.1:5432/uitdb?sslmode=disable"
  sqlConn, err := sql.Open("pgx", dbConnString)
  if err != nil  {
    log.Error("Unable to connect to database: \n" + err.Error())
    os.Exit(1)
  }
  defer sqlConn.Close()
  // Check if the database connection is valid
  if err = sqlConn.Ping(); err != nil {
    log.Error("Cannot ping database: \n" + err.Error())
    os.Exit(1)
  }

  sqlConn.SetMaxOpenConns(30)
  sqlConn.SetMaxIdleConns(10)
  sqlConn.SetConnMaxIdleTime(1 * time.Minute)

  log.Info("Connected to database successfully")
  db = sqlConn
  defer db.Close()

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
  mux.Handle("/api/auth", baseMuxChain.thenFunc(refreshClientToken))
  mux.Handle("/api/remote", baseMuxChain.thenFunc(remoteAPI))
  mux.Handle("/api/locations", baseMuxChain.thenFunc(remoteAPI))
  mux.HandleFunc("/dbstats/", GetInfoHandler)


	log.Info("Starting web server")

  httpServer := http.Server{
		Addr: ":31411",
    Handler: mux,
    ReadTimeout: time.Duration(10) * time.Second,
    WriteTimeout: time.Duration(10) * time.Second,
    IdleTimeout: time.Duration(120) * time.Second,
    MaxHeaderBytes: 32 << 20,
	}

  log.Info("Web server ready and listening for requests on https://*:31411")

	log.Error(httpServer.ListenAndServeTLS("/usr/local/share/ca-certificates/uit-web.crt", "/usr/local/share/ca-certificates/uit-web.key").Error())
  if err != nil {
    log.Error("Cannot start web server: " + err.Error())
    os.Exit(1)
  }
  defer httpServer.Close()
}