package post

import (
  "errors"
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

func UpdateRemote(db *sql.DB, tagnumber string, key string, value string) error {
  fail := func(err error) (int64, error) {
    return fmt.Errorf("CreateOrder: %v", err)
  }

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  tx, err := db.BeginTx(dbCTX)
  if err != nil {
    return fail(err)
  }
  defer tx.Rollback()

  if (key == "job_queued") {
    result, err = tx.ExecContext(dbCTX, "UPDATE remote SET job_queued = ? WHERE tagnumber = ?",
      value, tagnumber)
    if err != nil {
      return fail(err)
    }

    rowsAffected, err := result.RowsAffected()
    if err != nil {
      return fail(err)
    }

    if rowsAffected != 1 {
      return fail(errors.New("Rows affected are not exactly 1"))
    }

    if err = tx.Commit(); err != nil {
      return fail(err)
    }

    return nil
  }
}