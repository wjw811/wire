// Copyright 2021 Fred<fred@kuaixu.ltd>. All Rights Reserved.
// Use of this source code is governed by a MIT style
// license that can be found in the LICENSE file.
package main

import (
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
	"time"

	log "github.com/sirupsen/logrus"
	"github.com/spf13/viper"

	tcp "pomo/internal/tcp"
)

type ReqModel struct {
	Sn   string `json:sn`
	Chan byte   `json:chan`
	Data string `json:data`
}

func pub(srv *tcp.Server) {
	http.HandleFunc("/rpc/push", func(rw http.ResponseWriter, rq *http.Request) {
		if rq.Body == nil {
			rw.Write([]byte("ng"))
		}

		var model ReqModel
		if err := json.NewDecoder(rq.Body).Decode(&model); err != nil {
			log.Errorf("pub invoke error: %s", err.Error())
			return
		}
		data, err := hex.DecodeString(model.Data)
		if err != nil || len(data) < 1 {
			rw.Write([]byte("ng"))
			return
		}

		log.Infof("con num: %d, pub data: %v", srv.ConnNum(), model)

		// 解析命令，如果是单个查询则记录等待状态
		if len(data) >= 9 && data[0] == 0xAA && data[3] == 0x02 {
			// 单个查询命令的数据部分有两种形态：
			// 1) 旧：直接 E0 开头（极少用）
			// 2) wire3：内层以 AA 开头，格式 AA [dev] 05 E0 [addr_hi][addr_lo] [crc16]
			if len(data) >= 8 && data[4] == 0xE0 {
				if len(data) >= 7 {
					addr := int(data[5])<<8 | int(data[6])
					RecordWaitingSingleQuery(model.Sn, addr)
				}
			} else if len(data) >= 10 && data[4] == 0xAA && data[7] == 0xE0 {
				addr := int(data[8])<<8 | int(data[9])
				RecordWaitingSingleQuery(model.Sn, addr)
			}
		}

		found := false
		srv.Conns().Range(func(key, val interface{}) bool {
			conn := val.(*tcp.Conn)
			log.Infof("pub > checking conn: %s vs target: %s", conn.SN, model.Sn)
			if conn.SN == model.Sn {
				log.Infof("pub > found matching connection, sending data: %x", data)
				// 数据已经是完整的协议格式，直接发送原始数据
				err := conn.AsyncWritePacket(NewPacketRaw(data), time.Second)
				if err != nil {
					log.Errorf("pub > send error: %s", err.Error())
				} else {
					log.Infof("pub > data sent successfully")
				}
				found = true
				return false // 找到并处理后停止遍历
			}
			return true // 继续遍历
		})
		if !found {
			log.Warnf("pub > no matching connection found for SN: %s", model.Sn)
		}

		rw.Write([]byte("ok"))
	})

	http.HandleFunc("/rpc/set", func(rw http.ResponseWriter, rq *http.Request) {
		if rq.Body == nil {
			rw.Write([]byte("ng"))
		}

		var model ReqModel
		if err := json.NewDecoder(rq.Body).Decode(&model); err != nil {
			log.Errorf("pub invoke error: %s", err.Error())
			return
		}
		data, err := hex.DecodeString(model.Data)
		if err != nil || len(data) < 1 {
			rw.Write([]byte("ng"))
			return
		}

		log.Infof("con num: %d, pub data: %v", srv.ConnNum(), model)

		srv.Conns().Range(func(key, val interface{}) bool {
			conn := val.(*tcp.Conn)
			log.Infof("con: %v, mode: %v", conn.SN, model.Sn)
			if conn.SN == model.Sn {
				conn.AsyncWritePacket(NewPacket(C_SET, data), time.Second)
				return false // 找到并处理后停止遍历
			}
			return true // 继续遍历
		})

		rw.Write([]byte("ok"))
	})

	http.HandleFunc("/rpc/bin", func(rw http.ResponseWriter, rq *http.Request) {
		if rq.Body == nil {
			rw.Write([]byte("ng"))
		}

		var model ReqModel
		if err := json.NewDecoder(rq.Body).Decode(&model); err != nil {
			log.Errorf("pub invoke error: %s", err.Error())
			return
		}
		data, err := hex.DecodeString(model.Data)
		if err != nil || len(data) < 1 {
			rw.Write([]byte("ng"))
			return
		}

		log.Infof("bin > con num: %d, pub data: %v", srv.ConnNum(), model)

		srv.Conns().Range(func(key, val interface{}) bool {
			conn := val.(*tcp.Conn)
			if conn.SN == model.Sn {
				conn.AsyncWritePacket(NewPacket(C_BIN, data), time.Second)
				return false // 找到并处理后停止遍历
			}
			return true // 继续遍历
		})

		rw.Write([]byte("ok"))
	})

	http.HandleFunc("/rpc/upgrade", func(rw http.ResponseWriter, rq *http.Request) {
		if rq.Body == nil {
			rw.Write([]byte("ng"))
		}

		var model ReqModel
		if err := json.NewDecoder(rq.Body).Decode(&model); err != nil {
			log.Errorf("pub invoke error: %s", err.Error())
			return
		}

		log.Infof("upgrade > con num: %d, pub data: %v", srv.ConnNum(), model)

		srv.Conns().Range(func(key, val interface{}) bool {
			conn := val.(*tcp.Conn)
			if conn.SN == model.Sn {
				log.Infof("@@upgrade > con num: %d, pub data: %v", srv.ConnNum(), model)
				conn.AsyncWritePacket(NewPacket(C_UPD, []byte{}), time.Second)
				return false // 找到并处理后停止遍历
			}
			return true // 继续遍历
		})

		rw.Write([]byte("ok"))
	})

	http.HandleFunc("/rpc/online", func(rw http.ResponseWriter, rq *http.Request) {
		out := make([]string, 0)
		srv.Conns().Range(func(key, val interface{}) bool {
			conn := val.(*tcp.Conn)
			out = append(out, fmt.Sprintf("id: %d, sn: %s, addr: %s", key, conn.SN, conn.Addr()))
			return true // 继续遍历显示所有
		})
		rw.Write([]byte(strings.Join(out, "\n")))
	})

	// start service
	addr := viper.GetString("http.url")
	go http.ListenAndServe(addr, nil)
	log.Infof("pub http service: %s\n", addr)
}
