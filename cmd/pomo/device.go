package main

import (
	"encoding/hex"
	"fmt"
	"sync"
	"time"

	//"github.com/spf13/viper"

	tcp "pomo/internal/tcp"

	log "github.com/sirupsen/logrus"
)

var (
	// waitingSingleQueries 跟踪等待的单个查询请求
	waitingSingleQueries = make(map[string]map[string]interface{})
	waitingMu            sync.RWMutex
)

// RecordWaitingSingleQuery 记录等待响应的查询
func RecordWaitingSingleQuery(sn string, addr int) {
	waitingMu.Lock()
	defer waitingMu.Unlock()
	waitingSingleQueries[sn] = map[string]interface{}{
		"addr": addr,
		"time": time.Now(),
	}
	log.Infof("recorded waiting single query: sn=%s, addr=%d", sn, addr)
}

type Device struct {
	wire3Proto *Wire3Protocol
}

func NewDevice() *Device {
	return &Device{
		wire3Proto: NewWire3Protocol(),
	}
}

func (d *Device) Do(c *tcp.Conn, p *Packet) {
    log.Infof("recv cmd=0x%02x len=%d", p.Cmd(), len(p.Body()))
    
    // 处理单个查询响应：AA 00 11 02 55 01 07 50 02 [addr] [val] [CRC] 55
    // 检查外层协议中是否包含 55 01 07 50 02 模式
    // ===== 重要提醒：此协议已修复完成，请勿修改 =====
    // 用户明确要求："好的现在修复好的协议请千万不要修改"
    // 此协议格式已验证有效，修改可能导致单个查询响应识别失效
    // 如需修改，请先与用户确认
    // ================================================
    rawData := p.Serialize()
    if len(rawData) >= 9 && rawData[0] == 0xAA && rawData[3] == 0x02 && rawData[4] == 0x55 && rawData[5] == 0x01 && rawData[6] == 0x07 && rawData[7] == 0x50 && rawData[8] == 0x02 {
        // 提取内层数据：55 01 07 50 02 [addr] [val] [CRC]
        innerData := rawData[4:len(rawData)-1] // 去掉外层协议的AA、长度、命令和最后的55
        rawPacket := &Packet{buff: innerData}
        d.SingleQueryResponse(c, rawPacket)
        return // 单个查询响应处理完毕，直接返回
    }

    // 处理批量查询响应：55 [addr] [len] 52 [data] [CRC]
    // 检查内层数据是否以 55 [addr] [len] 52 开头（第3字节是 0x52）
    // 必须在switch之前检查，否则会被当作普通数据帧处理
    if p.Cmd() == 0x02 && len(p.Body()) >= 4 {
        body := p.Body()
        if body[0] == 0x55 && body[3] == 0x52 {
            // 直接使用内层数据，不创建新的Packet
            log.Infof("batch query response detected for addr=%d, len=%d, processing...", body[1], body[2])
            d.BatchQueryResponse(c, body)
            return // 批量查询响应处理完毕，直接返回
        }
    }
    
	switch p.Cmd() {
	case C_HRT:
		d.Auth(c, p)
    case C_DA1:
		d.Data(c, p)
	case C_BIN:
		d.Bin(c, p)
	}

    // 兼容：部分设备命令高位带通道标记，如 0x82 表示数据帧
    // 如果低4位等于 C_DA1，也当作数据帧处理
    if p.Cmd()&0x0F == C_DA1 && p.Cmd() != C_DA1 {
        log.Infof("compat DA1 hit, cmd=0x%02x", p.Cmd())
        d.Data(c, p)
    }

    // 兜底：除心跳外，所有未知命令也按数据帧上报，方便抓取真实设备协议
    if p.Cmd() != C_HRT && p.Cmd() != C_DA1 && (p.Cmd()&0x0F) != C_DA1 && p.Cmd() != C_BIN && p.Cmd() != 0x55 {
        log.Infof("fallback report any cmd=0x%02x len=%d", p.Cmd(), len(p.Body()))
        d.Data(c, p)
    }
}

