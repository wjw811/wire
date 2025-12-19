package main

import (
	"time"

	log "github.com/sirupsen/logrus"
	tcp "pomo/internal/tcp"
)

type Callback struct{}

func (this *Callback) OnConnect(c *tcp.Conn) bool {
	log.Infof("client connect: %s\n", c.Addr())

	return true
}

func (this *Callback) OnMessage(c *tcp.Conn, p tcp.Packet) bool {
	packet := p.(*Packet)
	tm := time.Now().UTC()

	defer func(p *Packet, tm time.Time) {
		duration := time.Now().Sub(tm)
		log.Infof("%s#%s -\033[32;1m TCP %x\033[0m %x - %v\n", c.Addr(), c.SN, p.Cmd(), p.Serialize(), duration)
	}(packet, tm)

	NewDevice().Do(c, packet)
	return true
}

func (this *Callback) OnClose(c *tcp.Conn) {
	log.Infof("client close: %s\n", c.Addr())
}
