package main

import (
	"crypto/rand"
	"encoding/base64"
	"encoding/json"
	"net/http"
	"strings"
	"sync"
	"time"

	"golang.org/x/time/rate"
)

type LimiterMap struct {
	m        sync.Map
	interval int64
	burst    int
}

type BlockedMap struct {
	m         sync.Map
	banPeriod time.Duration
}

var (
	rateLimitBurst       int
	rateLimitInterval    int64
	rateLimitBanDuration time.Duration
	ipRequests           *LimiterMap
	blockedIPs           *BlockedMap
)

func countAuthSessions(m *sync.Map) int {
	authSessionCount := 0
	m.Range(func(_, _ interface{}) bool {
		authSessionCount++
		return true
	})
	return authSessionCount
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

func (lm *LimiterMap) Get(ip string) *rate.Limiter {
	newLimiter := rate.NewLimiter(rate.Limit(lm.interval), lm.burst)
	queriedLimiter, _ := lm.m.LoadOrStore(ip, newLimiter)
	existingLimiter, ok := queriedLimiter.(*rate.Limiter)
	if !ok {
		lm.m.Delete(ip)
		lm.m.Store(ip, newLimiter)
		return newLimiter
	}
	return existingLimiter
}

func (lm *LimiterMap) Delete(ip string) {
	lm.m.Delete(ip)
}

func (bm *BlockedMap) IsBlocked(ip string) bool {
	val, ok := bm.m.Load(ip)
	if !ok {
		return false
	}
	unblockTime, ok := val.(time.Time)
	if !ok {
		bm.m.Delete(ip)
		return false
	}
	if time.Now().After(unblockTime) {
		bm.m.Delete(ip)
		return false
	}
	return true
}

func (bm *BlockedMap) Block(ip string) {
	bm.m.Store(ip, time.Now().Add(bm.banPeriod))
}

func getLimiter(requestIP string) *rate.Limiter {
	return ipRequests.Get(requestIP)
}

func isBlocked(requestIP string) bool {
	return blockedIPs.IsBlocked(requestIP)
}

func blockIP(requestIP string) {
	blockedIPs.Block(requestIP)
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
