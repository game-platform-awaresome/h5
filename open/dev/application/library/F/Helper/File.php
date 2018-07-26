<?php
/**
 * @author ideadawn@gmail.com
 */

class F_Helper_File
{
	protected $_root = '';
	
	public function __construct($root)
	{
		if( ! is_dir($root) ) {
			throw new Exception("$root is not a valid directory.");
		}
		$this->_root = trim($root, '/\\');
	}
	
	/**
	 * 创建目录
	 * 
	 * @param string $path
	 * @param int $mode
	 * @return mixed $dir_abs
	 */
	public function createDir($path, $mode = 0777)
	{
		$path = preg_replace('#[/\\\]+#', '/', $path);
		$path = trim($path, '/\\');
		$a = explode('/', $path);
		$d = $this->_root;
		foreach ($a as $i)
		{
			$d .= '/'.$i;
			if( ! is_dir($d) ) {
				if( ! mkdir($d, $mode) ) {
					return false;
				}
			}
		}
		return $d;
	}
	
	/**
	 * 创建目录，会多次尝试
	 *
	 * @param string $path
	 * @param int $times
	 * @param int $mode
	 * @return mixed $dir_abs
	 */
	public function createDirTimes($path, $times = 3, $mode = 0777)
	{
		$path = preg_replace('#[/\\\]+#', '/', $path);
		$path = trim($path, '/\\');
		$a = explode('/', $path);
		$d = $this->_root;
		foreach ($a as $i)
		{
			$d .= '/'.$i;
			if( ! is_dir($d) ) {
				$ts = $times;
				while ( ! mkdir($d, $mode) )
				{
					--$ts;
					if( $ts < 1 ) {
						return false;
					}
					sleep(1);
				}
			}
		}
		return $d;
	}
	
	/**
	 * 获取MD5相对路径
	 * 
	 * @param string &$md5
	 * @param int $deeps
	 * @return string
	 */
	public function md5Path(&$md5, $deeps = 2)
	{
		$i = 0;
		$len = 2;
		$path = '';
		while ($deeps > 0)
		{
			$path .= substr($md5, $i, $len).'/';
			$i += $len;
			--$deeps;
		}
		return rtrim($path, '/');
	}
	
	/**
	 * 保存文件，会多次尝试
	 * 
	 * @param string &$file
	 * @param string &$data
	 * @return mixed $rs
	 */
	public function saveFileTimes(&$file, &$data, $times = 3)
	{
		$f = $this->_root.'/';
		$f .= $file;
		while ( ! file_put_contents($f, $data) )
		{
			--$times;
			if( $times < 1 ) {
				return false;
			}
			sleep(1);
		}
		return true;
	}
}
