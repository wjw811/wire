package main

import (
	"encoding/hex"
	"fmt"
	"strconv"
)

// Wire3Protocol 移植自wire3的协议处理逻辑
type Wire3Protocol struct{}

// NewWire3Protocol 创建新的协议处理器
func NewWire3Protocol() *Wire3Protocol {
	return &Wire3Protocol{}
}

// DecodeData 解析设备数据包，移植自wire3/app/model/Proto.php的decode方法
func (p *Wire3Protocol) DecodeData(hexData string) map[string]interface{} {
	result := make(map[string]interface{})
	
	// 将十六进制字符串转换为字节数组
	data, err := hex.DecodeString(hexData)
	if err != nil || len(data) < 4 {
		return result
	}
	
    // 检查是否为P模式（参数设置/查询响应）
    // 典型帧: aa 00 11 02 55 01 07 50 02 {addr_hi} {addr_lo} {val_hi} {val_lo} ... 55
    if len(data) >= 12 {
        // 寻找头部标记 55 01 07 50
        for i := 0; i <= len(data)-12; i++ {
            if data[i] == 0x55 && data[i+1] == 0x01 && data[i+2] == 0x07 && data[i+3] == 0x50 {
                // 解析设备序号、地址与数值
                sn := int(data[i+1])
                // i+4 固定为 0x02
                addr := int(data[i+5])<<8 | int(data[i+6])
                val := int(data[i+7])<<8 | int(data[i+8])

                result["sn"] = sn
                result["mode"] = "P"
                result["addr"] = addr
                result["val"] = val
                return result
            }
        }
    }
	
	// 检查是否为CK模式（数据上报）
	if len(data) > 4 && data[3] == 67 && data[4] == 75 { // 'C' 'K'
		if data[2] != 120 || len(data) < 120 {
			return result
		}
		
		result["sn"] = data[1]
		result["mode"] = "CK"
		
		// 解析各种数据字段，移植自wire3的decode方法
		result["f01"] = p.parseSigned16(data[5], data[6]) // 系统A相有功 单位0.01kW
		result["f02"] = p.parseSigned16(data[7], data[8]) // 系统A相无功 单位0.01kvar
		result["f03"] = p.parseSigned16(data[9], data[10]) // 系统A相功率因数 0.01
		
		result["f04"] = p.parseSigned16(data[11], data[12]) // 系统B相有功
		result["f05"] = p.parseSigned16(data[13], data[14]) // 系统B相无功
		result["f06"] = p.parseSigned16(data[15], data[16]) // 系统B相功率因数
		
		result["f07"] = p.parseSigned16(data[17], data[18]) // 系统C相有功
		result["f08"] = p.parseSigned16(data[19], data[20]) // 系统C相无功
		result["f09"] = p.parseSigned16(data[21], data[22]) // 系统C相功率因数
		
		result["f10"] = p.parseSigned16(data[23], data[24]) // 输出A相有功 单位0.01kW
		result["f11"] = p.parseSigned16(data[25], data[26]) // 输出A相无功 单位0.01kvar
		result["f12"] = p.parseSigned16(data[27], data[28]) // 输出A相功率因数 0.01
		
		result["f13"] = p.parseSigned16(data[29], data[30]) // 输出B相有功
		result["f14"] = p.parseSigned16(data[31], data[32]) // 输出B相无功
		result["f15"] = p.parseSigned16(data[33], data[34]) // 输出B相功率因数
		
		result["f16"] = p.parseSigned16(data[35], data[36]) // 输出C相有功
		result["f17"] = p.parseSigned16(data[37], data[38]) // 输出C相无功
		result["f18"] = p.parseSigned16(data[39], data[40]) // 输出C相功率因数
		
		result["f19"] = int(data[41])<<8 | int(data[42]) // FPGA版本号
		result["f20"] = data[43] // 状态
		result["f21"] = data[44] // 预留
		result["f22"] = int(data[45])<<8 | int(data[46]) // DSPB版本号
		
		result["f23"] = float64(int(data[47])<<8|int(data[48])) / 10 // A相电网电压 0.1V
		result["f24"] = float64(int(data[49])<<8|int(data[50])) / 10 // B相电网电压
		result["f25"] = float64(int(data[51])<<8|int(data[52])) / 10 // C相电网电压
		
		result["f26"] = int(data[53])<<8 | int(data[54]) // DSPA版本号
		result["f27"] = float64(int(data[59])<<8|int(data[60])) / 10 // 电网电流谐波畸变率最大值
		
		result["f28"] = float64(int(data[61])<<8|int(data[62])) / 100 // 电网频率 0.01Hz
		result["f29"] = p.parseSigned16(data[63], data[64]) / 10 // A相温度
		result["f30"] = float64(int(data[65])<<8|int(data[66])) / 10 // 电网电压谐波畸变率最大值
		result["f31"] = int(data[67])<<8 | int(data[68]) // 直流母线电压 1V
		result["f32"] = int(data[69])<<8 | int(data[70]) // 上分裂电容电压 1V
		result["f33"] = int(data[71])<<8 | int(data[72]) // 下分裂电容电压 1V
		result["f34"] = p.parseSigned16(data[73], data[74]) / 10 // B相温度 0.1℃
		result["f35"] = p.parseSigned16(data[75], data[76]) / 10 // C相温度
		result["f36"] = float64(int(data[77])<<8|int(data[78])) / 10 // 装置A相电流 0.1A
		result["f37"] = float64(int(data[79])<<8|int(data[80])) / 10 // 装置B相电流
		result["f38"] = float64(int(data[81])<<8|int(data[82])) / 10 // 装置C相电流
		
		result["f39"] = int(data[83])<<8 | int(data[84]) // A相负载电流 0.1A
		result["f40"] = int(data[85])<<8 | int(data[86]) // B相负载电流
		result["f41"] = int(data[87])<<8 | int(data[88]) // C相负载电流
		
		result["f42"] = int(data[89])<<8 | int(data[90]) // A相网侧电流 0.1A
		result["f43"] = int(data[91])<<8 | int(data[92]) // B相网侧电流
		result["f44"] = int(data[93])<<8 | int(data[94]) // C相网侧电流
		
		result["f45"] = float64(int(data[95])<<8|int(data[96])) / 10 // A相设备电流 0.1A
		result["f46"] = float64(int(data[97])<<8|int(data[98])) / 10 // B相设备电流
		result["f47"] = float64(int(data[99])<<8|int(data[100])) / 10 // C相设备电流
		
		// 故障状态字段
		if len(data) > 118 {
			result["f48"] = data[117] // 故障状态1
			result["f49"] = data[118] // 故障状态2
			result["f50"] = data[119] // 故障状态3
			result["f51"] = data[120] // 故障状态4
		}
	}
	
	return result
}

