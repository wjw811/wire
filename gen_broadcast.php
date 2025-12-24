<?php
function crc16xmodem($data) {
    $crc = 0x0000;
    $len = strlen($data);
    for ($i = 0; $i < $len; $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
        }
    }
    return $crc & 0xFFFF;
}

$data = hex2bin('aa000541564b');
printf("c1_0: %s%04x\n", bin2hex($data), crc16xmodem($data));

