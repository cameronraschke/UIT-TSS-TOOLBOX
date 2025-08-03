//Don't forget - $ go mod tidy; 
package main

import (
  "context"
  // "fmt"
  "os"
  "io"
  "net/http"
  "log"
  "time"
  "encoding/json"
  "net/url"
  "errors"
  "database/sql"

  _ "github.com/jackc/pgx/v5/stdlib"
)

// Query types:
// 1: Live image query
// 2: Remote present query
// 3: Test query
// 4: Tag lookup query
// 0: Error query type
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




var (
	ctx context.Context
	db  *sql.DB
  queryType uint8
)

func urlToSql(requestURL string) (sql string, tagnumber string, systemSerial string, err error) {
    var path string
    var parsedURL *url.URL
    var queries url.Values

    parsedURL, err = url.Parse(requestURL)
    if err != nil {
      log.Print("Cannot parse URL: " + requestURL)
      panic("Cannot parse URL")
    }

    path = parsedURL.Path

    RawQuery := parsedURL.RawQuery
    queries, _ = url.ParseQuery(RawQuery)

    tagnumber = queries.Get("tagnumber")
    systemSerial = queries.Get("system_serial")

    // Query type determination
    // Query types:
    // 1: Live image query
    // 2: Remote present query
    // 3: Test query (locations)
    // 4: Tag lookup query
    // 0: Error query type
    if path == "/api/remote" && queries.Get("type") == "live_image" {
      if len(queries.Get("tagnumber")) == 6 {
        sql = `SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, screenshot 
              FROM live_images 
              WHERE tagnumber = $1`
        queryType = 1 // First query type
      } else {
        return "", "", "", errors.New("Bad URL request (tagnumber needs to be 6 digits)")
        queryType = 0 // Error query type
      }
    } else if path == "/api/remote" && queries.Get("type") == "remote_present" {
      sql = `SELECT job_queued, tagnumber, present_bool 
              FROM remote 
              WHERE present_bool = FALSE`
      queryType = 2
    } else if path == "/api/test" && queries.Get("type") == "test" {
      sql = `SELECT * FROM locations ORDER BY time DESC LIMIT 100`
      queryType = 3
    } else if path == "/api/remote" && queries.Get("type") == "tag_lookup" && len(queries.Get("system_serial")) >= 1 {
      sql = `SELECT tagnumber FROM locations WHERE system_serial = $1 ORDER BY time DESC LIMIT 1`
      queryType = 4
    } else if len(queries.Get("type")) <= 0{
      queryType = 0
      return "", "", "", errors.New("Bad URL request (empty 'type' key in URL)")
    } else {
      queryType = 0
      return "", "", "", errors.New("Bad URL request (unknown error)")
    }

    return sql, tagnumber, systemSerial, nil
}