// parseSigned16 解析有符号16位整数，移植自wire3的b函数
func (p *Wire3Protocol) parseSigned16(high, low byte) float64 {
	val := int(high)<<8 | int(low)
	if val > 32767 {
		val -= 65536
	}
	return float64(val)
}

// GenerateCommand 生成命令帧，移植自wire3/app/control/admin/Dash.php的cmd方法
func (p *Wire3Protocol) GenerateCommand(deviceSerial int, mode string, params map[string]interface{}) string {
	switch mode {
	case "run":
		// 运行命令: aa{serial}0552554E (RUN)
		return fmt.Sprintf("aa%02x0552554E", deviceSerial)
	case "stp":
		// 停止命令: aa{serial}05535450 (STP)
		return fmt.Sprintf("aa%02x05535450", deviceSerial)
	case "rst":
		// 复位命令: aa{serial}05525354 (RST)
		return fmt.Sprintf("aa%02x05525354", deviceSerial)
	case "pam":
		// 参数设置: aa{serial}07E1{cmd}{val}
		if cmd, ok := params["cmd"].(int); ok {
			if val, ok := params["val"].(int); ok {
				return fmt.Sprintf("aa%02x07E1%02x%02x%02x%02x", 
					deviceSerial, cmd>>8, cmd&0xff, val>>8, val&0xff)
			}
		}
	case "syn":
		// 同步命令: aa{serial}05E0{cmd}
		if cmd, ok := params["cmd"].(int); ok {
			return fmt.Sprintf("aa%02x05E0%02x%02x", 
				deviceSerial, cmd>>8, cmd&0xff)
		}
	case "query":
		// 查询命令: aa{serial}0541564B (AVK)
		return fmt.Sprintf("aa%02x0541564B", deviceSerial)
	}
	return ""
}