func (d *Device) Push(c *tcp.Conn, p *Packet) {
	c.AsyncWritePacket(p, time.Second)

	fd := c.Addr()
	log.Infof("%s -\033[32;1m Send\033[0m %x\n", fd, p.buff)
	
	// 检测单个查询命令并记录等待状态
	rawData := p.Serialize()
	if len(rawData) >= 9 && rawData[0] == 0xAA && rawData[3] == 0x02 {
		// 检查是否是单个查询命令：AA [size] 02 [data] [serial] [crc] 55
		// 单个查询命令的数据部分有两种形态：
		// 1) 旧：直接 E0 开头（极少用）
		// 2) wire3：内层以 AA 开头，格式 AA [dev] 05 E0 [addr_hi][addr_lo] [crc16]
		if len(rawData) >= 8 && rawData[4] == 0xE0 {
			// 解析地址：E0 [addr_hi] [addr_lo]
			if len(rawData) >= 7 {
				addr := int(rawData[5])<<8 | int(rawData[6])
				log.Infof("detected single query command (E0-direct): addr=%d", addr)
				RecordWaitingSingleQuery(c.SN, addr)
			}
		} else if len(rawData) >= 10 && rawData[4] == 0xAA && rawData[7] == 0xE0 {
			// 解析地址：AA [dev] 05 E0 [addr_hi] [addr_lo] ...
			addr := int(rawData[8])<<8 | int(rawData[9])
			log.Infof("detected single query command (wire3): addr=%d", addr)
			RecordWaitingSingleQuery(c.SN, addr)
		} else if len(rawData) >= 8 {
			log.Infof("packet cmd=0x02 but not query marker, found=0x%02x", rawData[4])
		}
	}
}

// -----------------
// business method
// -----------------
func (d *Device) Auth(c *tcp.Conn, p *Packet) {
	b := p.Body()
	if len(b) != 8 {
		return
	}
	if c.SN == "" {
		c.SN = hex.EncodeToString(b[0:8])
	}

	data := map[string]interface{}{
		"sn":   c.SN,
		"data": hex.EncodeToString(b),
	}
	go notify("/auth", data)
	d.Push(c, p)

    // polling - 检查是否有等待的单个查询请求
    time.Sleep(time.Second)

    // 检查是否有等待的单个查询
	waitingMu.RLock()
    waitingQuery, exists := waitingSingleQueries[c.SN]
	waitingMu.RUnlock()

    if exists {
        addr := waitingQuery["addr"].(int)
        deviceSerial := 1 // 默认使用设备序列号1

        // 使用wire3协议生成针对特定地址的查询命令
        queryCmd := d.wire3Proto.GenerateQueryCommand(deviceSerial, addr)
        if cmd, err := hex.DecodeString(queryCmd); err == nil {
            n := NewPacket(C_DA1, cmd)
            d.Push(c, n)
            log.Infof("wire3 generated query command: %s (deviceSerial=%d, addr=%d)", queryCmd, deviceSerial, addr)
        } else {
            log.Errorf("failed to decode query command: %s, error: %v", queryCmd, err)
        }

        // 清除已处理的等待查询
		waitingMu.Lock()
        delete(waitingSingleQueries, c.SN)
		waitingMu.Unlock()
    } else {
        // 如果没有等待的查询，优先按 data.yml 的 dev.k{SN}.proto 下发预置协议
        // 例如：c1_1 => AA010541564B0615（用户提供的 AVK 查询）
        protoKeys := []string{}
        if kv != nil {
            protoKeys = kv.GetStringSlice(fmt.Sprintf("dev.k%s.proto", c.SN))
        }

        if len(protoKeys) > 0 && kv != nil {
            for _, v := range protoKeys {
                hexStr := kv.GetString(fmt.Sprintf("proto.%s", v))
                cmd, err := hex.DecodeString(hexStr)
                if err != nil || len(cmd) == 0 {
                    log.Errorf("failed to decode configured proto: key=%s hex=%s err=%v", v, hexStr, err)
                    continue
                }
                n := NewPacket(C_DA1, cmd)
                d.Push(c, n)
                log.Infof("sent configured proto: sn=%s key=%s hex=%s", c.SN, v, hexStr)
                time.Sleep(time.Millisecond * 100)
            }
            return
        }

        // fallback：历史默认逻辑（地址查询），避免没有配置时完全不发
        deviceSerial := 1
        queryCmd := d.wire3Proto.GenerateQueryCommand(deviceSerial, 0x4156) // 默认地址
        if cmd, err := hex.DecodeString(queryCmd); err == nil {
            n := NewPacket(C_DA1, cmd)
            d.Push(c, n)
            log.Infof("wire3 generated default query command: %s (deviceSerial=%d)", queryCmd, deviceSerial)
        } else {
            log.Errorf("failed to decode query command: %s, error: %v", queryCmd, err)
        }
    }
    
    // 注释：如果网关下有多个设备需要查询，可以在Redis配置中指定协议命令
    // proto := kv.GetStringSlice(fmt.Sprintf("dev.k%s.proto", c.SN))
    // if len(proto) > 0 {
    //     for _, v := range proto {
    //         cmd, err := hex.DecodeString(kv.GetString(fmt.Sprintf("proto.%s", v)))
    //         if len(cmd) > 0 && err == nil {
    //             n := NewPacket(C_DA1, cmd)
    //             d.Push(c, n)
    //             time.Sleep(time.Millisecond * 100)
    //         }
    //     }
    // }
}

