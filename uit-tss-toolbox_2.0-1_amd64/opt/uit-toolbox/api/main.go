// Don't forget - $ go mod init api; go mod tidy; 
package main

import (
  "context"
  "fmt"
  "os"
  "io"
  "net/http"
  "time"
  "encoding/json"
  "strings"
	// "crypto/sha256"
  "crypto/rand"
  "net/url"
  "errors"
  "database/sql"
  "sync"
  "runtime/debug"

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

type RemotePresentHeader struct {
  TagnumberCount            *string   `json:"tagnumber_count"`
  OsInstalledFormatted      *string   `json:"os_installed_formatted"`
  BatteryChargeFormatted    *string   `json:"battery_charge_formatted"`
  CpuTempFormatted          *string   `json:"cpu_temp_formatted"`
  DiskTempFormatted         *string   `json:"disk_temp_formatted"`
  PowerUsageFormatted       *string   `json:"power_usage_formatted"`
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


var (
	dbCTX context.Context
	webCTX context.Context
  eventType string
  db *sql.DB
  authMap sync.Map
  log = logger.LoggerFactory("console")
  ChannelSqlCode = make(chan string)
  ChannelSqlRows = make(chan *sql.Rows)
)

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
      sql = `SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, screenshot 
            FROM live_images 
            WHERE tagnumber = $1`
      eventType = "live_image"
    } else if path == "/api/remote" && queries.Get("type") == "remote_present" {
      eventType = "remote_present"
    } else if path == "/api/remote" && queries.Get("type") == "remote_offline" {
      eventType = "remote_offline"
    } else if path == "/api/locations" && queries.Get("type") == "all_tags" {
      eventType = "all_tags"
    } else if path == "/api/remote" && queries.Get("type") == "remote_present_header" {
      sql = `SELECT CONCAT('(', COUNT(remote.tagnumber), ')') AS tagnumber_count, CONCAT('(', MIN(remote.battery_charge), '%', '/', MAX(remote.battery_charge), '%', '/', ROUND(AVG(remote.battery_charge), 2), '%', ')') AS battery_charge_formatted, CONCAT('(', MIN(remote.cpu_temp), '°C', '/', MAX(remote.cpu_temp), '°C', '/', ROUND(AVG(remote.cpu_temp), 2), '°C', ')') AS cpu_temp_formatted, CONCAT('(', MIN(remote.disk_temp), '°C',  '/', MAX(remote.disk_temp), '°C' , '/', ROUND(AVG(remote.disk_temp), 2), '°C' , ')') AS disk_temp_formatted, CONCAT('(', COUNT(client_health.os_installed), ')') AS os_installed_formatted, CONCAT('(', SUM(remote.watts_now), ' ', 'watts', ')') AS power_usage_formatted FROM remote LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.present_bool = TRUE`
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



func apiFunction (writer http.ResponseWriter, req *http.Request) {
  var request string
  var sqlCode string
  var tagnumber string
  var systemSerial string
  var err error
  var parsedURL *url.URL
  var queries url.Values
  var w http.ResponseWriter
  var response Auth
  var BearerToken string
  var jsonResponse []byte


  w, _, err = apiMiddleWare(writer, req)
  if err != nil {
    log.Error("Middleware error: " + err.Error())
    return
  }

  BearerToken, err = apiAuth(w, req)
  if err != nil {
    http.Error(w, formatHttpError(fmt.Errorf("Auth error: %w", err).Error()), http.StatusUnauthorized)
    return
  }

  if BearerToken != "" && req.URL.Path == "/api/auth" {
    cookie := http.Cookie{
      Name:     "authCookie",
      // Value:    BearerToken,
      Value:    "Yes",
      Path:     "/",
      MaxAge:   3600,
      HttpOnly: true,
      Secure:   true,
      SameSite: http.SameSiteLaxMode,
    }
    http.SetCookie(w, &cookie)


    response = Auth{Token: BearerToken}
    jsonResponse, err = json.Marshal(response)
    if err != nil {
      http.Error(w, formatHttpError("Cannot format bearer token: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
      return
    }
    w.Write(jsonResponse)
    return
  }


  parsedURL, err = url.Parse(req.URL.RequestURI())
  if err != nil {
    log.Warning("Cannot parse URL: " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
    http.Error(w, formatHttpError("Cannot parse URL: " + req.URL.RequestURI()), http.StatusBadRequest)
    return
  }

  RawQuery := parsedURL.RawQuery
  queries, _ = url.ParseQuery(RawQuery)

  // Process the request based on the method
    switch req.Method {
      case http.MethodGet:
        request = req.URL.RequestURI()
        sqlCode, tagnumber, systemSerial, _, err = getRequestToSQL(request)
        if err != nil {
          log.Warning("Cannot parse URL: " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
          http.Error(w, formatHttpError("Cannot parse URL: " + req.URL.RequestURI()), http.StatusBadRequest)
          return
        }
      // case http.MethodPost:
      // case http.MethodPut:
      // case http.MethodPatch:
      // case http.MethodDelete:
      default:
        http.Error(w, formatHttpError("Method not allowed: " + req.Method), http.StatusMethodNotAllowed)
        return
  }


  jsonData, err := queryResults(sqlCode, tagnumber, systemSerial)
  if err != nil {
    http.Error(w, formatHttpError("Error querying results: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
    return
  }

  



    if queries.Get("sse") == "true" {
      eventString := "event: " + eventType + "\n"
      jsonString := "data: " + jsonData + "\n\n"

      if _, err := io.WriteString(w, eventString); err != nil {
        log.Error("Cannot write output to client: " + err.Error())
        http.Error(w, formatHttpError("Cannot write result to http stream: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
      }
      if _, err := io.WriteString(w, jsonString); err != nil {
        log.Error("Cannot write output to client: " + err.Error())
        http.Error(w, formatHttpError("Cannot write result to http stream: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
      }
    } else {
      if _, err := io.WriteString(w, jsonData); err != nil {
        log.Error("Cannot write output to client: " + err.Error())
        http.Error(w, formatHttpError("Cannot write result to http stream: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
      }
    }

    return
}

func queryResults(sqlCode string, tagnumber string, systemSerial string) (jsonDataStr string, err error) {
  var results any
  // var sqlTime string
  var rows *sql.Rows
  var jsonData []byte

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  // Check if the database connection is valid
  if db == nil {
    log.Error("Database connection is not valid")
    return "", errors.New("Database connection is not valid")
  }
  if dbCTX.Err() != nil {
    log.Error("Context error: " + dbCTX.Err().Error()) 
    return "", errors.New("Context error: " + dbCTX.Err().Error())
  }


  switch eventType {
    case "all_tags":
    var allTagsJson string
    allTagsJson, err = database.GetAllTags(db)
    if err != nil {
      return "", errors.New("Query issue: " + err.Error());
    }
    return allTagsJson, nil
    case "live_image": // Live image query
      if len(tagnumber) != 6 {
        return "", errors.New("Bad tagnumber length (needs to be 6 digits)")
      }
      rows, err = db.QueryContext(dbCTX, sqlCode, tagnumber)
      if err != nil {
        return "", errors.New("Error querying live image")
      }
      defer rows.Close()


      var liveImages []LiveImage
      liveImages = make([]LiveImage, 0) // Ensure liveImages is initialized
      for rows.Next() {
        var result LiveImage
        if dbCTX.Err() != nil {
          log.Error("Context error: " + dbCTX.Err().Error())
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          return "", errors.New("Row error: " + err.Error())
        }
        err = rows.Scan(
          &result.TimeFormatted, 
          &result.Screenshot,
        )
        if err != nil {
          return "", errors.New("Error scanning row")
        }
        liveImages = append(liveImages, result)
      }
      results = liveImages // Assign results to liveImages

    case "remote_present":
      var remoteTableJson string
      remoteTableJson, err = database.GetRemoteOnlineTable(db)
      if err != nil {
        return "", errors.New("Query issue: " + err.Error());
      }
      return remoteTableJson, nil
    case "remote_offline":
      var remoteOfflineTableJson string
      remoteOfflineTableJson, err = database.GetRemoteOfflineTable(db)
      if err != nil {
        return "", errors.New("Query issue: " + err.Error());
      }
      return remoteOfflineTableJson, nil
    case "remote_present_header": 
      rows, err = db.QueryContext(dbCTX, sqlCode)
      if err != nil {
        log.Error("Error querying present clients" + err.Error())
        return "", errors.New("Error querying present clients")
      }
      defer rows.Close()

      var remotePresentHeader []RemotePresentHeader
      remotePresentHeader = make([]RemotePresentHeader, 0)
      for rows.Next() {
        var result RemotePresentHeader
        if dbCTX.Err() != nil {
          log.Error("Context error: " + dbCTX.Err().Error())
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          return "", errors.New("Context error: ")
        }
        err = rows.Scan(
          &result.TagnumberCount,
          &result.BatteryChargeFormatted,
          &result.CpuTempFormatted,
          &result.DiskTempFormatted,
          &result.OsInstalledFormatted,
          &result.PowerUsageFormatted,
        )
        
        if err != nil {
          log.Error("Error scanning row: " + err.Error())
          return "", errors.New("Error scanning row")
        }
        remotePresentHeader = append(remotePresentHeader, result)
      }

      if err != nil {
        return "", errors.New("Error querying present clients (main query)")
      }
      results = remotePresentHeader

    
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


func apiMiddleWare (w http.ResponseWriter, req *http.Request) (writer http.ResponseWriter, clientToken string, err error) {  
  var parsedURL *url.URL
  var queries url.Values
  var bearerToken string
  var basicToken string
  var token string

  parsedURL, err = url.Parse(req.URL.RequestURI())
  if err != nil {
    log.Warning("Cannot parse URL: " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
    http.Error(w, formatHttpError("Cannot parse URL: " + req.URL.RequestURI()), http.StatusInternalServerError)
    return nil, "", errors.New("Cannot parse URL: " + " " + err.Error() + " (" + req.URL.RequestURI() + ")")
  }

  RawQuery := parsedURL.RawQuery
  queries, _ = url.ParseQuery(RawQuery)
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
  // w.Header().Set("Connection", "keep-alive")
  w.Header().Set("X-Accel-Buffering", "no")
  w.WriteHeader(http.StatusOK) // Set the response status to 200 OK


  headerCount := 0
  for _, value := range req.Header["Authorization"] {
    headerCount++
    if strings.HasPrefix(value, "Bearer ") {
      bearerToken = strings.TrimPrefix(value, "Bearer ")
    } else if strings.HasPrefix(value, "Basic ") {
      basicToken = strings.TrimPrefix(value, "Basic ")
    } else {
      http.Error(w, formatHttpError("Malformed authorization header"), http.StatusBadRequest)
      return nil, "", errors.New("Malformed authorization header")
    }
  }

  if headerCount == 0 {
    http.Error(w, formatHttpError("Missing 'Authorization' header: " + req.URL.RequestURI()), http.StatusUnauthorized)
    return nil, "", errors.New("Authorization header missing: " + req.URL.RequestURI())
  }

  // Extract the token from the Authorization header
  if bearerToken != "" {
    token = bearerToken
  } else if basicToken != "" {
    token = basicToken
  }

  // Check if the token is empty
  if token == "" {
    http.Error(w, formatHttpError("Empty Authorization header"), http.StatusUnauthorized)
    return nil, "", errors.New("Empty Authorization header")
  }

  // Check if request method is valid
  if req.Method != http.MethodGet && req.Method != http.MethodPost && req.Method != http.MethodPut && req.Method != http.MethodPatch && req.Method != http.MethodDelete && req.Method != http.MethodOptions {
    log.Warning("Invalid request method: " + req.Method)
    http.Error(w, formatHttpError("Invalid request method" + req.Method), http.StatusMethodNotAllowed)
    return nil, "", errors.New("Invalid request method: " + req.Method)
  }

  // Check if Content-Type is valid
  if req.Header.Get("Content-Type") != "application/x-www-form-urlencoded" && req.Header.Get("Content-Type") != "application/json" {
    log.Warning("Invalid Content-Type: " + req.Header.Get("Content-Type"))
    http.Error(w, formatHttpError("Invalid content type: " + req.Header.Get("Content-Type")), http.StatusUnsupportedMediaType)
    return nil, "", errors.New("Invalid Content-Type: " + req.Header.Get("Content-Type"))
  }

  // Check if request content length exceeds 32 MB
  if req.ContentLength > 32 << 20 {
    http.Error(w, formatHttpError("Request content length exceeds limit: " + fmt.Sprint(req.ContentLength)), http.StatusRequestEntityTooLarge)
    return nil, "", errors.New("Request content length exceeds limit: " + fmt.Sprint(req.ContentLength))
  }

  return w, token, nil
}


func apiAuth (w http.ResponseWriter, req *http.Request) (BearerToken string, err error) {
  var token string
  var rows *sql.Rows
  var matches int32
  var TTLDuration time.Duration
  var timeDiff time.Duration

  log.Info("Received request: " + req.Method + " " + req.URL.RequestURI())

  if req.Method == http.MethodGet {
    w, token, err = apiMiddleWare(w, req)
    if err != nil {
      log.Error("Middleware error: " + err.Error())
      return "", errors.New("API middleware error: " + err.Error())
    }

    dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
    defer cancel()

    authMap.Range(func(k, v interface{}) bool { 
      value := v.(time.Time)
      key := k.(string)
      timeDiff = value.Sub(time.Now())

      // Set second timeout below. Will countdown from timeout seconds.
      if timeDiff.Seconds() < 0 {
        authMap.Delete(key)
        log.Debug("Auth session expired: " + key + " (TTL: " + fmt.Sprintf("%.2f", timeDiff.Seconds()) + ")")
      }
      return true
    })

    authMap.Range(func(k, v interface{}) bool {
      value := v.(time.Time)
      key := k.(string)
      var match int32
      if value.IsZero() {
        authMap.Delete(key)
        return false
      }
      if key == token {
        match++
        matches = match
        return false
      }
      return true

    })

    if matches >= 1 {
      log.Debug("Auth Cached: " + "(TTL: " + fmt.Sprintf("%.2f", timeDiff.Seconds()) + ")")
      return token, nil
    }

    // Check if DB connection is valid
    if db == nil {
      http.Error(w, formatHttpError("Connection to database failed"), http.StatusInternalServerError)
      return "", errors.New("Connection to database failed")
    }
    if dbCTX.Err() != nil {
      log.Error("Context error: " + dbCTX.Err().Error()) 
      http.Error(w, formatHttpError("Context error interrupt"), http.StatusInternalServerError)
      return "", errors.New("Context error: " + dbCTX.Err().Error())
    }

    // Check if the token exists in the database
    sqlCode := `SELECT ENCODE(SHA256(CONCAT(username, ':', password)::bytea), 'hex') as tokens FROM logins WHERE ENCODE(SHA256(CONCAT(username, ':', password)::bytea), 'hex') = $1`
    rows, err = db.QueryContext(dbCTX, sqlCode, token)
    if err != nil {
      http.Error(w, formatHttpError("Cannot query database"), http.StatusInternalServerError)
      return "", errors.New("Cannot query database: " + err.Error())
    }
    defer rows.Close()

    rowCount := 0
    for rows.Next() {
      var dbToken string
      rowCount++

      if err = rows.Scan(&dbToken); err != nil {
        http.Error(w, "Internal server error", http.StatusInternalServerError)
        return "", errors.New("Error scanning token: " + err.Error())
      }
      if dbCTX.Err() != nil {
        http.Error(w, "Internal server error", http.StatusInternalServerError)
        return "", errors.New("Context error: " + dbCTX.Err().Error())
      }
      if dbToken == "" {
        http.Error(w, formatHttpError("Empty token"), http.StatusUnauthorized)
        return "", errors.New("Empty Token")
      }

      if dbToken == token {
        // hash := sha256.New()
        // hash.Write([]byte(dbToken))
        // hashedTokenStr := fmt.Sprintf("%x", hash.Sum(nil))
        hash := make([]byte, 32)
        _, err = rand.Read(hash)
        if err != nil {
          http.Error(w, formatHttpError("Cannot generate token"), http.StatusInternalServerError)
          return "", errors.New("Cannot generate token: " + err.Error())
        }
        hashedTokenStr := fmt.Sprintf("%x", hash)

        TTLDuration = time.Second * 60
        authMap.Store(hashedTokenStr, time.Now().Add(TTLDuration))
        return hashedTokenStr, nil

      } else {
        http.Error(w, formatHttpError("Incorrect credentials"), http.StatusForbidden)
        return "", errors.New("Incorrect credentials")
      }
    }

    if rowCount == 0 {
      http.Error(w, formatHttpError("Token does not exist"), http.StatusForbidden)
      return "", errors.New("Token does not exist")
    }
  }

  http.Error(w, formatHttpError("Invalid request method: " + req.Method), http.StatusMethodNotAllowed)
  return "", errors.New("Invalid request method: " + req.Method)
}


func GetInfoHandler(w http.ResponseWriter, r *http.Request) {
    w.Header().Set("Content-Type", "application/json")
    if err := json.NewEncoder(w).Encode(db.Stats()); err != nil {
        http.Error(w, "Error encoding response", http.StatusInternalServerError)
        return
    }
}

func main() {
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
  mux := http.NewServeMux()
  mux.HandleFunc("/api/", apiFunction)
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

	log.Error(httpServer.ListenAndServeTLS("/usr/local/share/ca-certificates/uit-web.crt", "/usr/local/share/ca-certificates/uit-web.key").Error())
  if err != nil {
    log.Error("Cannot start web server: " + err.Error())
    os.Exit(1)
  }
  defer httpServer.Close()
  log.Info("Web server ready and listening for requests on https://*:31411")

  client := http.Client{
		Timeout: 5 * time.Second,
	}
	log.Info("Testing web server")
  var resp *http.Response
  resp, err = client.Get("https://localhost:31411/api/test")
  if err != nil || resp == nil {
    log.Error("No response from web server... exiting")
    os.Exit(1)
  }
  resp.Body.Close()
  log.Info("Web server running. Application ready!")

}