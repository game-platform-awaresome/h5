<?php
/**
 * 3DES ECB 加解密
 * 
 * @author ideadawn@gmail.com
 */

class F_Helper_Mcrypt
{
	/**
	 * 加密
	 * 
	 * @param string $data
	 * @param string $key min-length=24
	 * @return string
	 */
	static public function Encrypt($data, $key = null)
	{
		if( empty($key) ) {
			$key = Yaf_Registry::get('config')->mcrypt->key;
		}
		$key = substr($key, 0, mcrypt_get_key_size(MCRYPT_3DES, MCRYPT_MODE_ECB));
		$bs = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
		$pkcs7 = $bs - (strlen($data) % $bs);
		if( $pkcs7 < $bs  ) {
			$data .= str_repeat(chr($pkcs7), $pkcs7);
		}
		$cipher = mcrypt_encrypt(MCRYPT_3DES, $key, $data, MCRYPT_MODE_ECB);
		return rtrim(base64_encode($cipher), '=');
	}
	
	/**
	 * 解密
	 * 
	 * @param string $cipher
	 * @param string $key min-length=24
	 * @return string
	 */
	static public function Decrypt($cipher, $key = null)
	{
		if( empty($key) ) {
			$key = Yaf_Registry::get('config')->mcrypt->key;
		}
		$cipher = base64_decode(str_replace(' ', '+', rawurldecode($cipher)));
		$key = substr($key, 0, mcrypt_get_key_size(MCRYPT_3DES, MCRYPT_MODE_ECB));
		$data = mcrypt_decrypt(MCRYPT_3DES, $key, $cipher, MCRYPT_MODE_ECB);
		$bs = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
		$pkcs7 = ord($data[strlen($data)-1]);
		if( $pkcs7 < $bs ) {
			$data = substr($data, 0, -$pkcs7);
		}
		return $data;
	}
	
	/**
	 * Discuz 用户信息加解密函数
	 * 
	 * @param string $string
	 * @param string $operation
	 * @param string $key
	 * @param number $expiry
	 * @return string
	 */
	static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
	{
	    $ckey_length = 4;
	
	    $key = md5($key ? $key : Yaf_Registry::get('config')->mcrypt->key);
	    $keya = md5(substr($key, 0, 16));
	    $keyb = md5(substr($key, 16, 16));
	    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	
	    $cryptkey = $keya.md5($keya.$keyc);
	    $key_length = strlen($cryptkey);
	
	    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	    $string_length = strlen($string);
	
	    $result = '';
	    $box = range(0, 255);
	
	    $rndkey = array();
	    for($i = 0; $i <= 255; $i++) {
	        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
	    }
	
	    for($j = $i = 0; $i < 256; $i++) {
	        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
	        $tmp = $box[$i];
	        $box[$i] = $box[$j];
	        $box[$j] = $tmp;
	    }
	
	    for($a = $j = $i = 0; $i < $string_length; $i++) {
	        $a = ($a + 1) % 256;
	        $j = ($j + $box[$a]) % 256;
	        $tmp = $box[$a];
	        $box[$a] = $box[$j];
	        $box[$j] = $tmp;
	        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	    }
	
	    if($operation == 'DECODE') {
	        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
	            return substr($result, 26);
	        } else {
	            return '';
	        }
	    } else {
	        return $keyc.str_replace('=', '', base64_encode($result));
	    }
	}
}