func (d *Device) Data(c *tcp.Conn, p *Packet) {
    // 模拟设备发送到设备号123，真实设备发送到设备号1
    devSerial := "1"
    if c.Addr() == "127.0.0.1" || c.Addr()[:9] == "127.0.0.1" {
        devSerial = "123" // 本机模拟设备发送到123
    }
    
    body := p.Body()

    // 如果有等待的单个查询，并且回包是CK（哪怕长度偏短），尝试从CK固定字段直接提取值，
    // 避免前端 sync 一直轮询超时（典型：addr=0x0010(16) 回包不是 55 01 07 50，而是短CK）
	waitingMu.RLock()
    waitingQuery, exists := waitingSingleQueries[c.SN]
	waitingMu.RUnlock()

    if exists && len(body) > 5 && body[0] == 0x55 && body[3] == 0x43 && body[4] == 0x4B {
        if addr, ok := waitingQuery["addr"].(int); ok {
            // 目前只覆盖CK里前18个16位字段（f01~f18），这些字段在短CK里也通常存在
            if addr >= 1 && addr <= 18 {
                off := 5 + (addr-1)*2
                if len(body) > off+1 {
                    val := d.wire3Proto.parseSigned16(body[off], body[off+1])
                    log.Infof("single query fallback from CK: sn=%s addr=%d -> f%02d=%d (off=%d)", c.SN, addr, addr, val, off)
					waitingMu.Lock()
                    delete(waitingSingleQueries, c.SN)
					waitingMu.Unlock()
                    singleQueryData := map[string]interface{}{
                        "sn":   c.SN,
                        "addr": addr,
                        "val":  val,
                        "mode": "P",
                    }
                    go notify("/single_query", singleQueryData)
                }
            }
        }
    }

    // 使用wire3协议解析数据
    hexData := hex.EncodeToString(body)
    parsedData := d.wire3Proto.DecodeData(hexData)
    
    // 构建上报数据
    data1 := map[string]interface{}{
        "sn":   c.SN,
        "dev":  devSerial,
        "cmd":  p.Cmd(),
        "data": hexData,
    }
    
    // 如果解析成功，添加解析后的数据
    if len(parsedData) > 0 {
        data1["parsed"] = parsedData
        // 额外打印 addr/val 便于排查参数帧
        if _, ok := parsedData["addr"]; ok {
            log.Infof("wire3 parsed data: mode=%v, sn=%v, addr=%v, val=%v", parsedData["mode"], parsedData["sn"], parsedData["addr"], parsedData["val"])
        } else {
            log.Infof("wire3 parsed data: mode=%v, sn=%v", parsedData["mode"], parsedData["sn"])
        }
    }
    
    log.Infof("notify /pomo sn=%s len=%d (dev=%s)", c.SN, len(p.Body()), devSerial)
    go notify("/pomo", data1)
    
    // 检查是否有单个查询请求等待响应
    // 如果设备发送了专门的单个查询响应（50命令），会在Do函数中直接处理
    // 如果设备没有发送，则从CK数据帧中提取
    if len(parsedData) > 0 && parsedData["mode"] == "CK" {
        // 检查是否有等待的单个查询请求
        waitingMu.RLock()
        waitingQuery, exists := waitingSingleQueries[c.SN]
        waitingMu.RUnlock()

        if exists {
            waitingMu.Lock()
            delete(waitingSingleQueries, c.SN) // 清除等待状态
            waitingMu.Unlock()
            
            // 从解析的数据中提取对应的值
            if addr, ok := waitingQuery["addr"].(int); ok {
                // 根据地址从数据帧中提取值
                var val int
                found := false
                switch addr {
                case 512: // 自启动
                    if f01, ok := parsedData["f01"].(int); ok {
                        val = f01
                        found = true
                    }
                case 513: // 补偿模式
                    if f51, ok := parsedData["f51"].(int); ok {
                        val = f51
                        found = true
                    }
                case 514: // 其他参数
                    if f02, ok := parsedData["f02"].(int); ok {
                        val = f02
                        found = true
                    }
                default:
                    // 尝试从通用字段中获取
                    if fVal, ok := parsedData[fmt.Sprintf("f%02d", addr)].(int); ok {
                        val = fVal
                        found = true
                    }
                }
                
                if found {
                    log.Infof("single query response from data frame: addr=%d, val=%d", addr, val)
                    
                    // 通知PHP后端单个查询响应
                    singleQueryData := map[string]interface{}{
                        "sn":   c.SN,
                        "addr": addr,
                        "val":  val,
                        "mode": "P",
                    }
                    go notify("/single_query", singleQueryData)
                }
            }
        }
    }
}

