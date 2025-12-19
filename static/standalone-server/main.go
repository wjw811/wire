package main

import (
	"flag"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/gorilla/websocket"
)

var (
	staticDir  = flag.String("static", "_runtime", "Path to static assets")
	httpAddr   = flag.String("http", "0.0.0.0:18080", "HTTP listen address")
	bridgeAddr = flag.String("bridge", "0.0.0.0:18900", "WebSocket bridge listen address")
	deviceURL  = flag.String("device", "tcp://10.10.100.254:18899", "Target device URL (ws:// for WebSocket, tcp:// for TCP)")
)

var upgrader = websocket.Upgrader{
	ReadBufferSize:  32 * 1024,
	WriteBufferSize: 32 * 1024,
	CheckOrigin: func(r *http.Request) bool {
		return true
	},
	// 允许所有来源，类似 websockify
	EnableCompression: true,
}

func main() {
	flag.Parse()

	absStatic, err := filepath.Abs(*staticDir)
	if err != nil {
		log.Fatalf("failed to resolve static directory: %v", err)
	}

	if _, err := os.Stat(absStatic); err != nil {
		log.Fatalf("static directory not found: %s (%v)", absStatic, err)
	}

	u, err := url.Parse(*deviceURL)
	if err != nil {
		log.Fatalf("invalid device url: %v", err)
	}
	if u.Scheme != "ws" && u.Scheme != "wss" && u.Scheme != "tcp" {
		log.Fatalf("device url must use ws://, wss://, or tcp:// scheme")
	}

	go runHTTP(absStatic)
	runBridge(u)
}

func runHTTP(absStatic string) {
	// 确保地址格式正确（如果没有指定IP，默认监听所有接口）
	addr := *httpAddr
	if !strings.Contains(addr, ":") {
		addr = "0.0.0.0:" + addr
	} else if strings.HasPrefix(addr, ":") {
		addr = "0.0.0.0" + addr
	}
	
	mux := http.NewServeMux()
	fileServer := http.FileServer(http.Dir(absStatic))
	
	// 创建处理器：根路径重定向到 device_direct.html，其他路径由文件服务器处理
	rootHandler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path == "/" {
			http.Redirect(w, r, "/device_direct.html", http.StatusFound)
			return
		}
		// 其他路径由文件服务器处理
		fileServer.ServeHTTP(w, r)
	})
	
	mux.Handle("/", logRequest(rootHandler))

	srv := &http.Server{
		Addr:         addr,
		Handler:      mux,
		ReadTimeout:  15 * time.Second,
		WriteTimeout: 30 * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	log.Printf("[HTTP] serving %s on http://%s", absStatic, addr)

	go func() {
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("http server error: %v", err)
		}
	}()
}

func runBridge(target *url.URL) {
	// 确保地址格式正确（如果没有指定IP，默认监听所有接口）
	addr := *bridgeAddr
	if !strings.Contains(addr, ":") {
		addr = "0.0.0.0:" + addr
	} else if strings.HasPrefix(addr, ":") {
		addr = "0.0.0.0" + addr
	}
	
	log.Printf("[WS] bridge listening on ws://%s -> %s", addr, target.String())
	http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		proxyWebSocket(w, r, target)
	})

	srv := &http.Server{
		Addr:         addr,
		ReadTimeout:  0,
		WriteTimeout: 0,
	}

	if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
		log.Fatalf("bridge server error: %v", err)
	}
}

func proxyWebSocket(w http.ResponseWriter, r *http.Request, target *url.URL) {
	clientConn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Printf("[WS] upgrade error: %v", err)
		return
	}

	defer clientConn.Close()

	// 根据目标URL的协议选择不同的桥接方式
	if target.Scheme == "tcp" {
		proxyWebSocketToTCP(clientConn, target)
	} else {
		proxyWebSocketToWebSocket(clientConn, target)
	}
}

