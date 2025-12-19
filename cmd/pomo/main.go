package main

import (
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
	
	// register wire3 routes
	RegisterWire3Routes()

	// catch system signal
	chSig := make(chan os.Signal)
	signal.Notify(chSig, syscall.SIGINT, syscall.SIGTERM)
	log.Infof("signal: %s\nservice closing...", <-chSig)

	// stop server
	srv.Stop()
}
