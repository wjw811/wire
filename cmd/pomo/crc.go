// Copyright 2020 Fred<fred@api4.me>. All Rights Reserved.
// Use of this source code is governed by a MIT style
// license that can be found in the LICENSE file.
package main

func crc(buff []byte) byte {
	v := byte(0x00)
	for i := 0; i < len(buff); i++ {
		v = v + buff[i]
	}

	return v & 0xff
}
