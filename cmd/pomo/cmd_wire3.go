package main

import (
	"encoding/json"
	"net/http"
	"strconv"

	log "github.com/sirupsen/logrus"
)

// Wire3CommandHandler 处理wire3格式的命令请求
type Wire3CommandHandler struct {
	wire3Proto *Wire3Protocol
}

// NewWire3CommandHandler 创建新的命令处理器
func NewWire3CommandHandler() *Wire3CommandHandler {
	return &Wire3CommandHandler{
		wire3Proto: NewWire3Protocol(),
	}
}

// CommandRequest wire3命令请求结构
type CommandRequest struct {
	SN     string                 `json:"sn"`     // 设备SN
	Mode   string                 `json:"mode"`   // 命令模式: run, stp, rst, pam, syn, query
	Params map[string]interface{} `json:"params"` // 命令参数
}

// CommandResponse 命令响应结构
type CommandResponse struct {
	Code    int    `json:"code"`
	Message string `json:"message"`
	Data    string `json:"data,omitempty"`
}

// HandleCommand 处理wire3命令请求
func (h *Wire3CommandHandler) HandleCommand(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req CommandRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.sendResponse(w, 400, "Invalid JSON", "")
		return
	}

	// 验证必要参数
	if req.SN == "" || req.Mode == "" {
		h.sendResponse(w, 400, "Missing required parameters", "")
		return
	}

	// 从SN中提取设备序列号
	deviceSerial, err := strconv.Atoi(req.SN)
	if err != nil {
		h.sendResponse(w, 400, "Invalid device serial number", "")
		return
	}

	// 生成命令
	cmd := h.wire3Proto.GenerateCommand(deviceSerial, req.Mode, req.Params)
	if cmd == "" {
		h.sendResponse(w, 400, "Unsupported command mode", "")
		return
	}

	// 发送命令到设备
	if err := h.sendToDevice(req.SN, cmd); err != nil {
		h.sendResponse(w, 500, "Failed to send command", "")
		return
	}

	log.Infof("wire3 command sent: sn=%s, mode=%s, cmd=%s", req.SN, req.Mode, cmd)
	h.sendResponse(w, 0, "Command sent successfully", cmd)
}

// sendToDevice 发送命令到设备
func (h *Wire3CommandHandler) sendToDevice(sn, cmd string) error {
	// 这里应该通过pub接口发送命令
	// 暂时返回nil，实际实现需要调用pub服务
	return nil
}

// sendResponse 发送HTTP响应
func (h *Wire3CommandHandler) sendResponse(w http.ResponseWriter, code int, message, data string) {
	w.Header().Set("Content-Type", "application/json")
	response := CommandResponse{
		Code:    code,
		Message: message,
		Data:    data,
	}
	json.NewEncoder(w).Encode(response)
}

// RegisterWire3Routes 注册wire3相关的HTTP路由
func RegisterWire3Routes() {
	handler := NewWire3CommandHandler()
	
	// 注册wire3命令接口
	http.HandleFunc("/rpc/wire3/cmd", handler.HandleCommand)
	
	log.Info("wire3 routes registered")
}