func apiFunction (w http.ResponseWriter, req *http.Request) {
  var results any // Results will be of type []LiveImage, []RemotePresent, or []Locations
  var request string
  var sqlCode string
  var tagnumber string
  var systemSerial string
  var jsonData []byte
  var rows *sql.Rows
  var err error

  request = req.URL.RequestURI()
  log.Print("Request: ", request)

  sqlCode, tagnumber, systemSerial, err = urlToSql(request)
  if err != nil {
    log.Print("Cannot parse URL: ", err)
    panic("Cannot parse URL")
  }
  // log.Print("SQL Returned: ", sqlCode)

  // Connect to DB
  // log.Print("Connecting to DB")
  const dbConnString = "postgres://uitweb:WEB_SVC_PASSWD@127.0.0.1:5432/uitdb?sslmode=disable"
  db, err := sql.Open("pgx", dbConnString)
  if err != nil  {
    db.Close()
    log.Fatal("Unable to connect to database: \n", err)
    os.Exit(1)
  }
  defer db.Close()

  // log.Print("Creating context")
  ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second) // Set a timeout for the context
  if err != nil {
    log.Print("Error creating context: ", err)
    panic("Error creating context")
  }
  // Ensure the context is cancelled to avoid memory leaks
  defer cancel()


  switch queryType {
    case 1: // Live image query
      if len(tagnumber) != 6 {
        log.Print("Bad tagnumber length: ", tagnumber)
        panic("Bad tagnumber length")
      }
      log.Print("Executing live image query for tagnumber: ", tagnumber)
      rows, err = db.QueryContext(ctx, sqlCode, tagnumber)
      if err != nil {
        log.Print("Error querying screenshot: ", err)
        panic("Error querying screenshot")
      }
      defer rows.Close()
      log.Print("Query executed successfully")


      var liveImages []LiveImage
      liveImages = make([]LiveImage, 0) // Ensure liveImages is initialized
      for rows.Next() {
        var result LiveImage
        err = rows.Scan(
          &result.TimeFormatted, 
          &result.Screenshot,
        )
        if err != nil {
          log.Print("Error scanning row: ", err)
          panic("Error scanning row")
        }
        liveImages = append(liveImages, result)
      }
      results = liveImages // Assign results to liveImages

    case 2: // Remote present query
      log.Print("Executing remote query")
      rows, err = db.QueryContext(ctx, sqlCode)
      if err != nil {
        log.Print("Error querying remote: ", err)
        panic("Error querying remote")
      }
      defer rows.Close()
      log.Print("Query executed successfully")

      var remotePresent []RemotePresent // Initialize remotePresent slice
      remotePresent = make([]RemotePresent, 0) // Ensure remotePresent is initialized
      for rows.Next() {
        var result RemotePresent
        err = rows.Scan(
          &result.JobQueued, 
          &result.Tagnumber, 
          &result.PresentBool,
        )
        if err != nil {
          log.Print("Error scanning row: ", err)
          panic("Error scanning row")
        }
        remotePresent = append(remotePresent, result)
      }

      if err = rows.Err(); err != nil {
        log.Print("Error with rows: ", err)
        panic("Error with rows")
      }

      if err != nil {
        log.Print("Error querying locations: ", err)
        panic("Error querying locations")
      }
      results = remotePresent // Assign results to remotePresent

    case 3: // Test query
      log.Print("Executing test query")
      rows, err = db.QueryContext(ctx, sqlCode)
      if err != nil {
        log.Print("Error querying locations: ", err)
        panic("Error querying locations")
      }
      defer rows.Close()
      log.Print("Query executed successfully")
      
      var locations []Locations // Initialize Locations slice
      locations = make([]Locations, 0) // Ensure Locations is initialized
      for rows.Next() {
        var result Locations
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
          log.Print("Error scanning row: ", err)
          panic("Error scanning row")
        }
        locations = append(locations, result) // Append result to locations
      }

      if err = rows.Err(); err != nil {
        log.Print("Error with rows: ", err)
        panic("Error with rows")
      }

      if err != nil {
        log.Print("Error querying locations: ", err)
        panic("Error querying locations")
      }
      results = locations // Assign results to Locations

    case 4: 
      log.Print("Executing tag lookup query for system serial: ", systemSerial)
      rows, err = db.QueryContext(ctx, sqlCode, systemSerial)
      if err != nil {
        log.Print("Error querying tag lookup: ", err)
        panic("Error querying tag lookup")
      }
      defer rows.Close()
      log.Print("Query executed successfully")

      var tagLookup []TagLookup // Initialize tagLookup slice
      tagLookup = make([]TagLookup, 0) // Ensure tagLookup is initialized
      for rows.Next() {
        var result TagLookup
        err = rows.Scan(
          &result.Tagnumber,
        )
        if err != nil {
          log.Print("Error scanning row: ", err)
          panic("Error scanning row")
        }
        tagLookup = append(tagLookup, result)
      }

      results = tagLookup // Assign results to tagLookup

    default:
      log.Print("Unknown query type")
      panic("Unknown query type")
  }

  jsonData, err = json.Marshal(results)
  if err != nil {
    log.Print("Cannot marshal json: ", err)
    panic("Cannot marshal json")
  }

  w.Header().Set("Content-Type", "application/json")
  io.WriteString(w, string(jsonData))
}


func main() {
  // Recover from panics
  defer func() {
    if pan := recover(); pan != nil {
        log.Println("Recovered. Error:\n", pan)
    }
  }()

  // Check if connection is valid


  // Route to correct function
  mux := http.NewServeMux()
  mux.HandleFunc("/api/", apiFunction)

	log.Print("Server time: " + time.Now().Format("01-02-2006 15:04:05"))
	log.Print("Starting web server on https://localhost:8080")

	log.Fatal(http.ListenAndServeTLS("127.0.0.1:8080", "/usr/local/share/ca-certificates/uit-web.crt", "/usr/local/share/ca-certificates/uit-web.key", mux))

	log.Printf("Listening on https://localhost:8080")
}