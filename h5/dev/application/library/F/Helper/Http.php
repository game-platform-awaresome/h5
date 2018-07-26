<?php
/**
 * @Author ideadawn@gmail.com
 * @Date 2010.09
 */

class F_Helper_Http
{
	const Return_Header = 1;
	const Return_Body = 2;
	const Return_All = 3;

	static public function request($url, $method = 'get', $data = '', $timeout = 5, $cookie = null, $return = self::Return_Body)
	{
		$url = parse_url($url);
		$host = $url['host'];
		$port = isset($url['port']) ? $url['port'] : 80;
		$path = isset($url['path']) ? $url['path'] : '/';
		$path .= isset($url['query']) ? '?'.$url['query'] : '';
		if( $cookie === '' && $_COOKIE ) {
			$cmm = '';
			foreach( $_COOKIE as $k=>$v )
			{
				$cookie .= "{$cmm}{$k}={$v}";
				$cmm = '; ';
			}
		}
		
		if( strtolower($method) == 'post' ) {
			if( is_array($data) ) {
				$post = '';
				$cmm = '';
				foreach ($data as $k=>$v)
				{
					$post .= "{$cmm}{$k}={$v}";
					$cmm = '&';
				}
			} else {
				$post = $data;
			}
			$write = "POST $path HTTP/1.1\r\n";
			$write .= "Host: $host\r\n";
			$write .= "Accept: */*\r\n";
			$write .= "Accept-Language: zh-cn\r\n";
			$write .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$write .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
			$write .= "Content-Length: ".strlen($post)."\r\n";
			$write .= "Connection: Close\r\n";
			$write .= "Cache-Control: no-cache\r\n";
			if( $cookie ) {
				$write .= "Cookie: $cookie\r\n";
			}
			$write .= "\r\n";
			$write .= $post;
		} else {
			if( $data ) {
				$cmm = '';
				if( strpos($path, '?') === false ) {
					$cmm = '?';
				} elseif( substr($path, -1) != '&' ) {
					$cmm = '&';
				}
				if( is_array($data) ) {
					foreach ($data as $k=>$v)
					{
						$path .= "{$cmm}{$k}={$v}";
						$cmm = '&';
					}
				} else {
					$path .= $cmm.trim($data,'&');
				}
			}
			$write = "GET $path HTTP/1.1\r\n";
			$write .= "Host: $host\r\n";
			$write .= "Accept: */*\r\n";
			$write .= "Accept-Language: zh-cn\r\n";
			$write .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
			$write .= "Connection: Close\r\n";
			if( $cookie ) {
				$write .= "Cookie: $cookie\r\n";
			}
			$write .= "\r\n";
		}
		
		return self::getFromSocket($write, $host, $port, $timeout, $return);
	}
	
	static public function getFromSocket($data, $host, $port = 80, $timeout = 5, $return = 3)
	{
		$fp = fsockopen($host, $port, $errno, $errstr, $timeout);
		if( ! $fp ) {
			return '';
		}
		stream_set_blocking($fp, 1);
		stream_set_timeout($fp, $timeout);
		fwrite($fp, $data);
		$status = stream_get_meta_data($fp);
		$result = '';
		if( ! $status['timed_out'] ) {
		    $chunked = false;
			$headers = '';
			while( ! feof($fp) )
			{
				$header = fgets($fp);
				$headers .= $header;
				if( stripos($header, 'Transfer-Encoding') !== false && stripos($header, 'chunked') !== false ) {
				    $chunked = true;
				}
				if( $header && ($header == "\r\n" || $header == "\n") ) {
					break;
				}
			}
			if( $return == self::Return_Header ) {
				fclose($fp);
				return $headers;
			}
			while( ! feof($fp) )
			{
			    if( $chunked ) {
			        $len = fgets($fp, 32);
		            $len = hexdec($len);
		            $sonstr = fgets($fp, $len+2);
		            $result .= substr($sonstr, 0, $len);
			    } else {
			        $result .= fread($fp, 8192);
			    }
			}
		}
		fclose($fp);
		if( $return == self::Return_All ) {
			return $headers.$result;
		}
		return $result;
	}
}
