<?php
/**
 * Redis FIFO list.
 * 
 * @author ideadawn
 */

class F_Redis_FIFO
{
	/**
	 * @var Redis
	 */
	private $redis;
	
	/**
	 * @var string
	 */
	private $list;
	
	/**
	 * Construct function.
	 * 
	 * @param Redis $redis Redis Object
	 * @param string $list List Name
	 */
	public function __construct(Redis $redis, $list)
	{
		$this->redis = $redis;
		$this->list = $list;
	}
	
	/**
	 * Set list name.
	 * 
	 * @param string $list List Name
	 */
	public function setList($list)
	{
		$this->list = $list;
	}
	
	/**
	 * Push a value into list.
	 * 
	 * @param string $value
	 */
	public function push($value)
	{
		return $this->redis->lPush($this->list, $value);
	}
	
	/**
	 * Pop a value from list.
	 */
	public function pop()
	{
		return $this->redis->lPop($this->list);
	}
}
