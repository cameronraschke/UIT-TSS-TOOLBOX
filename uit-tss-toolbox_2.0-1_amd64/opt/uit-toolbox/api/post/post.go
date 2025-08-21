package post

import (
  "errors"
  "time"
  "database/sql"
  "encoding/json"
  "api/database"
  "net/http"
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
    err := database.UpdateDB(db, "UPDATE remote SET job_queued = $1 WHERE tagnumber = $2", tagnumber, value)
    if err != nil {
      return errors.New("Database error: " + err.Error())
    }
    return nil
  }

  return errors.New("Unknown key: " + key)
}