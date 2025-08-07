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
	// "crypto/sha256"
  "crypto/rand"
  "net/url"
  "errors"
  "database/sql"

  _ "net/http/pprof"
  _ "github.com/jackc/pgx/v5/stdlib"
)


//IMPORTANT: Order of struct matters for javacript for some reason
// Structs for JSON responses
type LiveImage struct {
  TimeFormatted   *string    `json:"time_formatted"`
  Screenshot      *string    `json:"screenshot"`
}

type RemotePresent struct {
  Tagnumber                   *string           `json:"tagnumber"`
  LastJobTimeFormatted        *string           `json:"last_job_time_formatted"`
  LocationFormatted           *string           `json:"location_formatted"`
  Status                      *string           `json:"status"`
  OsInstalled                 *bool             `json:"os_installed"`
  OsInstalledFormatted        *string           `json:"os_installed_formatted"`
  BatteryChargeFormatted      *string           `json:"battery_charge_formatted"`
  Uptime                      *string           `json:"uptime"`
  CpuTempFormatted            *string           `json:"cpu_temp_formatted"`
  CpuTemp                     *int32            `json:"cpu_temp"`
  DiskTemp                    *string           `json:"disk_temp"`
  WattsNow                    *string           `json:"watts_now"`
  Failstatus                  *int32            `json:"failstatus"`
  Domain                      *string           `json:"domain"`
  TimeFormatted               *string           `json:"time_formatted"`
  JobQueued                   *string           `json:"job_queued"`
  QueuePosition               *int32            `json:"queue_position"`
  PresentBool                 *bool             `json:"present_bool"`
  BiosUpdated                 *bool             `json:"bios_updated"`
  BiosUpdatedFormatted        *string           `json:"bios_updated_formatted"`
  KernelUpdated               *bool             `json:"kernel_updated"`
  JobActive                   *bool             `json:"job_active"`
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

type User struct {
	Username     string `json:"username"`
	PasswordHash string `json:"-"`
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
  authMap map[string]time.Time
)

func formatHttpError (errorString string) (jsonErrStr string) {
  var err error
  var jsonStr httpErrorCodes
  var jsonErr []byte

  jsonStr = httpErrorCodes{Message: errorString}
  jsonErr, err = json.Marshal(jsonStr)
  if err != nil {
    log.Print("Cannot parse JSON error: ", err)
    return
  }

  jsonErrStr = string(jsonErr)

  log.Print(errorString)
  return string(jsonErrStr)
}

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
    if path == "/api/remote" && queries.Get("type") == "live_image" && len(queries.Get("tagnumber")) == 6 {
      sql = `SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, screenshot 
            FROM live_images 
            WHERE tagnumber = $1`
      eventType = "live_image"
    } else if path == "/api/remote" && queries.Get("type") == "remote_present" {
      sql = `SELECT remote.tagnumber, 
        (CASE WHEN remote.status LIKE 'fail%' THEN 1 ELSE 0 END) AS failstatus, t1.domain, 
        TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, locationFormatting(t3.location) AS location_formatted, 
        TO_CHAR(remote.last_job_time, 'MM/DD/YY HH12:MI:SS AM') AS last_job_time_formatted, 
        remote.job_queued, remote.status, t2.queue_position, remote.present_bool, 
        client_health.os_name AS os_installed_formatted, client_health.os_installed, 
        client_health.bios_updated, (CASE WHEN client_health.bios_updated = TRUE THEN 'Yes' ELSE 'No' END) AS bios_updated_formatted, 
        remote.kernel_updated, CONCAT(remote.battery_charge, '%', ' - ', remote.battery_status) AS battery_charge_formatted, 
        AGE(NOW()::timestamp(0), remote.present::timestamp(0)) AS uptime, 
        remote.cpu_temp, CONCAT(remote.cpu_temp, '°C') AS cpu_temp_formatted, CONCAT(remote.disk_temp, '°C') AS disk_temp, 
        CONCAT(remote.watts_now, ' watts') AS watts_now, remote.job_active
      FROM remote 
      LEFT JOIN (SELECT s1.time, s1.tagnumber, s1.domain FROM (SELECT time, tagnumber, domain, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s1 WHERE s1.row_nums = 1) t1
        ON remote.tagnumber = t1.tagnumber
      LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
      LEFT JOIN (SELECT tagnumber, location, row_nums FROM (SELECT tagnumber, location, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s3 WHERE s3.row_nums = 1) t3
        ON t3.tagnumber = remote.tagnumber
      LEFT JOIN (SELECT tagnumber, queue_position FROM (SELECT tagnumber, ROW_NUMBER() OVER (ORDER BY tagnumber ASC) AS queue_position FROM remote WHERE job_queued IS NOT NULL) s2) t2
        ON remote.tagnumber = t2.tagnumber
      WHERE remote.present_bool = TRUE
      ORDER BY
      failstatus DESC,
      (CASE WHEN remote.status LIKE 'fail%' THEN 1 ELSE 0 END) DESC, job_queued IS NULL ASC, (CASE WHEN job_active = TRUE THEN 10 ELSE 5 END) DESC, queue_position ASC,
        (CASE WHEN job_queued = 'data collection' THEN 20 WHEN job_queued = 'update' THEN 15 WHEN job_queued = 'nvmeVerify' THEN 14 WHEN job_queued =  'nvmeErase' THEN 12 WHEN job_queued =  'hpCloneOnly' THEN 11 WHEN job_queued = 'hpEraseAndClone' THEN 10 WHEN job_queued = 'findmy' THEN 8 WHEN job_queued = 'shutdown' THEN 7 WHEN job_queued = 'fail-test' THEN 5 END) DESC, 
        (CASE WHEN status = 'Waiting for job' THEN 1 ELSE 0 END) ASC, (CASE WHEN client_health.os_installed = TRUE THEN 1 ELSE 0 END) DESC, (CASE WHEN remote.kernel_updated = TRUE THEN 1 ELSE 0 END) DESC, (CASE WHEN client_health.bios_updated = TRUE THEN 1 ELSE 0 END) DESC, remote.last_job_time DESC`
      eventType = "remote_present"
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
  var response Auth
  var BearerToken string
  var jsonResponse []byte


  w, _, err = apiMiddleWare(writer, req)
  if err != nil {
    log.Print("API middleware error: ", err)
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
          http.Error(w, formatHttpError("Cannot parse URL: " + req.URL.RequestURI()), http.StatusBadRequest)
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
        log.Print("Cannot write output to client: ", err)
        http.Error(w, formatHttpError("Cannot write result to http stream: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
      }
      if _, err := io.WriteString(w, jsonString); err != nil {
        log.Print("Cannot write output to client: ", err)
        http.Error(w, formatHttpError("Cannot write result to http stream: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
      }
    } else {
      if _, err := io.WriteString(w, jsonData); err != nil {
        log.Print("Cannot write output to client: ", err)
        http.Error(w, formatHttpError("Cannot write result to http stream: " + fmt.Errorf("%w", err).Error()), http.StatusInternalServerError)
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
  var results any
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
        log.Print(err)
        return "", errors.New("Error querying present clients (main query)")
      }
      defer rows.Close()
      //log.Print("Query executed successfully")

      var remotePresent []RemotePresent
      remotePresent = make([]RemotePresent, 0)
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
          &result.Tagnumber,
          &result.Failstatus,
          &result.Domain,
          &result.TimeFormatted,
          &result.LocationFormatted,
          &result.LastJobTimeFormatted,
          &result.JobQueued,
          &result.Status,
          &result.QueuePosition,
          &result.PresentBool,
          &result.OsInstalledFormatted,
          &result.OsInstalled,
          &result.BiosUpdated,
          &result.BiosUpdatedFormatted,
          &result.KernelUpdated,
          &result.BatteryChargeFormatted,
          &result.Uptime,
          &result.CpuTemp,
          &result.CpuTempFormatted,
          &result.DiskTemp,
          &result.WattsNow,
          &result.JobActive,
        )
        
        if err != nil {
          log.Print("Error scanning row: ", err)
          return "", errors.New("Error scanning row")
        }
        remotePresent = append(remotePresent, result)
      }

      if err != nil {
        return "", errors.New("Error querying present clients (main query)")
      }
      results = remotePresent // Assign results to remotePresent

    case "locations": // Test query
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
    log.Print("Cannot parse URL: " + req.URL.RequestURI())
    http.Error(w, formatHttpError("Cannot parse URL: " + req.URL.RequestURI()), http.StatusInternalServerError)
    return nil, "", errors.New("Cannot parse URL: " + req.URL.RequestURI())
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
    log.Print("Invalid request method: ", req.Method)
    http.Error(w, formatHttpError("Invalid request method" + req.Method), http.StatusMethodNotAllowed)
    return nil, "", errors.New("Invalid request method: " + req.Method)
  }

  // Check if Content-Type is valid
  if req.Header.Get("Content-Type") != "application/x-www-form-urlencoded" && req.Header.Get("Content-Type") != "application/json" {
    log.Print("Invalid Content-Type: ", req.Header.Get("Content-Type"))
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

  log.Print("Received request: ", req.Method, " ", req.URL.RequestURI())

  if req.Method == http.MethodGet {
    w, token, err = apiMiddleWare(w, req)
    if err != nil {
      log.Print("API middleware error: ", err.Error())
      return "", errors.New("API middleware error: " + err.Error())
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

    // Check if token is in authMap
    for key, value := range authMap {
      timeDiff = value.Sub(time.Now())

      // Set second timeout below. Will countdown from timeout seconds.
      if timeDiff.Seconds() < 0 {
        delete(authMap, key)
        log.Print("Auth session expired: ", key, " (TTL: ", timeDiff, ")")
      }
    }

    for key, _ := range authMap {
      var match int32
      if key == token {
        match++
        matches = match
      }

      if matches >= 1 {
        // log.Print("Auth Cached: ", key, " (TTL: ", timeDiff, ")")
        log.Print("Auth Cached: ", "(TTL: ", timeDiff, ")")
        return key, nil
      }
    }

    // Check if DB connection is valid
    if db == nil {
      http.Error(w, formatHttpError("Connection to database failed"), http.StatusInternalServerError)
      return "", errors.New("Connection to database failed")
    }
    if dbCTX.Err() != nil {
      log.Print("Context error: ", dbCTX.Err()) 
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

        TTLDuration = time.Second * 10
        authMap[hashedTokenStr] = time.Now().Add(TTLDuration)
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

  conn.SetMaxIdleConns(10)
  conn.SetConnMaxIdleTime(1 * time.Minute)

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