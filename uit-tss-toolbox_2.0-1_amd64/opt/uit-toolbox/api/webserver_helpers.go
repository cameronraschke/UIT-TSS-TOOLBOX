package main

import (
	"crypto/rand"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"net"
	"net/http"
	"strings"
	"sync"
	"time"

	"golang.org/x/time/rate"
)

type limiterEntry struct {
	limiter  *rate.Limiter
	lastSeen time.Time
}

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
	curTime := time.Now()
	newEntry := &limiterEntry{
		limiter:  rate.NewLimiter(rate.Every(time.Duration(lm.interval)*time.Second), lm.burst),
		lastSeen: curTime,
	}
	queriedLimiter, exists := lm.m.LoadOrStore(ip, newEntry)
	entry := queriedLimiter.(*limiterEntry)
	entry.lastSeen = curTime
	if !exists {
		log.Debug("Created new limiter for IP: " + ip + " interval=" + fmt.Sprint(lm.interval) + " burst=" + fmt.Sprint(lm.burst))
	}

	return entry.limiter
}

func (lm *LimiterMap) Delete(ip string) {
	lm.m.Delete(ip)
}

func (bm *BlockedMap) IsBlocked(ip string) bool {
	blockTime, ok := bm.m.Load(ip)
	if !ok {
		return false
	}
	unblockTime, ok := blockTime.(time.Time)
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

func GetLimiter(requestIP string) *rate.Limiter {
	return ipRequests.Get(requestIP)
}

func IsBlocked(requestIP string) bool {
	return blockedIPs.IsBlocked(requestIP)
}

func BlockIP(requestIP string) {
	blockedIPs.Block(requestIP)
}

func (as *AppState) Cleanup() {
	ttl := time.Now().Add(-10 * time.Minute)
	as.ipRequests.m.Range(func(key, value any) bool {
		entry, ok := value.(*limiterEntry)
		if !ok || entry.lastSeen.Before(ttl) {
			as.ipRequests.m.Delete(key)
		}
		return true
	})

	curTime := time.Now()
	as.blockedIPs.m.Range(func(key, value any) bool {
		unblockTime, ok := value.(time.Time)
		if !ok || curTime.After(unblockTime) {
			as.blockedIPs.m.Delete(key)
		}
		return true
	})
}

func (as *AppState) GetAllBlockedIPs() string {
	var blocked []string
	as.blockedIPs.m.Range(func(key, value any) bool {
		ip, ok := key.(string)
		if ok {
			blocked = append(blocked, ip)
		}
		return true
	})
	return strings.Join(blocked, ", ")
}

func getClientIP(r *http.Request) string {
	var ip string
	if strings.TrimSpace(r.Header.Get("X-Forwarded-For")) != "" {
		ip = strings.Split(r.Header.Get("X-Forwarded-For"), ",")[0]
	} else {
		ip, _, _ = net.SplitHostPort(r.RemoteAddr)
	}
	return ip
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
