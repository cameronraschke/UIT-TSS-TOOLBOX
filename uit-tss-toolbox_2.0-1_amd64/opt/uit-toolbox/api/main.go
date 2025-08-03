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


type queryLocations struct {
  Time            *time.Time  `json:"time"`
  Tagnumber       *int32      `json:"tagnumber"`
  System_serial   *string     `json:"system_serial"`
  Location        *string     `json:"location"`
  Status          *bool       `json:"status"`
  Disk_removed    *bool       `json:"disk_removed"`
  Department      *string     `json:"department"`
  Domain          *string     `json:"domain"`
  Note            *string     `json:"note"`
}

var (
	ctx context.Context
	// db  *sql.DB
  queryType uint8
)

func urlToSql(requestURL string) (sql string, tagnumber string, systemSerial string, err error) {
    var path string
    var parsedURL *url.URL
    var queries url.Values

    log.Print("Parsing: ", requestURL)

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
      sql = `SELECT * FROM locations LIMIT 50`
      queryType = 3
    } else if len(queries.Get("type")) <= 0{
      queryType = 0
      return "", "", "", errors.New("Bad URL request (empty 'type' key in URL)")
    } else {
      queryType = 0
      return "", "", "", errors.New("Bad URL request (unknown error)")
    }

    log.Print("Returning", sql, tagnumber, systemSerial)
    return sql, tagnumber, systemSerial, nil
}

func apiFunction (w http.ResponseWriter, req *http.Request) {
  var request string
  var sqlCode string
  var tagnumber string
  var systemSerial string
  var rows *sql.Rows
  var err error

  request = req.URL.RequestURI()
  log.Print("Request: ", request)

  sqlCode, tagnumber, systemSerial, err = urlToSql(request)
  if err != nil {
    log.Print("Cannot parse URL: ", err)
    panic("Cannot parse URL")
  }
  log.Print("SQL Returned: ", sqlCode)

  // Connect to DB
  log.Print("Connecting to DB")
  const dbConnString = "postgres://uitweb:448d0e373a0949e9546bdd4238ef9fd0@127.0.0.1:5432/uitdb?sslmode=disable"
  db, err := sql.Open("pgx", dbConnString)
  if err != nil  {
    db.Close()
    log.Fatal("Unable to connect to database: \n", err)
    os.Exit(1)
  }
  defer db.Close()

  log.Print("Creating context")
  ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
  defer cancel()


  log.Print("Checking validity of query")
  if len(tagnumber) > 0 && len(systemSerial) == 0 {
    rows, err = db.QueryContext(ctx, sqlCode, tagnumber)
  } else if len(tagnumber) == 0 && len(systemSerial) > 0 {
    rows, err = db.QueryContext(ctx, sqlCode, systemSerial)
  } else if len(tagnumber) == 0 && len(systemSerial) == 0 {
    rows, err = db.QueryContext(ctx, sqlCode)
  } else {
    log.Print("Query error: ", err)
    panic("Query error")
  }
    if err != nil {
      log.Print("Query error: ", err)
      panic("Query error")
    }
  defer rows.Close()

  log.Print("Creating map")


  var sqlResults []queryLocations
  for rows.Next() {
    var q queryLocations
    err = rows.Scan(&q.Time, &q.Tagnumber, &q.System_serial, &q.Location, &q.Status, &q.Disk_removed, &q.Department, &q.Domain, &q.Note)
    if err != nil {
      log.Print("Error scanning values from SQL rows: ", err)
      panic("Error scanning values from SQL rows")
    }
    sqlResults = append(sqlResults, q)
  }

  jsonData, err := json.Marshal(sqlResults)
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