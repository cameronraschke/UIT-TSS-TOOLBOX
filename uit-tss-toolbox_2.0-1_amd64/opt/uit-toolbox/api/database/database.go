package database

import (
"database/sql"
"context"
"time"
"errors"

_ "github.com/jackc/pgx/v5/stdlib"
)

var (
	dbCTX context.Context
)

type JobQueue struct {
  PresentBool     *bool   `json:"present_bool"`
  KernelUpdated   *bool   `json:"kernel_updated"`
  BiosUpdated     *bool   `json:"bios_updated"`
  RemoteStatus    *string   `json:"remote_status"`
  RemoteTimeFormatted *string `json:"remote_time_formatted"`
}

type RemoteOnlineTable struct {
  Tagnumber                   *string           `json:"tagnumber"`
  Screenshot                  *string           `json:"screenshot"`
  LastJobTimeFormatted        *string           `json:"last_job_time_formatted"`
  LocationFormatted           *string           `json:"location_formatted"`
  LocationsStatus             *bool             `json:"locations_status"`
  Status                      *string           `json:"status"`
  OsInstalled                 *bool             `json:"os_installed"`
  OsInstalledFormatted        *string           `json:"os_installed_formatted"`
  BatteryChargeFormatted      *string           `json:"battery_charge_formatted"`
  Uptime                      *string           `json:"uptime"`
  CpuTemp                     *int32            `json:"cpu_temp"`
  CpuTempFormatted            *string           `json:"cpu_temp_formatted"`
  DiskTemp                    *int32            `json:"disk_temp"`
  DiskTempFormatted           *string           `json:"disk_temp_formatted"`
  MaxDiskTemp                 *int32            `json:"max_disk_temp"`
  WattsNow                    *string           `json:"watts_now"`
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


type DBInterface interface {
	GetJobQueueByTagnumber(tagnumber int) ([]*JobQueue, error)
  GetRemoteOnlineTable() ([]*RemoteOnlineTable, error)
}

type DBRepository struct {
	db *sql.DB
}

func NewDBRepository(db *sql.DB) *DBRepository {
	return &DBRepository{db: db}
}

func (r *DBRepository) GetJobQueueByTagnumber(tagnumber int) ([]*JobQueue, error) {
  var sqlCode string
  var rows *sql.Rows
  var err error

  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  sqlCode = `SELECT remote.present_bool, remote.kernel_updated, client_health.bios_updated, 
  remote.status AS remote_status, TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS remote_time_formatted 
  FROM remote 
  LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.tagnumber = $1`

  rows, err = r.db.QueryContext(dbCTX, sqlCode, tagnumber)
  if err != nil {
    return nil, errors.New("Context error while querying job queue: " + err.Error())
  }
  defer rows.Close()

  var results []*JobQueue
  for rows.Next() {
    row := &JobQueue{}
    if err = rows.Err(); err != nil {
      return nil, errors.New("Error with rows: " + err.Error())  
    }
    if dbCTX.Err() != nil {
      return nil, errors.New("Context error: " + dbCTX.Err().Error())
    }
    err = rows.Scan(
      &row.PresentBool,
      &row.KernelUpdated,
      &row.BiosUpdated,
      &row.RemoteStatus,
      &row.RemoteTimeFormatted,
    )
    if err != nil {
      return nil, errors.New("Error scanning row: " + err.Error())
    }

    results = append(results, row)
  }

  return results, nil        
}


func (r *DBRepository) GetRemoteOnlineTable() ([]*RemoteOnlineTable, error) {
  var sqlCode string
  var rows *sql.Rows
  var err error

  sqlCode = `SELECT remote.tagnumber, live_images.screenshot, t1.domain, 
      TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, locationFormatting(t3.location) AS location_formatted, 
      TO_CHAR(remote.last_job_time, 'MM/DD/YY HH12:MI:SS AM') AS last_job_time_formatted, 
      remote.job_queued, remote.status, t2.queue_position, remote.present_bool, 
      client_health.os_name AS os_installed_formatted, client_health.os_installed, t1.locations_status, 
      client_health.bios_updated, (CASE WHEN client_health.bios_updated = TRUE THEN 'Yes' ELSE 'No' END) AS bios_updated_formatted, 
      remote.kernel_updated, CONCAT(remote.battery_charge, '%', ' - ', remote.battery_status) AS battery_charge_formatted, 
      AGE(NOW(), NOW() - (remote.uptime * INTERVAL '1 second')) AS uptime, 
      remote.cpu_temp, CONCAT(remote.cpu_temp, '°C') AS cpu_temp_formatted, 
      remote.disk_temp, CONCAT(remote.disk_temp, '°C') AS disk_temp_formatted, static_disk_stats.max_temp AS max_disk_temp, 
      CONCAT(remote.watts_now, ' watts') AS watts_now, remote.job_active
    FROM remote 
    LEFT JOIN (SELECT s1.time, s1.tagnumber, s1.domain, s1.status AS locations_status FROM (SELECT time, tagnumber, domain, status, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s1 WHERE s1.row_nums = 1) t1
      ON remote.tagnumber = t1.tagnumber
    LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
    LEFT JOIN (SELECT tagnumber, location, row_nums FROM (SELECT tagnumber, location, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s3 WHERE s3.row_nums = 1) t3
      ON t3.tagnumber = remote.tagnumber
    LEFT JOIN (SELECT tagnumber, queue_position FROM (SELECT tagnumber, ROW_NUMBER() OVER (ORDER BY tagnumber ASC) AS queue_position FROM remote WHERE job_queued IS NOT NULL) s2) t2
      ON remote.tagnumber = t2.tagnumber
    LEFT JOIN (SELECT s4.tagnumber, s4.disk_model FROM (SELECT tagnumber, disk_model, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE tagnumber IS NOT NULL AND disk_model IS NOT NULL) s4 WHERE s4.row_nums = 1) t4
      ON remote.tagnumber = t4.tagnumber
    LEFT JOIN static_disk_stats ON static_disk_stats.disk_model = t4.disk_model
    LEFT JOIN live_images ON remote.tagnumber = live_images.tagnumber
    WHERE remote.present_bool = TRUE
    ORDER BY
      remote.status LIKE 'fail%' DESC, job_queued IS NOT NULL DESC, job_active = TRUE DESC, queue_position ASC,
      (CASE WHEN job_queued = 'data collection' THEN 20 WHEN job_queued = 'update' THEN 15 WHEN job_queued = 'nvmeVerify' THEN 14 WHEN job_queued =  'nvmeErase' THEN 12 WHEN job_queued =  'hpCloneOnly' THEN 11 WHEN job_queued = 'hpEraseAndClone' THEN 10 WHEN job_queued = 'findmy' THEN 8 WHEN job_queued = 'shutdown' THEN 7 WHEN job_queued = 'fail-test' THEN 5 END) DESC, 
      status = 'Waiting for job' ASC, client_health.os_installed = TRUE DESC, 
      remote.kernel_updated DESC, t1.locations_status = TRUE DESC, client_health.bios_updated = TRUE DESC, 
      remote.last_job_time DESC`


  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  rows, err = r.db.QueryContext(dbCTX, sqlCode)
  if err != nil {
    return nil, errors.New("Error querying online remote table: " + err.Error())
  }
  defer rows.Close()

  var results []*RemoteOnlineTable
  for rows.Next() {
    row := &RemoteOnlineTable{}
    if err = rows.Err(); err != nil {
      return nil, errors.New("Error with rows: " + err.Error())  
    }
    if dbCTX.Err() != nil {
      return nil, errors.New("Context error: " + dbCTX.Err().Error())
    }
    err = rows.Scan(
      &row.Tagnumber,
      &row.Screenshot,
      &row.Domain,
      &row.TimeFormatted,
      &row.LocationFormatted,
      &row.LastJobTimeFormatted,
      &row.JobQueued,
      &row.LocationsStatus,
      &row.Status,
      &row.QueuePosition,
      &row.PresentBool,
      &row.OsInstalledFormatted,
      &row.OsInstalled,
      &row.BiosUpdated,
      &row.BiosUpdatedFormatted,
      &row.KernelUpdated,
      &row.BatteryChargeFormatted,
      &row.Uptime,
      &row.CpuTemp,
      &row.CpuTempFormatted,
      &row.DiskTemp,
      &row.DiskTempFormatted,
      &row.MaxDiskTemp,
      &row.WattsNow,
      &row.JobActive,
    )
    // Fix this
    // if err != nil && err != sql.ErrNoRows {
    //   return nil, errors.New("Error scanning row: " + err.Error())
    // }

    results = append(results, row)
  }

  return results, nil
}