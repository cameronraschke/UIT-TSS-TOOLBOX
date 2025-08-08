package services

import (
	"fmt"
	"api/database"
  "errors"
  "encoding/json"
)

type JobQueueService struct {
	repo database.JobQueueRepository
}

func NewJobQueueService(repo database.JobQueueRepository) *JobQueueService {
	return &JobQueueService{repo: repo}
}


func (s *JobQueueService) GetJobQueue(tagnumber int) ([]*database.JobQueue, string, error) {
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


func (s *JobQueueService) GetRemoteOnlineTableJson() ([]*database.JobQueue, string, error) {
  var jsonData []byte
  var jsonDataStr string

	results, err := s.repo.GetRemoteOnlineTable()
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