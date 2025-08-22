package post

import (
  "errors"
  "time"
  "database/sql"
  "encoding/json"
  "api/database"
  "net/http"
  "encoding/base64"
  "github.com/google/uuid"
  "image"
  _ "image/jpeg"
  _ "image/png"
  "bytes"
  "fmt"
)

type RemoteTable struct {
	Tagnumber           *int          `sql:"tagnumber"`
  JobQueued           *string       `sql:"job_queued"`
  JobQueuedPosition   *int          `sql:"job_queued_position"`
  JobActive           *bool         `sql:"job_queued_position"`
  CloneMode           *string       `sql:"clone_mode"`
  EraseMode           *string       `sql:"erase_mode"`
  LastJobTime         *time.Time    `sql:"last_job_time"`
  Present             *time.Time    `sql:"present"`
  PresentBool         *bool         `sql:"present_bool"`
  Status              *string       `sql:"status"`
  KernelUpdated       *bool         `sql:"kernel_updated"`
  BatteryCharge       *int          `sql:"battery_charge"`
  BatteryStatus       *string       `sql:"battery_status"`
  Uptime              *int          `sql:"uptime"`
  CpuTemp             *int          `sql:"cpu_temp"`
  DiskTemp            *int          `sql:"disk_temp"`
  MaxDiskTemp         *int          `sql:"max_disk_temp"`
  WattsNow            *int          `sql:"watts_now"`
  NetworkSpeed        *int          `sql:"network_speed"`
}

type FormJobQueue struct {
  Tagnumber         string  `json:"job_queued_tagnumber"`
  JobQueued         string  `json:"job_queued"`
}

func UpdateRemote(req *http.Request, db *sql.DB, key string) error {
  // Parse request body JSON
  var j FormJobQueue
  err := json.NewDecoder(req.Body).Decode(&j)
  if err != nil {
    return errors.New("Cannot parse request body JSON: " + err.Error())
  }
  defer req.Body.Close()

  tagnumber := j.Tagnumber
  value := j.JobQueued

  // Commit to DB
  if (key == "job_queued") {
    err := database.UpdateDB(db, "UPDATE remote SET job_queued = $1 WHERE tagnumber = $2", value, tagnumber)
    if err != nil {
      return errors.New("Database error: " + err.Error())
    }
    return nil
  }

  return errors.New("Unknown key: " + key)
}


type FormClientImages struct {
  Tagnumber             *string     `json:"tagnumber"`
  ImageNote             *string     `json:"note"`
}

func UpdateClientImages(req *http.Request, db *sql.DB, key string) error {
  const DefaultQuality = 100
  
  // Parse request body
  err := req.ParseMultipartForm(32 << 20)
  if err != nil {
    return errors.New("File upload too large")
  }
  files := req.MultipartForm.File["userfile"]
  for _, fileHeader := range files {
    file, err := fileHeader.Open()
    if err != nil {
      return errors.New("Cannot open uploaded file for reading")
    }
    defer file.Close()

    uploadedImage, err := jpeg.Decode(file)
    if err != nil {
      return errors.New("Cannot decode uploaded file")
    }

    var b bytes.Buffer
    convertedImage, err := jpeg.Encode(&b, uploadedImage, Options{Quality: 100})
    if err != nil {
      return errors.New("Cannot encode uploaded file")
    }

    EncodedImageData := base64.StdEncoding.EncodeToString([]byte(convertedImage))
    
    note := req.FormValue("note")

    uuidBytes := uuid.New()
    uuid := uuidBytes.String()

    time := time.Now().Format("2025-08-22 00:00:00.000")

    fmt.Println(EncodedImageData)
    fmt.Println(uuid)
    fmt.Println(time)
    fmt.Println(note)

    // var j FormClientImages
    // err := json.NewDecoder(req.Body).Decode(&j)
    // if err != nil {
    //   return errors.New("Cannot parse request body JSON: " + err.Error())
    // }
    defer req.Body.Close()

    // tagnumber := j.Tagnumber
    // value := j.JobQueued

    // // Commit to DB
    // if (key == "job_queued") {
    //   err := database.UpdateDB(db, "UPDATE remote SET job_queued = $1 WHERE tagnumber = $2", value, tagnumber)
    //   if err != nil {
    //     return errors.New("Database error: " + err.Error())
    //   }
    //   return nil
    // }
    return nil
  }

  return errors.New("Unknown key: " + key)
}