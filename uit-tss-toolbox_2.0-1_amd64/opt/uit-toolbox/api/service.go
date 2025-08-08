package service

import (
	"fmt"
	"./database.go"
)

type JobQueueService struct {
	repo database.JobQueueRepository
}

func NewJobQueueService(repo database.JobQueueRepository) *JobQueueService {
	return &JobQueueService{repo: repo}
}


func (s *JobQueueService) GetUser(id int) (*database.JobQueue, error) {
	user, err := s.repo.GetUserByID(id)
	if err != nil {
		return nil, fmt.Errorf("failed to get user: %w", err)
	}
	if user == nil {
		return nil, fmt.Errorf("user with ID %d not found", id)
	}
	return user, nil
}