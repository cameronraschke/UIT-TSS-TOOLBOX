//Don't forget - $ go mod init tidy; go mod init hello; go get github.com/jackc/pgx/v5; 
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

  "github.com/jackc/pgx/v5"
)


var conn *pgx.Conn

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

    if path == "/api/remote" && queries.Get("type") == "live_image" {
      if len(queries.Get("tagnumber")) == 6 {
        sql = `SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, screenshot 
              FROM live_images 
              WHERE tagnumber = $1`
      } else {
        return "", "", "", errors.New("Bad URL request (tagnumber needs to be 6 digits)")
      }
    } else if path == "/api/remote" && queries.Get("type") == "remote_present" {
      sql = `SELECT job_queued, tagnumber, present_bool 
              FROM remote 
              WHERE present_bool = FALSE`
    } else if len(queries.Get("type")) <= 0{
      return "", "", "", errors.New("Bad URL request (empty 'type' key in URL)")
    } else {
      return "", "", "", errors.New("Bad URL request (unknown error)")
    }

    return sql, tagnumber, systemSerial, nil
}

func apiFunction (w http.ResponseWriter, req *http.Request) {
  var request string
  var sqlCode string
  var tagnumber string
  var systemSerial string
  var rows pgx.Rows
  var err error

  request = req.URL.RequestURI()

  sqlCode, tagnumber, systemSerial, err = urlToSql(request)
  if err != nil {

    log.Print("Cannot parse URL: ", err)
    panic("Cannot parse URL")
  }

  // Connect to DB
  const dbConnString = "postgres://uitweb:3096e3109239ec86654ac3ff17892dbb@127.0.0.1:5432/uitdb?sslmode=disable"
  conn, err := pgx.Connect(context.Background(), dbConnString)
  if err != nil  {
    log.Fatal("Unable to connect to database: \n", err)
    os.Exit(1)
  }


  if len(tagnumber) > 0 && len(systemSerial) == 0 {
    rows, err = conn.Query(context.Background(), sqlCode, tagnumber)
  } else if len(tagnumber) == 0 && len(systemSerial) > 0 {
    rows, err = conn.Query(context.Background(), sqlCode, systemSerial)
  } else if len(tagnumber) == 0 && len(systemSerial) == 0 {
    rows, err = conn.Query(context.Background(), sqlCode)
  } else {
    log.Print("Query error: ", err)
    panic("Query error")
  }
    if err != nil {
      log.Print("Query error: ", err)
      panic("Query error")
    }
  defer rows.Close()

    var results []map[string]interface{}
    columnNames := make([]string, len(rows.FieldDescriptions()))
    for i, fd := range rows.FieldDescriptions() {
      columnNames[i] = fd.Name
    }

    for rows.Next() {
      values, err := rows.Values()
      if err != nil {
        log.Print("Error scanning values from SQL rows: ", err)
        panic("Error scanning values from SQL rows")
      }
  
      rowMap := make(map[string]interface{})
      for i, colName := range columnNames {
        rowMap[colName] = values[i]
      }
      results = append(results, rowMap)
    }

    jsonData, err := json.Marshal(results)
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

	log.Fatal(http.ListenAndServeTLS("127.0.0.1:8080", "/etc/ssl/certs/uit-web.crt", "/etc/ssl/private/uit-web.key", mux))

	log.Printf("Listening on https://localhost:8080")
}