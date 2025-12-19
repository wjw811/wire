package main

import (
	"bytes"
	"encoding/json"
	"io/ioutil"
	"net/http"

	log "github.com/sirupsen/logrus"
	"github.com/spf13/viper"
)

func checkError(err error) {
	if err != nil {
		log.Fatal(err)
	}
}

func notify(uri string, data interface{}) {
	client := &http.Client{}

    base := viper.GetString("notify")
    // 强制直连 8000
    base = "http://127.0.0.1:8000/rpc/dev"
    url := base + uri
    log.Infof("notify base=%s url=%s", base, url)
	body, _ := json.Marshal(data)
    // 实时打印上报地址与数据
    log.Infof("notify url=%s payload=%s", url, string(body))
	req, err := http.NewRequest("POST", url, bytes.NewReader(body))
	if err != nil {
		log.Errorf("notify error url: %s, err: %s", url, err.Error())
	}

	req.Header.Set("User-Agent", "pomo dev 2.1")
	req.Header.Set("Cache-Control", "max-age=0")
	req.Header.Set("Content-Type", "application/json")

	resp, err := client.Do(req)
	if err != nil {
		log.Errorf("notify error url: %s, err: %s", url, err.Error())
		return
	}
	defer resp.Body.Close()

	body, _ = ioutil.ReadAll(resp.Body)
	log.Infof("notify success url: %s, resp: %s", url, string(body))
}
