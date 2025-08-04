// Don't forget - $ go mod init main; go mod tidy; 
package main

import (
  "context"
  "fmt"
  "os"
  "io"
  "net/http"
  "log"
  "time"
  "encoding/json"
  "strings"
  "crypto/md5"
  "net/url"
  "errors"
  "database/sql"

  _ "net/http/pprof"
  _ "github.com/jackc/pgx/v5/stdlib"
)

// Structs for JSON responses
type LiveImage struct {
  TimeFormatted   *string    `json:"time_formatted"`
  Screenshot      *string    `json:"screenshot"`
}

type RemotePresent struct {
  JobQueued      *string   `json:"job_queued"`
  Tagnumber      *string `json:"tagnumber"`
  PresentBool    *bool   `json:"present_bool"`
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

type TagLookup struct {
  Tagnumber *string `json:"tagnumber"`
}

type Auth struct {
	Token string `json:"token"`
}

type User struct {
	Username     string `json:"username"`
	PasswordHash string `json:"-"`
}

type StoredAuth struct {
  Expires time.Time
  Hash    string
}


var (
	dbCTX context.Context
	webCTX context.Context
  eventType string
  db *sql.DB
  authMap map[string]time.Time
)

func getRequestToSQL(requestURL string) (sql string, tagnumber string, systemSerial string, sqlTime string, err error) {
    var path string
    var parsedURL *url.URL
    var queries url.Values

    parsedURL, err = url.Parse(requestURL)
    if err != nil {
      log.Print("Cannot parse URL: " + requestURL)
      return "", "", "", "", errors.New("Cannot parse URL: " + requestURL)
    }

    path = parsedURL.Path

    RawQuery := parsedURL.RawQuery
    queries, _ = url.ParseQuery(RawQuery)

    tagnumber = queries.Get("tagnumber")
    systemSerial = queries.Get("system_serial")
    sqlTime = queries.Get("time")

    // Query type determination
    if path == "/api/remote" && queries.Get("type") == "live_image" {
      if len(queries.Get("tagnumber")) == 6 {
        sql = `SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, screenshot 
              FROM live_images 
              WHERE tagnumber = $1`
        eventType = "live_image" // First query type
      } else {
        return "", "", "", "", errors.New("Bad URL request (tagnumber needs to be 6 digits)")
        eventType = "err" // Error query type
      }
    } else if path == "/api/remote" && queries.Get("type") == "remote_present" {
      sql = `SELECT job_queued, tagnumber, present_bool 
              FROM remote 
              WHERE present_bool = FALSE`
      eventType = "remote_present"
    } else if path == "/api/test" && queries.Get("type") == "test" {
      sql = `SELECT * FROM locations ORDER BY time DESC LIMIT 100`
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
      return "", "", "", "", errors.New("Bad URL request (empty 'type' key in URL)")
    } else {
      eventType = "err"
      return "", "", "", "", errors.New("Bad URL request (unknown error)")
    }

    return sql, tagnumber, systemSerial, sqlTime, nil
}

// func postRequestToSQL (req http.Request) (sql string, []tagnumber, []systemSerial, []sqlTime time.Time, err error) {
//   var uniqueIteratedValues []string
//   // If method is POST, read the form data
//   if req.Method == http.MethodPost {
//     err := req.ParseMultipartForm(32 << 20)
//     if err != nil {
//       log.Print("Failed to parse form: ", err)
//       http.Error(w, "Failed to parse form", http.StatusInternalServerError)
//       return "", "", "", "", nil
//     }

//     if req.Form != nil && len(req.Form) > 0 {
//       for key, values := range req.Form {
//         switch key {
//           case "tagnumber":
//             for _, value := range values {
//               if len(value) != 6 {
//                 log.Print("Bad tag number length: ", value)
//                 http.Error(w, "Bad tag numebr length", http.StatusBadRequest)
//                 return "", "", "", "", nil
//               }
//               tagnumber = append(tagnumber, value)
//             }

//           case "system_serial":
//             for _, value := range values {
//               if len(value) < 1 {
//                 log.Print("Bad system serial length: ", value)
//                 http.Error(w, "Bad system serial length", http.StatusBadRequest)
//                 return "", "", "", "", nil
//               }
//               systemSerial = append(systemSerial, value)
//             }

//           default:
//             log.Printf("Unknown form key: %s with values: %v", key, values)
//             http.Error(w, "Unknown form key", http.StatusBadRequest)
//             return "", "", "", "", nil
//         }
//       }
//     } else {
//       log.Print("No form values found in request")
//       http.Error(w, "No form values found in request", http.StatusBadRequest)
//       return "", "", "", "", nil
//     }
//   }
  
//   return sql, tagnumber, systemSerial, time, nil
// }



func apiFunction (writer http.ResponseWriter, req *http.Request) {
  var request string
  var sqlCode string
  var tagnumber string
  var systemSerial string
  var err error
  var parsedURL *url.URL
  var queries url.Values
  var w http.ResponseWriter


  w, err = apiMiddleWare(writer, req)
  if err != nil {
    log.Print("API middleware error: ", err)
    return
  }

  err = apiAuth(w, req)
  if err != nil {
    log.Print(err)
    http.Error(w, "Auth Error: ", http.StatusUnauthorized)
    return
  }

  parsedURL, err = url.Parse(req.URL.RequestURI())
  if err != nil {
    log.Print("Cannot parse URL: " + req.URL.RequestURI())
    http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
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
          log.Print("Cannot parse URL: ", err)
          http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
          return
        }
      // case http.MethodPost:
      //   log.Print("POST request received")
      //   sqlCode, tagnumber, systemSerial, sqlTime, err = postRequestToSQL(req)
      //   if err != nil {
      //     log.Print("Cannot parse URL: ", err)
      //     http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
      //     return
      //   }
      // case http.MethodPut:
      //   log.Print("PUT request received")
      //   sqlCode, tagnumber, systemSerial, sqlTime, err = requestToSQL(request)
      //   if err != nil {
      //     log.Print("Cannot parse URL: ", err)
      //     http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
      //     return
      //   }
      // case http.MethodPatch:
      //   log.Print("PATCH request received")
      //   sqlCode, tagnumber, systemSerial, sqlTime, err = requestToSQL(request)
      //   if err != nil {
      //     log.Print("Cannot parse URL: ", err)
      //     http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
      //     return
      //   }
      // case http.MethodDelete:
      //   log.Print("DELETE request received")
      //   sqlCode, tagnumber, systemSerial, sqlTime, err = requestToSQL(request)
      //   if err != nil {
      //     log.Print("Cannot parse URL: ", err)
      //     http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
      //     return
      //   }
      default:
        log.Print("Unknown request method: ", req.Method)
        http.Error(w, "Unknown request method", http.StatusMethodNotAllowed)
        return
  }


  jsonData, err := queryResults(sqlCode, tagnumber, systemSerial)
  if err != nil {
    log.Print("Error querying results: ", err)
    http.Error(w, "Error querying results", http.StatusInternalServerError)
    return
  }

  



    if queries.Get("sse") == "true" {
      eventString := "event: " + eventType + "\n"
      jsonString := "data: " + jsonData + "\n\n"

      if _, err := io.WriteString(w, eventString); err != nil {
        log.Print("Cannot write output to client: ", err)
        http.Error(w, "Cannot write output to client", http.StatusInternalServerError)
      }
      if _, err := io.WriteString(w, jsonString); err != nil {
        log.Print("Cannot write output to client: ", err)
        http.Error(w, "Cannot write output to client", http.StatusInternalServerError)
      }
    } else {
      if _, err := io.WriteString(w, jsonData); err != nil {
        log.Print("Cannot write output to client: ", err)
        http.Error(w, "Cannot write output to client", http.StatusInternalServerError)
      }
    }



  // if len(jsonData) < 1 {
  //   log.Print("No results found for query: ", sqlCode)
  //   http.Error(w, "No results found", http.StatusNotFound)
  //   return
  // }
  // jsonEncoder := json.NewEncoder(w)
  // jsonEncoder.Encode(jsonData)
  return
}

