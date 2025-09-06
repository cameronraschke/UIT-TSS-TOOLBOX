package main

import (
	"context"
	"crypto/tls"
	"encoding/json"
	"fmt"
	"net"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"time"
)

func allowIPRangeMiddleware(allowedCIDR string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			ipStr, _, err := net.SplitHostPort(r.RemoteAddr)
			if err != nil {
				http.Error(w, "Forbidden", http.StatusForbidden)
				return
			}
			requestIP := net.ParseIP(ipStr)
			_, ipNet, err := net.ParseCIDR(allowedCIDR)
			if err != nil || !ipNet.Contains(requestIP) {
				http.Error(w, "Forbidden", http.StatusForbidden)
				return
			}
			next.ServeHTTP(w, r)
		})
	}
}

func rateLimitMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		requestIP, _, err := net.SplitHostPort(r.RemoteAddr)
		if err != nil {
			http.Error(w, "Bad Request", http.StatusBadRequest)
			return
		}

		if isBlocked(requestIP) {
			http.Error(w, "Too Many Requests (IP temporarily blocked)", http.StatusTooManyRequests)
			return
		}

		limiter := getLimiter(requestIP)
		if !limiter.Allow() {
			blockIP(requestIP)
			http.Error(w, "Too Many Requests (IP temporarily blocked)", http.StatusTooManyRequests)
			return
		}

		next.ServeHTTP(w, r)
	})
}

func timeoutMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		ctx, cancel := context.WithTimeout(req.Context(), 10*time.Second)
		defer cancel()
		req = req.WithContext(ctx)
		next.ServeHTTP(w, req)
	})
}

func tlsMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		if req.TLS == nil || !req.TLS.HandshakeComplete {
			log.Warning("TLS handshake failed for client " + req.RemoteAddr)
			http.Error(w, formatHttpError("TLS required"), http.StatusUpgradeRequired)
			return
		}

		if req.TLS.Version < tls.VersionTLS13 {
			log.Warning("Rejected connection with weak TLS version from " + req.RemoteAddr)
			http.Error(w, formatHttpError("TLS version too low"), http.StatusUpgradeRequired)
			return
		}

		weakCiphers := map[uint16]bool{
			tls.TLS_RSA_WITH_RC4_128_SHA:                true,
			tls.TLS_RSA_WITH_3DES_EDE_CBC_SHA:           true,
			tls.TLS_RSA_WITH_AES_128_CBC_SHA256:         true,
			tls.TLS_ECDHE_ECDSA_WITH_RC4_128_SHA:        true,
			tls.TLS_ECDHE_RSA_WITH_RC4_128_SHA:          true,
			tls.TLS_ECDHE_RSA_WITH_3DES_EDE_CBC_SHA:     true,
			tls.TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA256: true,
			tls.TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA256:   true,
		}
		if weakCiphers[req.TLS.CipherSuite] {
			log.Warning("Rejected connection with weak cipher suite from " + req.RemoteAddr)
			http.Error(w, formatHttpError("Weak cipher suite not allowed"), http.StatusUpgradeRequired)
			return
		}

		next.ServeHTTP(w, req)
	})
}

func httpMethodMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		// Get IP address
		requestIP, _, err := net.SplitHostPort(req.RemoteAddr)
		if err != nil {
			log.Warning("Cannot parse IP: " + req.RemoteAddr)
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Check method
		validMethods := map[string]bool{
			http.MethodOptions: true,
			http.MethodGet:     true,
			http.MethodPost:    true,
			http.MethodPut:     true,
			http.MethodDelete:  true,
		}
		if !validMethods[req.Method] {
			log.Warning("Invalid request method (" + requestIP + "): " + req.Method)
			http.Error(w, formatHttpError("Method not allowed"), http.StatusMethodNotAllowed)
			return
		}

		// Check Content-Type for POST/PUT
		if req.Method == http.MethodPost || req.Method == http.MethodPut {
			contentType := req.Header.Get("Content-Type")
			if contentType != "application/x-www-form-urlencoded" && contentType != "application/json" {
				log.Warning("Invalid Content-Type header: " + contentType + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
				http.Error(w, formatHttpError("Invalid content type"), http.StatusUnsupportedMediaType)
			}
		}
		next.ServeHTTP(w, req)
	})
}

func checkHeadersMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		// Get IP address
		requestIP, _, err := net.SplitHostPort(req.RemoteAddr)
		if err != nil {
			log.Warning("Cannot parse IP: " + req.RemoteAddr)
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Content length
		if req.ContentLength > 64<<20 {
			log.Warning("Request content length exceeds limit: " + fmt.Sprintf("%.2fMB", float64(req.ContentLength)/1e6))
			http.Error(w, formatHttpError("Request too large"), http.StatusRequestEntityTooLarge)
			return
		}

		// URL length
		if len(req.URL.RequestURI()) > 2048 {
			log.Warning("Request URL length exceeds limit: " + fmt.Sprintf("%d", len(req.URL.RequestURI())) + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Request URI too long"), http.StatusRequestURITooLong)
			return
		}

		// URL path
		if strings.ContainsAny(req.URL.Path, "<>\"'%;()&+") {
			log.Warning("Invalid characters in URL path: " + req.URL.Path + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// URL query
		if strings.ContainsAny(req.URL.RawQuery, "<>\"'%;()+") {
			log.Warning("Invalid characters in query parameters: " + req.URL.RawQuery + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Origin header
		origin := req.Header.Get("Origin")
		if origin != "" && len(origin) > 2048 {
			log.Warning("Invalid Origin header: " + origin + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Host header
		host := req.Host
		if strings.TrimSpace(host) == "" || strings.ContainsAny(host, " <>\"'%;()&+") || len(host) > 255 {
			log.Warning("Invalid Host header: " + host + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// User-Agent header
		userAgent := req.Header.Get("User-Agent")
		if userAgent == "" || len(userAgent) > 256 {
			log.Warning("Invalid User-Agent header: " + userAgent + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Referer header
		referer := req.Header.Get("Referer")
		if referer != "" && len(referer) > 2048 {
			log.Warning("Invalid Referer header: " + referer + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Other headers
		// for key, value := range req.Header {
		//   if strings.ContainsAny(key, "<>\"'%;()&+") || strings.ContainsAny(value[0], "<>\"'%;()&+") {
		//     log.Warning("Invalid characters in header '" + key + "': " + value[0] + " (" + requestIP + ": " + req.Method + " " + req.URL.RequestURI() + ")")
		//     http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
		//     return
		//   }
		// }

		next.ServeHTTP(w, req)
	})
}

func setHeadersMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		// Check CORS policy
		cors := http.NewCrossOriginProtection()
		cors.AddTrustedOrigin("https://UIT_WAN_IP_ADDRESS:1411")
		if err := cors.Check(req); err != nil {
			log.Warning("Request to " + req.URL.RequestURI() + " blocked from " + req.RemoteAddr)
			http.Error(w, formatHttpError("CORS policy violation"), http.StatusForbidden)
			return
		}

		// Handle OPTIONS early
		if req.Method == http.MethodOptions {
			// Headers for OPTIONS request
			w.Header().Set("Access-Control-Allow-Origin", "https://UIT_WAN_IP_ADDRESS:1411")
			w.Header().Set("Access-Control-Allow-Credentials", "true")
			w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
			w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, Set-Cookie, credentials")
			w.WriteHeader(http.StatusNoContent)
			return
		}

		w.Header().Set("Access-Control-Allow-Origin", "https://UIT_WAN_IP_ADDRESS:1411")
		w.Header().Set("Access-Control-Allow-Credentials", "true")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("Cache-Control", "no-cache, no-store, must-revalidate")
		w.Header().Set("Pragma", "no-cache")
		w.Header().Set("Expires", "0")
		w.Header().Set("X-Frame-Options", "DENY")
		w.Header().Set("Content-Security-Policy", "frame-ancestors 'self'")
		w.Header().Set("Strict-Transport-Security", "max-age=86400; includeSubDomains")
		w.Header().Set("X-Accel-Buffering", "no")
		w.Header().Set("Referrer-Policy", "no-referrer")
		w.Header().Set("Server", "")
		w.Header().Set("Permissions-Policy", "geolocation=(), microphone=(), camera=()")

		// Deprecated headers
		w.Header().Set("X-XSS-Protection", "1; mode=block")
		w.Header().Set("X-Download-Options", "noopen")
		w.Header().Set("X-Permitted-Cross-Domain-Policies", "none")

		// JSON or SSE response
		parsedURL, _ := url.Parse(req.URL.RequestURI())
		queries, _ := url.ParseQuery(parsedURL.RawQuery)
		if queries.Get("sse") == "true" {
			w.Header().Set("Content-Type", "text/event-stream")
		} else {
			w.Header().Set("Content-Type", "application/json; charset=utf-8")
		}

		next.ServeHTTP(w, req)
	})
}

func apiAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		var requestBasicToken string
		var requestBearerToken string
		var sessionCount int = 0

		// Delete expired tokens & malformed entries out of authMap
		authMap.Range(func(k, v interface{}) bool {
			sessionID := k.(string)
			authSession := v.(AuthSession)
			sessionIP := strings.SplitN(sessionID, ":", 2)[0]

			// basicExpiry := authSession.Basic.Expiry.Sub(time.Now())
			bearerExpiry := time.Until(authSession.Bearer.Expiry)

			// Auth cache entry expires once countdown reaches zero
			if bearerExpiry.Seconds() <= 0 {
				authMap.Delete(sessionID)
				sessionCount = countAuthSessions(&authMap)
				log.Info("Auth session expired: " + sessionIP + " (TTL: " + fmt.Sprintf("%.2f", bearerExpiry.Seconds()) + ", " + strconv.Itoa(int(sessionCount)) + " session(s))")
			}
			return true
		})

		requestIP, _, _ := net.SplitHostPort(req.RemoteAddr)
		queryType := strings.TrimSpace(req.URL.Query().Get("type"))
		if strings.TrimSpace(queryType) == "" {
			log.Warning("No query type defined for request: " + req.RemoteAddr)
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		headers := ParseHeaders(req.Header)
		if headers.Authorization.Basic != nil {
			requestBasicToken = *headers.Authorization.Basic
		}
		if headers.Authorization.Bearer != nil {
			requestBearerToken = *headers.Authorization.Bearer
		}

		if strings.TrimSpace(requestBearerToken) == "" && strings.TrimSpace(requestBasicToken) == "" {
			log.Warning("Empty value for Authorization header")
			http.Error(w, formatHttpError("Unauthorized"), http.StatusUnauthorized)
			return
		}

		basicValid, bearerValid, _, bearerTTL, matchedSession := checkAuthSession(&authMap, requestIP, requestBasicToken, requestBearerToken)

		if (basicValid && bearerValid) || bearerValid {
			if queryType == "check-token" {
				jsonData, err := json.Marshal(returnedJsonToken{
					Token: matchedSession.Bearer.Token,
					TTL:   bearerTTL,
					Valid: true,
				})
				if err != nil {
					log.Error("Cannot marshal Token to JSON: " + err.Error())
					return
				}
				w.Write(jsonData)
				return
			} else if strings.TrimSpace(queryType) != "" {
				next.ServeHTTP(w, req)
			} else {
				log.Warning("No query type defined for bearer token: " + requestIP)
				http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
				return
			}
		} else if (basicValid && !bearerValid) || (!basicValid && !bearerValid) {
			sessionCount = countAuthSessions(&authMap)
			log.Debug("Auth cache miss: " + requestIP + " (Sessions: " + strconv.Itoa(int(sessionCount)) + ")")
			if queryType == "new-token" && strings.TrimSpace(requestBasicToken) != "" {
				next.ServeHTTP(w, req)
			} else {
				http.Error(w, formatHttpError("Unauthorized"), http.StatusUnauthorized)
				return
			}
		} else {
			log.Warning("No valid authentication found for request: " + requestIP)
			http.Error(w, formatHttpError("Unauthorized"), http.StatusUnauthorized)
			return
		}
	})
}

func csrfMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, req *http.Request) {
		// Still testing function

		requestIP, _, err := net.SplitHostPort(req.RemoteAddr)
		if err != nil {
			log.Warning("Cannot parse IP: " + req.RemoteAddr)
			http.Error(w, formatHttpError("Bad request"), http.StatusBadRequest)
			return
		}

		// Only check for state-changing methods
		if req.Method == http.MethodPost || req.Method == http.MethodPut || req.Method == http.MethodPatch || req.Method == http.MethodDelete {
			csrfToken := req.Header.Get("X-CSRF-Token")
			if strings.TrimSpace(csrfToken) == "" {
				log.Warning("Missing CSRF token in request from " + req.RemoteAddr)
				http.Error(w, formatHttpError("Forbidden: missing CSRF token"), http.StatusForbidden)
				return
			}

			headers := ParseHeaders(req.Header)
			var bearerToken string
			if headers.Authorization.Bearer != nil {
				bearerToken = *headers.Authorization.Bearer
			}

			sessionID := requestIP + ":" + bearerToken

			value, ok := authMap.Load(sessionID)
			if !ok {
				log.Warning("No session found for CSRF check: " + sessionID)
				http.Error(w, formatHttpError("Forbidden: invalid session"), http.StatusForbidden)
				return
			}
			authSession := value.(AuthSession)

			if csrfToken != authSession.CSRF {
				log.Warning("Invalid CSRF token for session: " + sessionID)
				http.Error(w, formatHttpError("Forbidden: invalid CSRF token"), http.StatusForbidden)
				return
			}

		}
		next.ServeHTTP(w, req)
	})
}

func denyAllMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "Access denied", http.StatusForbidden)
	})
}
