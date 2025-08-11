package services

import (
	"fmt"
	"api/database"
  "errors"
  "encoding/json"
)

type MainService struct {
	repo database.DBInterface
}

func NewMainService(repo database.DBInterface) *MainService {
	return &MainService{repo: repo}
}


func (s *MainService) GetJobQueue(tagnumber int) ([]*database.JobQueue, string, error) {
  var jsonData []byte
  var jsonDataStr string

	results, err := s.repo.GetJobQueueByTagnumber(tagnumber)
	if err != nil {
		return nil, "", fmt.Errorf("failed to get row: %w", err)
	}
	if results == nil {
		return nil, "", fmt.Errorf("No results found for: ", tagnumber)
	}

	jsonData, err = json.Marshal(results)
	if err != nil {
		return nil, "", errors.New("Error creating JSON data: " + err.Error())
	}  
	if len(jsonData) < 1 {
		return nil, "", errors.New("No results found for query")
	}


	// Convert jsonData to string
	if len(jsonData) > 0 {
		jsonDataStr = string(jsonData)
	} else {
		return nil, "", errors.New("No results found for query")
	}


	return results, jsonDataStr, nil
}


func (s *MainService) GetRemoteOnlineTableJson() ([]*database.RemoteOnlineTable, string, error) {
  var jsonData []byte
  var jsonDataStr string

	results, err := s.repo.GetRemoteOnlineTable()
	if err != nil {
		return nil, "", fmt.Errorf("failed to get row: %w", err)
	}
	if results == nil {
		return nil, "", fmt.Errorf("No results found for online table")
	}

	jsonData, err = json.Marshal(results)
	if err != nil {
		return nil, "", errors.New("Error creating JSON data: " + err.Error())
	}  
	if len(jsonData) < 1 {
		return nil, "", errors.New("No results found for query")
	}


	// Convert jsonData to string
	if len(jsonData) > 0 {
		jsonDataStr = string(jsonData)
	} else {
		return nil, "", errors.New("No results found for query")
	}


	return results, jsonDataStr, nil
}


func (s *MainService) GetRemoteOfflineTableJson() ([]*database.RemoteOnlineTable, string, error) {
  var jsonData []byte
  var jsonDataStr string

	results, err := s.repo.GetRemoteOfflineTable()
	if err != nil {
		return nil, "", fmt.Errorf("failed to get row: %w", err)
	}
	if results == nil {
		return nil, "", fmt.Errorf("No results found for online table")
	}

	jsonData, err = json.Marshal(results)
	if err != nil {
		return nil, "", errors.New("Error creating JSON data: " + err.Error())
	}  
	if len(jsonData) < 1 {
		return nil, "", errors.New("No results found for query")
	}


	// Convert jsonData to string
	if len(jsonData) > 0 {
		jsonDataStr = string(jsonData)
	} else {
		return nil, "", errors.New("No results found for query")
	}


	return results, jsonDataStr, nil
}
