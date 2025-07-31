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
  // "strconv"
  // "strings"
  // "database/sql"

  "github.com/jackc/pgx/v5"
)

//type 


var serverTime = time.Now()


var conn *pgx.Conn

func db_query() {
  dbConnString := "postgres://cameron:bruh@127.0.0.1:5432/uitdb?sslmode=disable"
  conn, err := pgx.Connect(context.Background(), dbConnString)
  if err != nil {
    fmt.Fprintf(os.Stderr, "Unable to connect to database: %v\n", err)
    os.Exit(1)
  }

  rows, err := conn.Query(context.Background(), "SELECT tagnumber, system_serial, time from (SELECT tagnumber, system_serial, time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s1 WHERE s1.row_nums <= 1")
    if err != nil {
      log.Fatal("Query error: %v\n", err)
    }

    var results []map[string]interface{}
    columnNames := make([]string, len(rows.FieldDescriptions()))
    for i, fd := range rows.FieldDescriptions() {
      columnNames[i] = fd.Name
    }

    for rows.Next() {
      values, err := rows.Values()
      if err != nil {
        log.Fatalf("Error scanning values: %v\n", err)
      }
  
      rowMap := make(map[string]interface{})
      for i, colName := range columnNames {
        rowMap[colName] = values[i]
      }
      results = append(results, rowMap)
    }

    // sqlData := make(map[string]string)
    // for rows.Next() {
    //   var result string
    //   err = rows.Scan(&result)
    //   if err != nil {
    //     fmt.Printf("error: %v\n", err)
    //   }
    //   sqlData["tagnumber"] = result
    // }
      
    // for key, value := range sqlData {
    //   log.Print(key)
    //   log.Print(value)
    // }

    jsonData, err := json.Marshal(results)
    if err != nil {
        log.Fatal(err)
    }

    fmt.Println(string(jsonData) + "\n")
}

func main() {
  db_query()

	apiHandler := func(w http.ResponseWriter, req *http.Request) {
		io.WriteString(w, "Hello, world!\n")
    // Flush()
	}

	http.HandleFunc("/api", apiHandler)
	log.Print("Server time: " + serverTime.Format("01-02-2006 15:04:05"))
	log.Print("Starting web server on https://localhost:8080")
	httpServerErr := http.ListenAndServeTLS("127.0.0.1:8080", "/etc/ssl/certs/uit-web.crt", "/etc/ssl/private/uit-web.key", nil)
	log.Fatal(httpServerErr)
	if (httpServerErr != nil) {
		log.Printf("Listening on https://localhost:8080")
	}
}