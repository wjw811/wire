package main

import (
	"encoding/hex"
	"net"
	"net/http"
	_ "net/http/pprof"
	"os"
	"os/signal"
	"syscall"
	"time"

	log "github.com/sirupsen/logrus"
	"github.com/spf13/viper"

	tcp "pomo/internal/tcp"
)

var kv *viper.Viper

func init() {
	log.SetOutput(os.Stdout)
	log.SetLevel(log.DebugLevel)
	log.SetFormatter(&log.TextFormatter{
		FullTimestamp: true,
	})

	// load config yaml
	viper.SetConfigName("config")
	viper.SetConfigType("yml")
	viper.AddConfigPath(".")
	if err := viper.ReadInConfig(); err != nil {
		log.Errorf("Fatal error config file: %s \n", err)
	}

	// load data yaml
	kv = viper.New()
	kv.SetConfigName("data")
	kv.SetConfigType("yml")
	kv.AddConfigPath(".")
	if err := kv.ReadInConfig(); err != nil {
		log.Errorf("Fatal error data file: %s \n", err)
	}
	kv.WatchConfig()
}

func main() {
	go http.ListenAndServe(":2010", nil)

	// create server
	config := &tcp.Config{
		PacketTimeout:   viper.GetInt("tcp.timeout"),
		PacketChanLimit: viper.GetInt("tcp.chan"),
		MaxConn:         viper.GetInt("tcp.maxconn"),
		PacketTimer:     viper.GetInt("tcp.timer"),
		PacketSlice:     viper.GetInt("tcp.slice"),
	}
	srv := tcp.NewServer(config, &Callback{}, &Protocol{})

	// start service
	addr, err := net.ResolveTCPAddr("tcp4", viper.GetString("tcp.url"))
	checkError(err)
	listener, err := net.ListenTCP("tcp", addr)
	checkError(err)
	go srv.Start(listener, time.Second)
	log.Infof("start tcp: %s\n", listener.Addr())

	// http service
	go pub(srv)
	
	// start global broadcast (20s)
	startBroadcast(srv)
	
	// register wire3 routes
	RegisterWire3Routes()

	// catch system signal
	chSig := make(chan os.Signal)
	signal.Notify(chSig, syscall.SIGINT, syscall.SIGTERM)
	log.Infof("signal: %s\nservice closing...", <-chSig)

	// stop server
	srv.Stop()
}

// startBroadcast 每20秒向所有在线设备发送一次巡检指令
func startBroadcast(srv *tcp.Server) {
	ticker := time.NewTicker(20 * time.Second)
	device := NewDevice()
	go func() {
		log.Infof("[Broadcast] Started global polling task (20s interval)")
		for range ticker.C {
			connCount := 0
			srv.Conns().Range(func(key, val interface{}) bool {
				conn := val.(*tcp.Conn)
				// 只有已授权并分配了 SN 的连接才发送
				if conn.SN != "" {
					// 生成查询命令：Slave ID = 1, 地址 = 0x4156 (AVK查询，大部分设备支持)
					queryCmd := device.wire3Proto.GenerateQueryCommand(1, 0x4156)
					if cmd, err := hex.DecodeString(queryCmd); err == nil {
						// 构造外层协议并发送
						packet := NewPacket(C_DA1, cmd)
						err := conn.AsyncWritePacket(packet, time.Second)
						if err != nil {
							log.Errorf("[Broadcast] Send error to %s: %v", conn.SN, err)
						} else {
							connCount++
						}
					}
				}
				return true
			})
			if connCount > 0 {
				log.Infof("[Broadcast] Sent periodic query to %d active connections", connCount)
			}
		}
	}()
}
