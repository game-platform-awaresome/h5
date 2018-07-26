<?php
/**
 * xxtea 加解密
 * 
 * @copyright http://coolcode.org/?action=show&id=128
 */

class F_Helper_Xxtea
{
	/**
	 * 加密
	 * 
	 * @param $data 原始字符串
	 * @param $key 密钥
	 * @return	string
	 */
	static public function Encrypt($data, $key = '')
	{
	    if( empty($data) ) {
	        return '';
	    }
	    
	    if( empty($key) ) {
	        $key = Yaf_Registry::get('config')->mcrypt->key;
	    }
	    $v = self::str2long($data, true);
	    $k = self::str2long($key, false);
	    if( empty($v) || empty($k) ) {
	        return '';
	    }
	    $len = count($k);
	    if ($len < 4) {
	        for ($i = $len; $i < 4; $i++) {
	            $k[$i] = 0;
	        }
	    }
	    
	    $n = count($v) - 1;
	    $z = $v[$n];
	    $y = $v[0];
	    $delta = 0x9E3779B9;
	    $q = floor(6 + 52 / ($n + 1));
	    
        $sum = 0;
        while (0 < $q--) {
            $sum = self::int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = self::int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = self::int32($v[$n] + $mx);
        }
        return base64_encode(self::long2str($v, false));
	}
	
	/**
	 * 解密
	 *
	 * @param $data 加密字符串
	 * @param $key 密钥
	 * @return	string
	 */
	static public function Decrypt($data, $key = '')
	{
	    if( empty($data) ) {
	        return '';
	    }
	    
	    if( empty($key) ) {
	        $key = Yaf_Registry::get('config')->mcrypt->key;
	    }
	    $data = base64_decode($data);
	    $v = self::str2long($data, false);
	    $k = self::str2long($key, false);
	    if (empty($v) || empty($k)) {
	        return '';
	    }
	    $len = count($k);
	    if ($len < 4) {
	        for ($i = $len; $i < 4; $i++) {
	            $k[$i] = 0;
	        }
	    }
	
	    $n = count($v) - 1;
	    $z = $v[$n];
	    $y = $v[0];
	    $delta = 0x9E3779B9;
	    $q = floor(6 + 52 / ($n + 1));
	    
        $sum = self::int32($q * $delta);
        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z = $v[$p - 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y = $v[$p] = self::int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[0] = self::int32($v[0] - $mx);
            $sum = self::int32($sum - $delta);
        }
        return self::long2str($v, true);
	}
	
	static private function long2str($v, $w)
	{
	    $len = count($v);
	    $n = ($len - 1) << 2;
	    if ($w) {
	        $m = $v[$len - 1];
	        if (($m < $n - 3) || ($m > $n)) {
	            return false;
	        }
	        $n = $m;
	    }
	    $s = array();
	    for ($i = 0; $i < $len; $i++) {
	        $s[$i] = pack("V", $v[$i]);
	    }
	    return $w ? substr(implode('', $s), 0, $n) : implode('', $s);
	}
	
	static private function str2long($s, $w)
	{
	    $v = unpack("V*", $s. str_repeat("\0", (4 - strlen($s) % 4) & 3));
	    $v = array_values($v);
	    if ($w) {
	        $v[count($v)] = strlen($s);
	    }
	    return $v;
	}
	
	static private function int32($n)
	{
	    while ($n >= 2147483648) $n -= 4294967296;
	    while ($n <= -2147483649) $n += 4294967296;
	    return (int)$n;
	}
}