func queryResults(sqlCode string, tagnumber string, systemSerial string) (jsonDataStr string, err error) {
  var results any // Results will be of type []LiveImage, []RemotePresent, or []Locations
  // var sqlTime string
  var rows *sql.Rows
  var jsonData []byte

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  // Check if the database connection is valid
  if db == nil {
    log.Print("Database connection is not valid")
    return "", errors.New("Database connection is not valid")
  }
  if dbCTX.Err() != nil {
    log.Print("Context error: ", dbCTX.Err()) 
    return "", errors.New("Context error: " + dbCTX.Err().Error())
  }


  switch eventType {
    case "live_image": // Live image query
      if len(tagnumber) != 6 {
        return "", errors.New("Bad tagnumber length (needs to be 6 digits)")
      }
      //log.Print("Executing live image query for tagnumber: ", tagnumber)
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
          log.Print("Context error: ", dbCTX.Err())
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

    case "remote_present": // Remote present query
      //log.Print("Executing remote query")
      rows, err = db.QueryContext(dbCTX, sqlCode)
      if err != nil {
        return "", errors.New("Error querying present clients")
      }
      defer rows.Close()
      //log.Print("Query executed successfully")

      var remotePresent []RemotePresent // Initialize remotePresent slice
      remotePresent = make([]RemotePresent, 0) // Ensure remotePresent is initialized
      for rows.Next() {
        var result RemotePresent
        if dbCTX.Err() != nil {
          log.Print("Context error: ", dbCTX.Err())
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          return "", errors.New("Context error: ")
        }
        err = rows.Scan(
          &result.JobQueued, 
          &result.Tagnumber, 
          &result.PresentBool,
        )
        if err != nil {
          return "", errors.New("Error scanning row")
        }
        remotePresent = append(remotePresent, result)
      }

      if err != nil {
        return "", errors.New("Error querying present clients")
      }
      results = remotePresent // Assign results to remotePresent

    case "test": // Test query
      //log.Print("Executing test query")
      rows, err = db.QueryContext(dbCTX, sqlCode)
      if err != nil {
        return "", errors.New("Error querying locations")
      }
      defer rows.Close()
      //log.Print("Query executed successfully")
      
      var locations []Locations // Initialize Locations slice
      locations = make([]Locations, 0) // Ensure Locations is initialized
      for rows.Next() {
        var result Locations
        if dbCTX.Err() != nil {
          log.Print("Context error: ", dbCTX.Err())
          return "", errors.New("Context error: " + dbCTX.Err().Error())
        }
        if err = rows.Err(); err != nil {
          log.Print("Context error: ", dbCTX.Err())
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
      //log.Print("Executing tag lookup query for system serial: ", systemSerial)
      rows, err = db.QueryContext(dbCTX, sqlCode, systemSerial)
      if err != nil {
        return "", errors.New("Error querying tag lookup")
      }
      defer rows.Close()
      //log.Print("Query executed successfully")

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


func apiMiddleWare (w http.ResponseWriter, req *http.Request) (writer http.ResponseWriter, err error) {  
  log.Print("Received request: ", req.Method, " ", req.URL.RequestURI())
  var parsedURL *url.URL
  var queries url.Values
  var token string


  // Check for the Authorization header
  authHeader := req.Header.Get("Authorization")
  if authHeader == "" {
    log.Print("Authorization header is missing")
    http.Error(w, "Unauthorized", http.StatusUnauthorized)
    return nil, errors.New("Authorization header is missing")
  }

  // Check if the Authorization header starts with "Bearer "
  if !strings.HasPrefix(authHeader, "Bearer ") {
    http.Error(w, "Unauthorized", http.StatusUnauthorized)
    return nil, errors.New("Authorization header must start with 'Bearer '")
  }

  // Extract the token from the Authorization header
  token = strings.TrimPrefix(authHeader, "Bearer ")

  // Check if the token is empty
  if token == "" {
    http.Error(w, "Unauthorized", http.StatusUnauthorized)
    return nil, errors.New("Authorization token is empty")
  }

  // Check if request method is valid
  if req.Method != http.MethodGet && req.Method != http.MethodPost && req.Method != http.MethodPut && req.Method != http.MethodPatch && req.Method != http.MethodDelete && req.Method != http.MethodOptions {
    log.Print("Invalid request method: ", req.Method)
    http.Error(w, "Invalid request method", http.StatusMethodNotAllowed)
    return nil, errors.New("Invalid request method: " + req.Method)
  }

  // Check if Content-Type is valid
  if req.Header.Get("Content-Type") != "application/x-www-form-urlencoded" && req.Header.Get("Content-Type") != "application/json" {
    log.Print("Invalid Content-Type: ", req.Header.Get("Content-Type"))
    // http.Error(w, "Invalid Content-Type", http.StatusUnsupportedMediaType)
    // return nil, errors.New("Invalid Content-Type: " + req.Header.Get("Content-Type"))
  }

  // Check if request content length exceeds 32 MB
  if req.ContentLength > 32 << 20 { // 32 MB limit
    log.Print("Request content length exceeds limit: ", req.ContentLength)
    http.Error(w, "Request content length exceeds limit", http.StatusRequestEntityTooLarge)
    return nil, errors.New("Request content length exceeds limit: " + fmt.Sprint(req.ContentLength))
  }


  parsedURL, err = url.Parse(req.URL.RequestURI())
  if err != nil {
    log.Print("Cannot parse URL: " + req.URL.RequestURI())
    http.Error(w, "Cannot parse URL", http.StatusInternalServerError)
    return nil, errors.New("Cannot parse URL: " + req.URL.RequestURI())
  }

  RawQuery := parsedURL.RawQuery
  queries, _ = url.ParseQuery(RawQuery)
  // Validate request method and headers
  if req.Method == http.MethodOptions {
    if queries.Get("sse") == "true" {
      w.Header().Set("Content-Type", "text/event-stream")
    } else {
      w.Header().Set("Content-Type", "application/json")
    }

    w.Header().Set("Access-Control-Allow-Origin", "*")
    w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
    w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
    w.Header().Set("Cache-Control", "no-cache, no-store, must-revalidate")
    w.Header().Set("Pragma", "no-cache")
    w.Header().Set("Expires", "0")
    // w.Header().Set("Connection", "keep-alive")
    w.Header().Set("X-Accel-Buffering", "no")
    w.WriteHeader(http.StatusOK) // Set the response status to 200 OK
  }

  return w, nil
}


func apiAuth (w http.ResponseWriter, req *http.Request) (err error) {
  var authHeader string
  var token string
  var rows *sql.Rows
  var hashedToken [16]byte
  var hashedTokenStr string
  var response Auth
  var jsonResponse []byte
  var matches int32

  if req.Method == http.MethodGet {
    w, err = apiMiddleWare(w, req)
    if err != nil {
      log.Print("API middleware error: ", err)
      return errors.New("API middleware error: ")
    }

    dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
    defer cancel()


    // err = json.NewDecoder(req.Body).Decode(&user)
    // if err != nil {
    //   log.Print("Error decoding JSON: ", err)
    //   http.Error(w, "Bad Request", http.StatusBadRequest)
    //   return
    // }
    // if user.Username == "" || user.PasswordHash == "" {
    //   log.Print("Username or password is empty")
    //   http.Error(w, "Bad Request", http.StatusBadRequest)
    //   return
    // }

    authHeader = req.Header.Get("Authorization")
    token = strings.TrimPrefix(authHeader, "Bearer ")

    // Check if auth is in auth map
    for key, value := range authMap {
      // timeDiff := time.Now().Sub(value)
      timeDiff := value.Sub(time.Now())

      // Set second timeout below. Will countdown from timeout seconds.
      if timeDiff.Seconds() < 0 {
        delete(authMap, key)
        // http.Error(w, "Auth session expired", http.StatusUnauthorized)
      }
      var match int32
      if key == token {
        match = match + 1
        matches = match
      }
      if matches == 0 {
        // http.Error(w, "No auth matches", http.StatusUnauthorized)
      } else if matches >= 1 {
        return nil
      }
    }

    // Check if DB connection is valid
    if db == nil {
      log.Print("Database connection is not valid")
      http.Error(w, "Database connection is not valid", http.StatusInternalServerError)
      return errors.New("Auth Error")
    }
    if dbCTX.Err() != nil {
      log.Print("Context error: ", dbCTX.Err()) 
      http.Error(w, "Context error", http.StatusInternalServerError)
      return errors.New("Auth Error")
    }

    // Check if the token exists in the database
    sqlCode := `SELECT MD5(CONCAT(username, password)) as tokens FROM logins WHERE MD5(CONCAT(username, password)) = $1`
    rows, err = db.QueryContext(dbCTX, sqlCode, token)
    if err != nil {
      log.Print("Error querying database: ", err)
      http.Error(w, "Internal Server Error", http.StatusInternalServerError)
      return errors.New("Auth Error")
    }

    for rows.Next() {
      var dbToken string

      if err = rows.Scan(&dbToken); err != nil {
        log.Print("Error scanning token: ", err)
        http.Error(w, "Error scanning token", http.StatusInternalServerError)
        return errors.New("Auth Error")
      }
      if dbCTX.Err() != nil {
        log.Print("Context error: ", dbCTX.Err())
        http.Error(w, "Context error", http.StatusInternalServerError)
        return errors.New("Auth Error")
      }
      if dbToken == "" {
        log.Print("Unauthorized access attempt with token: ", token)
        http.Error(w, "Unauthorized", http.StatusUnauthorized)
        return errors.New("Auth Error")
      }

      if string(dbToken) == token {
        hashedToken = md5.Sum([]byte(token))
        hashedTokenStr = fmt.Sprintf("%x", hashedToken)

        authMap[hashedTokenStr] = time.Now().Add(time.Second * 10)

        response = Auth{Token: hashedTokenStr}
        jsonResponse, err = json.Marshal(response)
        if err != nil {
          log.Print("Error creating JSON response: ", err)
          http.Error(w, "Internal Server Error", http.StatusInternalServerError)
          return errors.New("Auth Error")
        }
        w.Write(jsonResponse)
        return nil
        
      } else {
        log.Print("Invalid token: ", token)
        http.Error(w, "Forbidden", http.StatusForbidden)
        return errors.New("Auth Error")
      }
    }
    defer rows.Close()
  }
  return errors.New("Auth Error")
}



func main() {
  // Recover from panics
  defer func() {
    if pan := recover(); pan != nil {
        log.Println("Recovered. Error:\n", pan)
    }
  }()

  go func() {
	  log.Println(http.ListenAndServe("localhost:6060", nil))
  }()

  // Connect to the database
  log.Print("Connecting to database...")
  // Use the pgx driver for PostgreSQL
  const dbConnString = "postgres://uitweb:WEB_SVC_PASSWD@127.0.0.1:5432/uitdb?sslmode=disable"
  conn, err := sql.Open("pgx", dbConnString)
  if err != nil  {
    log.Fatal("Unable to connect to database: \n", err)
    os.Exit(1)
  }
  defer conn.Close()
  // Check if the database connection is valid
  if err = conn.Ping(); err != nil {
    log.Fatal("Cannot ping database: \n", err)
    os.Exit(1)
  }

  log.Print("Connected to database successfully")
  db = conn // Assign the database connection to the global variable

  webCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()


  // Check if the web context is valid
  if webCTX.Err() != nil {
    log.Print("Web context error: ", webCTX.Err())
    panic("Web context error")
  }

  if dbCTX.Err() != nil {
    log.Print("DB context error: ", dbCTX.Err())
    panic("DB context error")
  }



  // // Check if connection is valid
  // if http.ConnState.String() != "StateActive" {
  //   log.Print("Connection is not active")
  //   panic("Connection is not active")
  // }

  authMap = make(map[string]time.Time, 10)

  // Route to correct function
  mux := http.NewServeMux()
  mux.HandleFunc("/api/", apiFunction)


	log.Print("Server time: " + time.Now().Format("01-02-2006 15:04:05"))
	log.Print("Starting web server on https://*:31411")

    httpServer := http.Server{
		Addr: ":31411",
    Handler: mux,
    ReadTimeout: time.Duration(10) * time.Second,
    WriteTimeout: time.Duration(10) * time.Second,
    IdleTimeout: time.Duration(120) * time.Second,
    MaxHeaderBytes: 32 << 20,
    ErrorLog: log.New(os.Stderr, "ERROR: ", log.LstdFlags),
	}

	log.Fatal(httpServer.ListenAndServeTLS("/usr/local/share/ca-certificates/uit-web.crt", "/usr/local/share/ca-certificates/uit-web.key"))
  defer httpServer.Close()

	log.Printf("Listening on https://*:31411")
}