func proxyWebSocketToTCP(clientConn *websocket.Conn, target *url.URL) {
	// TCP 连接
	addr := net.JoinHostPort(target.Hostname(), target.Port())
	if target.Port() == "" {
		addr = net.JoinHostPort(target.Hostname(), "18899")
	}

	log.Printf("[WS->TCP] 尝试连接到设备 %s", addr)

	// 尝试连接目标设备，最多重试3次
	var tcpConn net.Conn
	maxRetries := 3
	for i := 0; i < maxRetries; i++ {
		var err error
		tcpConn, err = net.DialTimeout("tcp", addr, 10*time.Second)
		if err == nil {
			break
		}
		log.Printf("[WS->TCP] dial %s failed (尝试 %d/%d): %v", addr, i+1, maxRetries, err)
		if i < maxRetries-1 {
			time.Sleep(2 * time.Second)
		}
	}

	if tcpConn == nil {
		errMsg := fmt.Sprintf("无法连接到设备 %s，已重试 %d 次", addr, maxRetries)
		log.Printf("[WS->TCP] %s", errMsg)
		clientConn.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(websocket.CloseAbnormalClosure, errMsg))
		return
	}
	defer tcpConn.Close()

	log.Printf("[WS->TCP] 成功连接到设备 %s，开始转发数据", addr)

	errc := make(chan error, 2)

	// WebSocket -> TCP
	go func() {
		for {
			msgType, reader, err := clientConn.NextReader()
			if err != nil {
				if !websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
					log.Printf("[WS->TCP] client read error: %v", err)
				}
				errc <- err
				return
			}
			if msgType == websocket.TextMessage || msgType == websocket.BinaryMessage {
				if _, err := io.Copy(tcpConn, reader); err != nil {
					log.Printf("[WS->TCP] copy to TCP error: %v", err)
					errc <- err
					return
				}
			}
		}
	}()

	// TCP -> WebSocket
	go func() {
		buf := make([]byte, 32*1024)
		for {
			n, err := tcpConn.Read(buf)
			if err != nil {
				if err != io.EOF {
					log.Printf("[WS->TCP] TCP read error: %v", err)
				}
				errc <- err
				return
			}
			if n > 0 {
				if err := clientConn.WriteMessage(websocket.BinaryMessage, buf[:n]); err != nil {
					log.Printf("[WS->TCP] write to WS error: %v", err)
					errc <- err
					return
				}
			}
		}
	}()

	// 等待任一方向出错
	err1 := <-errc
	err2 := <-errc

	if err1 != nil && err1 != io.EOF && !websocket.IsCloseError(err1, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
		log.Printf("[WS->TCP] relay error (方向1): %v", err1)
	}
	if err2 != nil && err2 != io.EOF && !websocket.IsCloseError(err2, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
		log.Printf("[WS->TCP] relay error (方向2): %v", err2)
	}
}

func proxyWebSocketToWebSocket(clientConn *websocket.Conn, target *url.URL) {
	dialer := websocket.Dialer{
		Proxy:             http.ProxyFromEnvironment,
		HandshakeTimeout:  30 * time.Second,
		EnableCompression: false,
		ReadBufferSize:    32 * 1024,
		WriteBufferSize:   32 * 1024,
	}

	// 尝试连接目标设备，最多重试3次
	var targetConn *websocket.Conn
	var resp *http.Response
	maxRetries := 3
	for i := 0; i < maxRetries; i++ {
		var err error
		targetConn, resp, err = dialer.Dial(target.String(), nil)
		if err == nil {
			break
		}
		status := 0
		if resp != nil {
			status = resp.StatusCode
		}
		log.Printf("[WS->WS] dial %s failed (尝试 %d/%d): %v (status %d)", target, i+1, maxRetries, err, status)
		if i < maxRetries-1 {
			time.Sleep(2 * time.Second)
		}
	}

	if targetConn == nil {
		status := 0
		if resp != nil {
			status = resp.StatusCode
		}
		errMsg := fmt.Sprintf("无法连接到设备 %s (状态码: %d)，已重试 %d 次", target.String(), status, maxRetries)
		log.Printf("[WS->WS] %s", errMsg)
		clientConn.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(websocket.CloseAbnormalClosure, errMsg))
		return
	}
	defer targetConn.Close()

	log.Printf("[WS->WS] 成功连接到设备 %s，开始转发数据", target.String())

	errc := make(chan error, 2)

	go copyWebSocket(errc, clientConn, targetConn, "client->device")
	go copyWebSocket(errc, targetConn, clientConn, "device->client")

	// 等待任一方向出错
	err1 := <-errc
	err2 := <-errc

	// 记录错误（忽略正常关闭）
	if err1 != nil && !websocket.IsCloseError(err1, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
		log.Printf("[WS->WS] relay error (方向1): %v", err1)
	}
	if err2 != nil && !websocket.IsCloseError(err2, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
		log.Printf("[WS->WS] relay error (方向2): %v", err2)
	}
}

func copyWebSocket(errc chan<- error, src, dst *websocket.Conn, direction string) {
	for {
		msgType, reader, err := src.NextReader()
		if err != nil {
			if !websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
				log.Printf("[WS] %s: read error: %v", direction, err)
			}
			errc <- err
			return
		}

		writer, err := dst.NextWriter(msgType)
		if err != nil {
			if !websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
				log.Printf("[WS] %s: write error: %v", direction, err)
			}
			errc <- err
			return
		}

		if _, err := io.Copy(writer, reader); err != nil {
			if !websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
				log.Printf("[WS] %s: copy error: %v", direction, err)
			}
			errc <- err
			_ = writer.Close()
			return
		}

		if err := writer.Close(); err != nil {
			if !websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseGoingAway) {
				log.Printf("[WS] %s: close error: %v", direction, err)
			}
			errc <- err
			return
		}
	}
}

func logRequest(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()
		next.ServeHTTP(w, r)
		log.Printf("[HTTP] %s %s %s", r.RemoteAddr, r.Method, r.URL.Path)
		if time.Since(start) > time.Second {
			log.Printf("[HTTP] slow request: %s took %s", r.URL.Path, time.Since(start))
		}
	})
}
