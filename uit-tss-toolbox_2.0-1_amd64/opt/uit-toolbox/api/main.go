//Don't forget - $ go mod init tidy; go mod init hello; go get github.com/jackc/pgx/v5; 
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

  "github.com/jackc/pgx/v5"
)


var serverTime = time.Now()

var conn *pgx.Conn


func db_query() (string, error) {
  dbConnString := "postgres://cameron:WEB_SVC_PASSWD@127.0.0.1:5432/uitdb?sslmode=disable"
  conn, err := pgx.Connect(context.Background(), dbConnString)
  if err != nil {
    fmt.Fprintf(os.Stderr, "Unable to connect to database: %v\n", err)
    os.Exit(1)
  }

  rows, err := conn.Query(context.Background(), "SELECT tagnumber, system_serial, time FROM (SELECT tagnumber, system_serial, time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s1 WHERE s1.row_nums = 1")
    if err != nil {
      log.Print("Query error: %v\n", err)
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
        log.Printf("Error scanning values: %v\n", err)
      }
  
      rowMap := make(map[string]interface{})
      for i, colName := range columnNames {
        rowMap[colName] = values[i]
      }
      results = append(results, rowMap)
    }

    jsonData, err := json.Marshal(results)
    if err != nil {
        log.Print(err)
    }

    return string(jsonData), err
}

func main() {

	apiHandler := func(w http.ResponseWriter, req *http.Request) {
    output, err := db_query()
    if err != nil {
      log.Print("Function returned error")
    }
		io.WriteString(w, output)
	}

	http.HandleFunc("/api", apiHandler)
	log.Print("Server time: " + serverTime.Format("01-02-2006 15:04:05"))
	log.Print("Starting web server on https://localhost:8080")
	httpServerErr := http.ListenAndServeTLS("127.0.0.1:8080", "/etc/ssl/certs/uit-web.crt", "/etc/ssl/private/uit-web.key", nil)
	log.Print(httpServerErr)
	if (httpServerErr != nil) {
		log.Printf("Listening on https://localhost:8080")
	}
}