// CRC16XMODEM 计算CRC16校验，移植自wire3的crc16xmodem函数
func (p *Wire3Protocol) CRC16XMODEM(data string) string {
	// CRC16 XMODEM查表算法，移植自Next/Helper/Common.php的crc16xmodem方法
	table := []uint16{
		0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50A5,
		0x60C6, 0x70E7, 0x8108, 0x9129, 0xA14A, 0xB16B,
		0xC18C, 0xD1AD, 0xE1CE, 0xF1EF, 0x1231, 0x0210,
		0x3273, 0x2252, 0x52B5, 0x4294, 0x72F7, 0x62D6,
		0x9339, 0x8318, 0xB37B, 0xA35A, 0xD3BD, 0xC39C,
		0xF3FF, 0xE3DE, 0x2462, 0x3443, 0x0420, 0x1401,
		0x64E6, 0x74C7, 0x44A4, 0x5485, 0xA56A, 0xB54B,
		0x8528, 0x9509, 0xE5EE, 0xF5CF, 0xC5AC, 0xD58D,
		0x3653, 0x2672, 0x1611, 0x0630, 0x76D7, 0x66F6,
		0x5695, 0x46B4, 0xB75B, 0xA77A, 0x9719, 0x8738,
		0xF7DF, 0xE7FE, 0xD79D, 0xC7BC, 0x48C4, 0x58E5,
		0x6886, 0x78A7, 0x0840, 0x1861, 0x2802, 0x3823,
		0xC9CC, 0xD9ED, 0xE98E, 0xF9AF, 0x8948, 0x9969,
		0xA90A, 0xB92B, 0x5AF5, 0x4AD4, 0x7AB7, 0x6A96,
		0x1A71, 0x0A50, 0x3A33, 0x2A12, 0xDBFD, 0xCBDC,
		0xFBBF, 0xEB9E, 0x9B79, 0x8B58, 0xBB3B, 0xAB1A,
		0x6CA6, 0x7C87, 0x4CE4, 0x5CC5, 0x2C22, 0x3C03,
		0x0C60, 0x1C41, 0xEDAE, 0xFD8F, 0xCDEC, 0xDDCD,
		0xAD2A, 0xBD0B, 0x8D68, 0x9D49, 0x7E97, 0x6EB6,
		0x5ED5, 0x4EF4, 0x3E13, 0x2E32, 0x1E51, 0x0E70,
		0xFF9F, 0xEFBE, 0xDFDD, 0xCFFC, 0xBF1B, 0xAF3A,
		0x9F59, 0x8F78, 0x9188, 0x81A9, 0xB1CA, 0xA1EB,
		0xD10C, 0xC12D, 0xF14E, 0xE16F, 0x1080, 0x00A1,
		0x30C2, 0x20E3, 0x5004, 0x4025, 0x7046, 0x6067,
		0x83B9, 0x9398, 0xA3FB, 0xB3DA, 0xC33D, 0xD31C,
		0xE37F, 0xF35E, 0x02B1, 0x1290, 0x22F3, 0x32D2,
		0x4235, 0x5214, 0x6277, 0x7256, 0xB5EA, 0xA5CB,
		0x95A8, 0x8589, 0xF56E, 0xE54F, 0xD52C, 0xC50D,
		0x34E2, 0x24C3, 0x14A0, 0x0481, 0x7466, 0x6447,
		0x5424, 0x4405, 0xA7DB, 0xB7FA, 0x8799, 0x97B8,
		0xE75F, 0xF77E, 0xC71D, 0xD73C, 0x26D3, 0x36F2,
		0x0691, 0x16B0, 0x6657, 0x7676, 0x4615, 0x5634,
		0xD94C, 0xC96D, 0xF90E, 0xE92F, 0x99C8, 0x89E9,
		0xB98A, 0xA9AB, 0x5844, 0x4865, 0x7806, 0x6827,
		0x18C0, 0x08E1, 0x3882, 0x28A3, 0xCB7D, 0xDB5C,
		0xEB3F, 0xFB1E, 0x8BF9, 0x9BD8, 0xABBB, 0xBB9A,
		0x4A75, 0x5A54, 0x6A37, 0x7A16, 0x0AF1, 0x1AD0,
		0x2AB3, 0x3A92, 0xFD2E, 0xED0F, 0xDD6C, 0xCD4D,
		0xBDAA, 0xAD8B, 0x9DE8, 0x8DC9, 0x7C26, 0x6C07,
		0x5C64, 0x4C45, 0x3CA2, 0x2C83, 0x1CE0, 0x0CC1,
		0xEF1F, 0xFF3E, 0xCF5D, 0xDF7C, 0xAF9B, 0xBFBA,
		0x8FD9, 0x9FF8, 0x6E17, 0x7E36, 0x4E55, 0x5E74,
		0x2E93, 0x3EB2, 0x0ED1, 0x1EF0,
	}
	
	crc := uint16(0x0000)
	// 将十六进制字符串转换为字节进行处理
	if len(data)%2 == 0 {
		for i := 0; i < len(data); i += 2 {
			b, err := strconv.ParseUint(data[i:i+2], 16, 8)
			if err != nil {
				return ""
			}
			crc = table[((crc>>8)^uint16(b))&0xFF] ^ (crc << 8)
		}
	}
	crc = (crc ^ 0x0000) & 0xFFFF
	return fmt.Sprintf("%02X%02X", (crc>>8)&0xFF, crc&0xFF)
}

// GenerateQueryCommand 生成查询命令，移植自wire3/app/model/Proto.php的make方法
func (p *Wire3Protocol) GenerateQueryCommand(deviceSerial int, addr int) string {
	// 生成查询命令: aa{serial}05{addr_hi}{addr_lo} + CRC16
	cmd := fmt.Sprintf("aa%02x05%04x", deviceSerial, addr)
	return cmd + p.CRC16XMODEM(cmd)
}
