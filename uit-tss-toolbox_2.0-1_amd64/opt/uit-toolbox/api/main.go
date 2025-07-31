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
  "net/url"
  "errors"

  "github.com/jackc/pgx/v5"
)


var conn *pgx.Conn

func urlToSql(requestURL string) (string, string, error) {
    var sql string
    var path string
    var tagnumber string
    var queries url.Values

    parsedURL, err := url.Parse(requestURL)
    if err != nil {
      log.Print("Cannot parse URL: " + requestURL)
    }

    path = parsedURL.Path

    RawQuery := parsedURL.RawQuery
    queries, _ = url.ParseQuery(RawQuery)

    tagnumber = queries.Get("tagnumber")

    if path == "/api/remote" && queries.Get("type") == "live_image"  {
      sql = "SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, screenshot FROM live_images WHERE tagnumber = $1"
    } else {
      return "", "", errors.New("Bad URL request")
    }

      return sql, tagnumber, nil
}

func apiFunction (w http.ResponseWriter, req *http.Request) {
  // requestURL := string(req.URL.Scheme + req.URL.Host + req.URL.Path + req.URL.RawQuery)
  requestURL := req.URL.RequestURI()
  log.Print("[" + time.Now().Format("01-02-2006 15:04:05") + "] " + "Request to " + requestURL)
  sqlCode, tagnumner, err := urlToSql(requestURL)
  if err != nil {
    log.Print("Cannot parse URL: ")
  }

  log.Print(sqlCode)

  // Connect to DB
  dbConnString := "postgres://uitweb:WEB_SVC_PASSWD@127.0.0.1:5432/uitdb?sslmode=disable"
  conn, err := pgx.Connect(context.Background(), dbConnString)
  if err != nil  {
    fmt.Fprintf(os.Stderr, "Unable to connect to database: %v\n", err)
    os.Exit(1)
  }
  //   if ($_GET["type"] == "job_queue" && isset($_GET["tagnumber"])) { 
  //   $dbPSQL->Pselect("SELECT remote.present_bool, remote.kernel_updated, client_health.bios_updated, remote.status AS remote_status, TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS remote_time_formatted FROM remote LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
  //   foreach ($dbPSQL->get() as $key => $value) {
  //     $event = "server_time";
  //     $data = json_encode($value);
  //   }
  //   unset($value);
  // }
  rows, err := conn.Query(context.Background(), sqlCode, tagnumner)
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

    w.Header().Set("Content-Type", "application/json")
    io.WriteString(w, string(jsonData))
}


func main() {
  // Check if connection is valid


  // Route to correct function
  mux := http.NewServeMux()
  mux.HandleFunc("/api/", apiFunction)

	log.Print("Server time: " + time.Now().Format("01-02-2006 15:04:05"))
	log.Print("Starting web server on https://localhost:8080")

	log.Fatal(http.ListenAndServeTLS("127.0.0.1:8080", "/etc/ssl/certs/uit-web.crt", "/etc/ssl/private/uit-web.key", mux))

	log.Printf("Listening on https://localhost:8080")
}