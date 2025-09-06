package main

import (
	"context"
	"crypto/rand"
	"encoding/base64"
	"encoding/json"
	"math"
	"net/http"
	"strings"
	"sync"
	"time"
)

func countAuthSessions(m *sync.Map) int {
	count := 0
	m.Range(func(_, _ interface{}) bool {
		count++
		return true
	})
	return count
}

func formatHttpError(errorString string) (jsonErrStr string) {
	jsonStr := httpErrorCodes{Message: errorString}
	jsonErr, err := json.Marshal(jsonStr)
	if err != nil {
		log.Error("Cannot parse JSON: " + err.Error())
		return
	}
	return string(jsonErr)
}

func rateLimitCheck(ctx context.Context, ipAddrChan <-chan string, bannedChan chan<- bool, rateLimitChan chan<- int) {
	// requestLimit (per second) is float64 because request rate is a float64
	var requestLimit float64 = 200
	var bannedTimeout time.Duration = time.Second * 10

	select {
	case requestIPAddr := <-ipAddrChan:
		ipMap.Range(func(k, v any) bool {
			key := k.(string)
			value := v.(RateLimiter)

			var banned bool
			var numOfRequests int
			var timeDiff float64
			var rate float64
			var requestRate float64

			if key == requestIPAddr {
				timeDiff = math.Abs(time.Until(value.LastSeen).Seconds())
				numOfRequests = value.Requests + 1
				rate = float64(numOfRequests) / timeDiff
				requestRate = rate * (1 / timeDiff)

				if value.Banned && time.Until(value.BannedUntil).Seconds() > 0 {
					banned = true
					if timeDiff > 1 {
						bannedChan <- banned
						rateLimitChan <- int(math.Round(requestRate))
					}
					close(bannedChan)
					close(rateLimitChan)
					return false
				}

				banned = false
				if timeDiff > 1 {
					if requestRate > requestLimit {
						banned = true
					} else {
						numOfRequests = 0
						banned = false
					}
					ipMap.Store(key, RateLimiter{Requests: numOfRequests, LastSeen: time.Now(), MapLastUpdated: time.Now(), BannedUntil: time.Now().Add(bannedTimeout), Banned: banned})
					bannedChan <- banned
					rateLimitChan <- int(math.Round(requestRate))
					close(bannedChan)
					close(rateLimitChan)
					return false
				} else if timeDiff < 1 {
					ipMap.Store(key, RateLimiter{Requests: numOfRequests, LastSeen: value.LastSeen, MapLastUpdated: value.MapLastUpdated, BannedUntil: time.Now().Add(bannedTimeout), Banned: value.Banned})
					bannedChan <- banned
					rateLimitChan <- int(math.Round(requestRate))
					close(bannedChan)
					close(rateLimitChan)
					return false
				} else {
					return true
				}
			}
			return true
		})
	case <-ctx.Done():
		close(bannedChan)
		close(rateLimitChan)
		return
	}
}

func generateCSRFToken() (string, error) {
	b := make([]byte, 32)
	_, err := rand.Read(b)
	if err != nil {
		return "", err
	}
	return base64.StdEncoding.EncodeToString(b), nil
}

func ParseHeaders(header http.Header) HttpHeaders {
	var headers HttpHeaders
	var authHeader AuthHeader
	for _, value := range header.Values("Authorization") {
		value = strings.TrimSpace(value)
		if strings.HasPrefix(value, "Basic ") {
			basic := strings.TrimSpace(strings.TrimPrefix(value, "Basic "))
			authHeader.Basic = &basic
		}
		if strings.HasPrefix(value, "Bearer ") {
			bearer := strings.TrimSpace(strings.TrimPrefix(value, "Bearer "))
			authHeader.Bearer = &bearer
		}
	}
	headers.Authorization = authHeader
	return headers
}
