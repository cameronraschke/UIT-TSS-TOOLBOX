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

type JobQueueRepository interface {
	GetJobQueueByTagnumber(id int) (*JobQueue, error)
	// Add other methods like CreateUser, UpdateUser, DeleteUser, etc.
}

type PostgresJobQueueRepository struct {
	db *sql.DB
}

func NewPostgresJobQueueRepository(db *sql.DB) *PostgresJobQueueRepository {
	return &PostgresJobQueueRepository{db: db}
}

func (r *PostgresJobQueueRepository) GetJobQueueByTagnumber(id int) (*JobQueue, error) {

  var sqlCode string
  var rows *sql.Rows
  var err error
  var tagnumber int32


  dbCTX, cancel := context.WithTimeout(context.Background(), 10*time.Second) 
  defer cancel()

  sqlCode = `SELECT remote.present_bool, remote.kernel_updated, client_health.bios_updated, 
  remote.status AS remote_status, TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS remote_time_formatted 
  FROM remote 
  LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.tagnumber = $1`

  rows, err = r.db.QueryContext(dbCTX, sqlCode, tagnumber)
  if err != nil {
  return nil, errors.New("Error querying tag lookup")
  }
  defer rows.Close()
  result := &JobQueue{}
  err = rows.Scan(
    &result.PresentBool,
    &result.KernelUpdated,
    &result.BiosUpdated,
    &result.RemoteStatus,
    &result.RemoteTimeFormatted,
  )
  if err != nil {
    return nil, errors.New("Error scanning row: " + err.Error())
  }

  return result, nil
}

