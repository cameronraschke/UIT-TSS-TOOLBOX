package services

import (
	"fmt"
	"api/database"
)

type JobQueueService struct {
	repo database.JobQueueRepository
}

func NewJobQueueService(repo database.JobQueueRepository) *JobQueueService {
	return &JobQueueService{repo: repo}
}


func (s *JobQueueService) GetJobQueue(id int) (*database.JobQueue, error) {
	user, err := s.repo.GetJobQueueByTagnumber(id)
	if err != nil {
		return nil, fmt.Errorf("failed to get user: %w", err)
	}
	if user == nil {
		return nil, fmt.Errorf("user with ID %d not found", id)
	}
	return user, nil
}