func (d *Device) Bin(c *tcp.Conn, p *Packet) {
	data := map[string]interface{}{
		"sn":   c.SN,
		"cmd":  p.Cmd(),
		"data": hex.EncodeToString(p.Body()),
	}
	log.Info(p.Body())
	go notify("/bin", data)
}

func (d *Device) BatchQueryResponse(c *tcp.Conn, body []byte) {
	// 处理批量查询响应：55 01 07 52 [data] [CRC]
	log.Infof("batch query response raw data: %x", body)
	if len(body) >= 7 { // 55 01 07 52 [data...] [CRC]
		// 解析批量查询响应数据
		// 格式：55 [dev_addr] 07 52 [val1_hi] [val1_lo] [val2_hi] [val2_lo] [CRC_hi] [CRC_lo]
		// 实际数据：5501075200000010b07a
		// 解析：55 01 07 52 00 00 00 10 b0 7a
		//      其中 01 是设备地址（设备序列号）
		
		// 提取设备序列号（body[1]是设备地址）
		deviceSerial := fmt.Sprintf("%d", body[1])
		
		// 解析数据值（每2字节一个参数值）
		var values []int
		for i := 4; i < len(body)-2; i += 2 {
			if i+1 < len(body)-2 {
				val := int(body[i])<<8 | int(body[i+1])
				values = append(values, val)
				log.Infof("batch query param %d: val=%d", len(values), val)
			}
		}
		
	// 构造响应数据
	responseData := map[string]interface{}{
		"sn":           c.SN,         // 网关序列号
		"deviceSerial": deviceSerial, // 设备地址
		"mode":         "batch_query",
		"values":       values,
		"count":        len(values),
	}
		
		// 通过HTTP通知PHP后端
		go notify("/batch_query", responseData)
		
		log.Infof("batch query response processed: sn=%s (gw) dev=%s values=%v", c.SN, deviceSerial, values)
	}
}

func (d *Device) SingleQueryResponse(c *tcp.Conn, p *Packet) {
	// 处理单个查询响应：55 01 07 50 02 [addr] [val] [CRC]
	log.Infof("single query response cmd=0x%02x len=%d", p.Cmd(), len(p.Body()))
	
	// body是内层数据：55 [dev] [len] 50 [addr_hi] [addr_lo] [val_hi] [val_lo] [crc] ...
	body := p.Serialize()
	if len(body) >= 8 { 
		// 解析地址和值
		// body[4] = 地址高字节, body[5] = 地址低字节
		// body[6] = 值高字节, body[7] = 值低字节
		addr := int(body[4])<<8 | int(body[5])
		val := int(body[6])<<8 | int(body[7])
		
		// 提取设备序列号（设备地址）
		deviceSerial := int(body[1])
		
		log.Infof("single query response: addr=0x%04X (%d), val=%d (原始值=0x%04X), deviceSerial=%d", addr, addr, val, val, deviceSerial)
		
		// 构造外层协议：AA [size] 02 [内层数据] [serial] [crc] 55
		innerData := body // 55 01 07 50 02 [addr] [val] [CRC]
		innerDataLen := len(innerData)
		totalSize := 7 + innerDataLen // AA + size(2) + cmd(1) + data + serial(1) + crc(1) + 55
		
		// 构造完整协议
		fullProtocol := make([]byte, 0, totalSize)
		fullProtocol = append(fullProtocol, 0xAA)                    // 帧头
		fullProtocol = append(fullProtocol, byte(totalSize>>8))       // 长度高字节
		fullProtocol = append(fullProtocol, byte(totalSize&0xff))     // 长度低字节
		fullProtocol = append(fullProtocol, 0x02)                    // 命令=数据透传1
		fullProtocol = append(fullProtocol, innerData...)             // 内层数据
		fullProtocol = append(fullProtocol, 0x07)                    // 序列号
		fullProtocol = append(fullProtocol, 0xBE)                    // CRC (需要计算)
		fullProtocol = append(fullProtocol, 0x55)                    // 帧尾
		
		log.Infof("single query full protocol: %x", fullProtocol)
		
		// 通知PHP后端处理单个查询响应
		notify("/single_query", map[string]interface{}{
			"sn":           c.SN,
			"deviceSerial": deviceSerial,
			"addr":         addr,
			"val":          val,
			"mode":         "P",
		})
		
		log.Infof("single query response notified to PHP: sn=%s addr=%d val=%d", c.SN, addr, val)
		
		// ===== 重要提醒：此协议已修复完成，请勿修改 =====
		// 用户明确要求："好的现在修复好的协议请千万不要修改"
		// 此协议格式已验证有效，修改可能导致单个查询响应处理失效
		// 如需修改，请先与用户确认
		// ================================================
	}
}
