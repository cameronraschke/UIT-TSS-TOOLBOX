package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"strconv"
	"strings"
	"time"
)

func jsonEncode(v any) (jsonStr string, err error) {
	jsonBytes, err := json.Marshal(v)
	if err != nil {
		return "", err
	}
	return string(jsonBytes), nil
}

type serverTime struct {
	Time string `json:"server_time"`
}

func getServerTime(w http.ResponseWriter, r *http.Request) {
	requestIP, ok := GetRequestIP(r)
	if !ok {
		log.Warning("no IP address stored in context")
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}
	requestURL, ok := GetRequestURL(r)
	if !ok {
		log.Warning("no URL stored in context")
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}

	jsonData, err := jsonEncode(serverTime{
		Time: time.Now().Format("2006-01-02 15:04:05.000"),
	})
	if err != nil || strings.TrimSpace(jsonData) == "" {
		log.Error("Cannot parse JSON from " + requestIP + " (" + requestURL + "): " + err.Error())
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}
	io.WriteString(w, jsonData)
}

type clientLookup struct {
	Tagnumber    int    `json:"tagnumber"`
	SystemSerial string `json:"system_serial"`
}

func dbSelClientLookup(ctx context.Context, db *sql.DB, tagnumber int, serial string) (string, error) {
	var sqlQuery string
	var results []*clientLookup

	if strings.TrimSpace(serial) != "" {
		sqlQuery = "SELECT tagnumber, system_serial FROM locations WHERE system_serial = $1 ORDER BY time DESC LIMIT 1;"
	} else if tagnumber > 0 {
		sqlQuery = "SELECT tagnumber, system_serial FROM locations WHERE tagnumber = $1 ORDER BY time DESC LIMIT 1;"
	} else {
		return "", errors.New("no tagnumber or system_serial provided")
	}

	rows, err := db.QueryContext(ctx, sqlQuery, tagnumber)
	if err != nil {
		return "", errors.New("Context error: " + err.Error())
	}
	defer rows.Close()

	for rows.Next() {
		row := &clientLookup{}
		if err := rows.Scan(
			&row.Tagnumber,
			&row.SystemSerial,
		); err != nil {
			return "", errors.New("Error scanning rows: " + err.Error())
		}
		results = append(results, row)
	}
	if err := rows.Err(); err != nil {
		return "", errors.New("Query error: " + err.Error())
	}
	if err := ctx.Err(); err != nil {
		return "", errors.New("Context error: " + err.Error())
	}
	if len(results) == 0 {
		return "", errors.New("no results found")
	}

	jsonStr, err := jsonEncode(results)
	if err != nil {
		return "", errors.New("Error encoding results to JSON: " + err.Error())
	}

	return jsonStr, nil
}

func getClientLookup(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()
	requestIP, ok := GetRequestIP(r)
	if !ok {
		log.Warning("no IP address stored in context")
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}
	requestURL, ok := GetRequestURL(r)
	if !ok {
		log.Warning("no URL stored in context")
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}

	tag := strings.TrimSpace(r.URL.Query().Get("tagnumber"))
	systemSerial := strings.TrimSpace(r.URL.Query().Get("system_serial"))
	if tag == "" && systemSerial == "" {
		log.Warning("No tagnumber or system_serial provided in request from: " + requestIP + " (" + requestURL + ")")
		http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
		return
	}

	var tagnumber, err = strconv.Atoi(tag)
	if err != nil || tagnumber <= 0 {
		tagnumber = 0
	}
	jsonStr, err := dbSelClientLookup(ctx, db, tagnumber, systemSerial)
	if err != nil {
		log.Warning("Database lookup failed for: " + requestIP + " (" + requestURL + "): " + err.Error())
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}
	io.WriteString(w, jsonStr)
}

type hardwareData struct {
	Tagnumber               int    `json:"tagnumber"`
	SystemSerial            string `json:"system_serial"`
	EthernetMAC             string `json:"ethernet_mac"`
	WifiMac                 string `json:"wifi_mac"`
	SystemModel             string `json:"system_model"`
	SystemUUID              string `json:"system_uuid"`
	SystemSKU               string `json:"system_sku"`
	ChassisType             string `json:"chassis_type"`
	MotherboardManufacturer string `json:"motherboard_manufacturer"`
	MotherboardSerial       string `json:"motherboard_serial"`
	SystemManufacturer      string `json:"system_manufacturer"`
}

func dbSelHardwareData(ctx context.Context, db *sql.DB, tagnumber int) (string, error) {
	var sqlQuery string
	var results []*hardwareData

	if tagnumber <= 0 {
		return "", errors.New("no tagnumber provided")
	}

	sqlQuery = `SELECT locations.tagnumber, locations.system_serial, jobstats.etheraddress, system_data.wifi_mac,
	system_data.system_model, system_data.system_uuid, system_data.system_sku, system_data.chassis_type, 
	system_data.motherboard_manufacturer, system_data.motherboard_serial, system_data.system_manufacturer
	FROM locations
	LEFT JOIN jobstats ON locations.tagnumber = jobstats.tagnumber AND jobstats.time IN (SELECT MAX(time) FROM jobstats GROUP BY tagnumber)
	LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
	WHERE locations.time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)
	AND locations.tagnumber = $1`

	rows, err := db.QueryContext(ctx, sqlQuery, tagnumber)
	if err != nil {
		return "", errors.New("Context error: " + err.Error())
	}
	defer rows.Close()

	for rows.Next() {
		row := &hardwareData{}
		if err := rows.Scan(
			&row.Tagnumber,
			&row.SystemSerial,
			&row.EthernetMAC,
			&row.WifiMac,
			&row.SystemModel,
			&row.SystemUUID,
			&row.SystemSKU,
			&row.ChassisType,
			&row.MotherboardManufacturer,
			&row.MotherboardSerial,
			&row.SystemManufacturer,
		); err != nil {
			return "", errors.New("Error scanning rows: " + err.Error())
		}
		results = append(results, row)
	}
	if err := rows.Err(); err != nil {
		return "", errors.New("Query error: " + err.Error())
	}
	if err := ctx.Err(); err != nil {
		return "", errors.New("Context error: " + err.Error())
	}
	if len(results) == 0 {
		return "", errors.New("no results found")
	}

	jsonStr, err := jsonEncode(results)
	if err != nil {
		return "", errors.New("Error encoding results to JSON: " + err.Error())
	}

	return jsonStr, nil
}

func getHardwareData(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()
	requestIP, ok := GetRequestIP(r)
	if !ok {
		log.Warning("no IP address stored in context")
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}
	requestURL, ok := GetRequestURL(r)
	if !ok {
		log.Warning("no URL stored in context")
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}

	tag := strings.TrimSpace(r.URL.Query().Get("tagnumber"))
	if tag == "" {
		log.Warning("No tagnumber provided in request from: " + requestIP + " (" + requestURL + ")")
		http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
		return
	}

	var tagnumber, err = strconv.Atoi(tag)
	if err != nil || tagnumber <= 0 {
		tagnumber = 0
	}
	jsonStr, err := dbSelHardwareData(ctx, db, tagnumber)
	if err != nil {
		log.Warning("Database lookup failed for: " + requestIP + " (" + requestURL + "): " + err.Error())
		http.Error(w, formatHttpError("Internal server error"), http.StatusInternalServerError)
		return
	}
	io.WriteString(w, jsonStr)
}
