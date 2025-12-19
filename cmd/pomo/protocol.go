package main

import (
	"errors"
	"io"
	"net"

	log "github.com/sirupsen/logrus"

	tcp "pomo/internal/tcp"
)

const (
	HEAD = 0xAA
	TAIL = 0x55
)

// Pack is a utility function to read from the supplied Writer
// according to the Next protocol spec:
//
//	AA[x][x][x][x][x][x][x][x][x]55
//	   |  |  |    (binary)  |  |
//	   |  |1-byte           |  | 1byte
//	   |2-byte              |1-byte
//	 ---------------------------
//	size cmd   data    serial  crc
type Packet struct {
	buff []byte
}

func (this *Packet) Serialize() []byte {
	return this.buff
}

func (this *Packet) Size() uint32 {
	return uint32(len(this.buff)) - 7
}

func (this *Packet) Cmd() byte {
	return this.buff[3]
}

func (this *Packet) Body() []byte {
	if size := this.Size(); size > 0 {
		return this.buff[4 : 4+size]
	}

	return nil
}

var tk uint8

func NewPacketRaw(data []byte) *Packet {
	return &Packet{
		buff: data,
	}
}
func NewPacket(cmd byte, data []byte) *Packet {
	p := &Packet{}

	size := 7 + len(data)

	p.buff = make([]byte, size)
	p.buff[0] = HEAD
	p.buff[1] = byte(size >> 8)
	p.buff[2] = byte(size & 0xff)
	p.buff[3] = cmd
	copy(p.buff[4:], data)
	p.buff[size-1] = TAIL
	p.buff[size-2] = crc(p.buff[1 : size-2])
	p.buff[size-3] = tk

	if tk == 255 {
		tk = 0
	} else {
		tk += 1
	}

	return p
}

type Protocol struct {
}

func (this *Protocol) ReadPacket(conn *net.TCPConn) (tcp.Packet, error) {
	packet := make([]byte, 65535)
	size, err := conn.Read(packet)
	if err != nil {
		if err != io.EOF {
			log.Infof("read error: %v\n", err)
		}
		return nil, tcp.ErrReadBlocking
	}

	packet = packet[:size]
	fd := conn.RemoteAddr().String()
    // 原始包十六进制日志，便于定位非协议帧
    log.Infof("%s - RAW %x\n", fd, packet)
	if size < 6 {
		log.Infof("%s -\033[31;1m ERR size\033[0m %x\n", fd, packet)
		return nil, errors.New("data size is error")
	}

	if packet[0] != HEAD {
		log.Infof("%s -\033[31;1m ERR\033[0m %x\n", fd, packet)
	}

	// filter package
	p := &Packet{}
	p.buff = packet
	for i := 0; i < size; i++ {
		if packet[i] != HEAD || i+1 > size {
			continue
		}

		lan := int(packet[i+1]<<8) + int(packet[i+2])
		idx := lan + i - 1
		if idx >= size || idx == -1 || packet[idx] != TAIL {
			continue
		}

		p.buff = make([]byte, lan)
		copy(p.buff, packet[i:idx+1])

		cc := crc(p.buff[1 : lan-2])
		if cc != p.buff[lan-2] {
			log.Infof("%s -\033[31;1m ERR crc\033[0m %x\n", fd, p.buff)
		}

		break
	}

	return p, nil
}
