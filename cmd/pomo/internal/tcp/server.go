// Copyright 2020 Fred<fred@kuaixu.ltd>. All Rights Reserved.
// Use of this source code is governed by a MIT style
// license that can be found in the LICENSE file.
package tcp

import (
	"net"
	"sync"
	"sync/atomic"
	"time"
)

type Config struct {
	MaxConn         int // max conn num
	PacketTimeout   int // read or write timeout unit second
	PacketChanLimit int // the limit of packet channel
	PacketTimer     int // the polling timer unit second
	PacketSlice     int // the loop duration unit millisecond
}

type Server struct {
	seq       int64           // client id sequence
	config    *Config         // server configuration
	protocol  Protocol        // customize packet protocol
	callback  ConnCallback    // message callbacks in connection
	exitChan  chan struct{}   // notify all goroutines to shutdown
	waitGroup *sync.WaitGroup // wait for all goroutines
	conns     *sync.Map
}

// create a server
func NewServer(config *Config, callback ConnCallback, protocol Protocol) *Server {
	return &Server{
		config:    config,
		callback:  callback,
		protocol:  protocol,
		exitChan:  make(chan struct{}),
		waitGroup: &sync.WaitGroup{},
		conns:     &sync.Map{},
	}
}

// start service
func (s *Server) Start(listener *net.TCPListener, acceptTimeout time.Duration) {
	s.waitGroup.Add(1)
	defer func() {
		listener.Close()
		s.waitGroup.Done()
	}()

	for {
		select {
		case <-s.exitChan:
			return
		default:
		}

		listener.SetDeadline(time.Now().Add(acceptTimeout))

		conn, err := listener.AcceptTCP()
		if err != nil {
			continue
		}

		// max conn limit
		if s.config.MaxConn != 0 && s.ConnNum() >= s.config.MaxConn {
			conn.Close()
			continue
		}

		s.waitGroup.Add(1)
		go func() {
			id := atomic.AddInt64(&s.seq, 1)
			c := newConn(id, conn, s)
			s.conns.Store(id, c)

			c.Do()

			s.waitGroup.Done()
		}()
	}
}

// stop service
func (s *Server) Stop() {
	close(s.exitChan)
	s.waitGroup.Wait()
}

// conn num
func (s *Server) ConnNum() int {
	var i int
	s.conns.Range(func(k, v interface{}) bool {
		i++
		return true
	})
	return i
}

func (s *Server) Conns() *sync.Map {
	return s.conns
}
