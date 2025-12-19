package main

import (
	"fmt"
	"net"
	"time"
)

func main() {
	fmt.Println("mock device client start")
	
	// 连接到Go服务器
	conn, err := net.Dial("tcp", "127.0.0.1:2024")
	if err != nil {
		fmt.Printf("connect error: %v\n", err)
		return
	}
	defer conn.Close()
	
	fmt.Println("connected to server")
	
	// 发送心跳
	heartbeat := []byte{0xAA, 0x00, 0x0E, 0x01, 0x32, 0x30, 0x42, 0x33, 0x36, 0x32, 0x35, 0x34, 0x1D, 0xBE, 0x55}
	send(conn, heartbeat)
	fmt.Printf("send: %x\n", heartbeat)
	
	// 保持连接活跃
	go func() {
		ticker := time.NewTicker(10 * time.Second)
		defer ticker.Stop()
		for {
			select {
			case <-ticker.C:
				send(conn, heartbeat)
				fmt.Println("heartbeat sent")
			}
		}
	}()
	
	fmt.Println("keeping connection alive...")
	
	// 接收数据
	buffer := make([]byte, 1024)
	for {
		n, err := conn.Read(buffer)
		if err != nil {
			fmt.Printf("read error: %v\n", err)
			break
		}
		
		data := buffer[:n]
		fmt.Printf("received: %x\n", data)
		
		// 处理心跳
		if len(data) == 15 && data[0] == 0xAA && data[3] == 0x01 {
			// 心跳响应
			send(conn, heartbeat)
			fmt.Println("heartbeat sent")
			continue
		}
		
		// 处理单个查询命令
		if len(data) >= 14 && data[0] == 0xAA && data[3] == 0x02 {
			fmt.Printf("DEBUG: Command matches basic criteria - len=%d, data[0]=0x%02x, data[3]=0x%02x\n", len(data), data[0], data[3])
			fmt.Printf("received command: %x, checking if it's single query...\n", data)
			fmt.Printf("data length: %d, data[7] = 0x%02x\n", len(data), data[7])
			
			// 检查内层数据是否包含E0（单个查询标志）
			if len(data) >= 8 && data[7] == 0xE0 {
				fmt.Println("found E0 flag, this is a single query command!")
				
				// 构造单个查询响应：AA 00 11 02 55 01 07 50 02 01 00 10 2E A1 01 00 55
				response := []byte{
					0xAA, 0x00, 0x11, 0x02, // 外层协议头
					0x55, 0x01, 0x07, 0x50, // 内层协议头
					0x02, 0x01, // 地址 513 (02 01)
					0x00, 0x10, // 值 16 (00 10)
					0x2E, 0xA1, // 内层CRC
					0x01, 0x00, // 外层CRC
					0x55, // 帧尾
				}
				
				time.Sleep(100 * time.Millisecond) // 模拟设备处理时间
				send(conn, response)
				fmt.Println("single query response sent")
			} else {
				fmt.Printf("E0 check failed: len=%d, data[7]=0x%02x, expected=0xE0\n", len(data), data[7])
			}
		} else {
			fmt.Printf("received: %x\n", data)
			// 调试：检查所有接收到的命令
			if len(data) >= 4 {
				fmt.Printf("DEBUG: All commands - len=%d, data[0]=0x%02x, data[3]=0x%02x\n", len(data), data[0], data[3])
			}
		}
	}
}

func send(conn net.Conn, data []byte) {
	_, err := conn.Write(data)
	if err != nil {
		fmt.Printf("send error: %v\n", err)
	}
}
