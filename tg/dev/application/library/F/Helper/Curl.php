<?php
/**
 * @Author ideadawn@gmail.com
 * @Date 2010.05
 */

class F_Helper_Curl
{
	/**
	 * cURL句柄。
	 */
	protected $_ch;
	
	/**
	 * 保存Cookie的文件路径。
	 */
	protected $_cookie_file;
	/**
	 * HTTP状态码
	 */
	protected $_http_code;
	
	/**
	 * 初始化cURL使其成为长链接。
	 */
	public function init()
	{
		if( ! $this->_ch ) {
			$this->_ch = curl_init();
		}
		return $this->_ch;
	}
	
	/**
	 * 关闭cURL并使长链接失效。
	 */
	public function close()
	{
		if( $this->_ch ) {
			curl_close($this->_ch);
		}
		$this->_ch = null;
	}
	
	/**
	 * 设置或获取保存Cookie的文件。
	 * 
	 * @param string|null $file
	 * @return void|string
	 */
	public function cookieFile($file = null)
	{
		if( $file === null ) {
			return $this->_cookie_file;
		} else {
			$this->_cookie_file = $file;
		}
	}
	
	/**
	 * 清空Cookie文件。
	 */
	public function clearCookie()
	{
		if( $this->_cookie_file && file_exists($this->_cookie_file) ) {
			file_put_contents($this->_cookie_file, '');
		}
	}
	
	/**
	 * 执行请求。
	 *
	 * @param string $url
	 * @param mixed $data
	 * @param int $timeout
	 * @param string $referer
	 * @param mixed $cookie
	 * @param resource $fp file_pointer
	 * @param string $agent userAgent
	 * @return mixed $return
	 */
	public function request($url, $data = null, $timeout = 5, $referer = null, $cookie = null, $fp = null, $agent = null)
	{
		$once = empty($this->_ch);
		if( $once ) {
			$ch = curl_init($url);
		} else {
			curl_setopt($this->_ch, CURLOPT_URL, $url);
			$ch = $this->_ch;
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout*2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
		$header = array();
		if( $agent ) {
			$header[] = 'User-Agent: '.$agent;
		} else {
			$header[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0';
		}
		$header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$header[] = 'Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3';
		$header[] = 'Accept-Encoding: gzip, deflate';
		if( $referer ) {
			$header[] = "Referer: {$referer}";
		}
		if( $cookie ) {
			if( is_array($cookie) ) {
				$ck_str = '';
				$ck_cmm = '';
				foreach ($cookie as $ck_k=>$ck_v)
				{
					$ck_str .= "{$ck_cmm}{$ck_k}={$ck_v}";
					$ck_cmm = '; ';
				}
				$header[] = "Cookie: {$ck_str}";
			} else {
				$header[] = "Cookie: {$cookie}";
			}
		} elseif( $this->_cookie_file ) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_cookie_file);
		}
		if( ! $once ) {
			$header[] = 'Connection: keep-alive';
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		if( $fp ) {
			curl_setopt($ch, CURLOPT_FILE, $fp);
		} else {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		}
		if( $data ) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		$return = curl_exec($ch);
		$this->_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if( $once ) {
			curl_close($ch);
		}
		return $return;
	}
	
	/**
	 * 获取上一个HTTP请求返回的状态码。
	 */
	public function httpCode()
	{
		return $this->_http_code;
	}
	
	/**
	 * 下载单个文件
	 * 
	 * @param string $url 下载地址
	 * @param string $file 文件或目录绝对路径
	 * @param string $path 文件保存地址
	 * @param array $rs 执行结果
	 */
	static public function download($url, $file)
	{
		$rs = array(false, '');
		$file = rtrim($file, '/\\');
		if( is_dir($file) ) {
			$fn = basename($url);
			if( ! preg_match('/\.[a-zA-Z]{2,4}$/', $fn) ) {
				$fn = md5($url);
			}
			$file .= "/{$fn}";
		}
		$fp = fopen($file, 'wb');
		if( ! is_resource($fp) ) {
			$rs[1] = "文件创建失败，{$file}。";
			return $rs;
		}
		
		preg_match('#(^.*?(?<!/))/(?!/)#', $url, $ref);
		if( $ref ) {
			$ref = $ref[1];
		} else {
			$ref = '';
		}
			
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0");
		curl_setopt($ch, CURLOPT_REFERER, $ref);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$rs[0] = curl_exec($ch);
		$rs[1] = $rs[0] ? $file : '文件下载失败。';
		return $rs;
	}
	
	/**
	 * 文件批量下载
	 * 
	 * @param array $url 下载地址
	 * @param string $dir 目录绝对路径
	 * @return mixed $file 文件相对路径，或错误信息
	 */
	static public function multiDownload($url, $dir)
	{
		$dir = rtrim($dir, '/\\');
		if( ! is_dir($dir) ) {
			return "{$dir} 不是目录。";
		}
		$dir .= '/';
		
		$fp = array();
		$ch = array();
		$mch = curl_multi_init();
		$file = array();
		foreach ($url as $k=>$u)
		{
			$fn = basename($u);
			if( ! preg_match('/\.[a-zA-Z]{2,4}$/', $fn) ) {
				$fn = md5($u);
			}
			$file[$k] = $fn;
			$fp[$k] = fopen($dir.$fn, 'wb');
			if( ! is_resource($fp[$k]) ) {
				unset($fp[$k]);
				foreach ($fp as $v)
				{
					fclose($v);
				}
				foreach ($ch as $v)
				{
					curl_close($v);
				}
				curl_multi_close($mch);
				return "文件创建失败，{$fn}。";
			}
			
			preg_match('#(^.*?(?<!/))/(?!/)#', $u, $ref);
			if( $ref ) {
				$ref = $ref[1];
			} else {
				$ref = '';
			}
			
			$ch[$k] = curl_init($u);
			curl_setopt($ch[$k], CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0");
			curl_setopt($ch[$k], CURLOPT_REFERER, $ref);
			curl_setopt($ch[$k], CURLOPT_FILE, $fp[$k]);
			curl_setopt($ch[$k], CURLOPT_HEADER, 0);
			curl_setopt($ch[$k], CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch[$k], CURLOPT_FOLLOWLOCATION, 1);
			curl_multi_add_handle($mch, $ch[$k]);
		}
		
		$active = null;
		do {
			curl_multi_exec($mch, $active);
		} while($active > 0);
		
		foreach ($ch as $k=>$v)
		{
			curl_multi_remove_handle($mch, $v);
			curl_close($v);
			fclose($fp[$k]);
		}
		curl_multi_close($mch);
		
		return $file;
	}
}
