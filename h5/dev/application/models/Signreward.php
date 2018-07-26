<?php

class SignrewardModel extends F_Model_Pdo
{
	protected $_table = 'sign_reward';
	protected $_primary='reward_id';
	
	public $_types = array(
	    'serial' => '连续签到',
	    'total' => '总计签到',
	);
	
	public function getTableLabel()
	{
		return '签到奖励';
	}
	
	public function getFieldsLabel()
	{
		return array(
		    'reward_id' => '奖励ID',
		    'type' => function(&$row){
		        if( empty($row) ) return '触发类型';
		        return $this->_types[$row['type']];
		    },
		    'days' => '触发条件天数',
		    'times' => function(&$row){
		        if( empty($row) ) return '可反复领取次数';
		        return $row['times'] ? $row['times'] : '不限';
		    },
		    'reward' => function(&$row){
		        if( empty($row) ) return '奖励内容';
		        if( $row['reward'] ) return $this->unserializeReward($row['reward']);
		        else return '';
		    },
		    'reset' => function(&$row){
		        if( empty($row) ) return '重置签到天数';
		        return $row['reset'] ? '是' : '-';
		    },
		    'disabled' => function(&$row){
		        if( empty($row) ) return '禁用此奖励';
		        return $row['disabled'] ? '是' : '-';
		    },
		);
	}
	
	/**
	 * 序列化奖励内容
	 * 
	 * @param string $reward
	 * @return string
	 */
	public function serializeReward($reward)
	{
	    $reward = str_replace("\r\n\r\n", "\n", $reward);
	    $reward = str_replace("\n\n", "\n", $reward);
	    $reward = explode("\n", $reward);
	    $data = array();
	    foreach ($reward as $row)
	    {
	        $data[] = explode(',', trim($row, ','));
	    }
	    return serialize($data);
	}
	/**
	 * 反序列化奖励内容
	 * 
	 * @param string $reward
	 * @return string
	 */
	public function unserializeReward($reward)
	{
	    $string = array();
	    $reward = unserialize($reward);
	    foreach ($reward as &$row)
	    {
	        $string[] = implode(',', $row);
	    }
	    return implode("\n", $string);
	}
}
