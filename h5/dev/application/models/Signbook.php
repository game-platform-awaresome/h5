<?php

class SignbookModel extends F_Model_Pdo
{
	protected $_table = 'sign_book';
	protected $_primary='user_id';
	
	public $_types = array(
	    'serial' => '连续签到',
	    'total' => '总计签到',
	);
	
	public function getTableLabel()
	{
		return '签到薄';
	}
	
	/**
	 * 获取玩家当月签到记录
	 * 
	 * @param int $user_id
	 * @return array
	 */
	public function get($user_id)
	{
	    $ymd = date('Ym01');
	    $list = $this->fetchAll("user_id='{$user_id}' AND ymd>={$ymd} AND rewards<>''", 1, 31, 'ymd,rewards,serial_days,total_days', 'ymd ASC');
	    foreach ($list as &$row)
	    {
	        $row['date'] = date('n-j', strtotime($row['ymd']));
	    }
	    return $list;
	}
	
	/**
	 * 玩家签到
	 * 
	 * @param int $user_id
	 * @return array(result, msg)
	 */
	public function sign($user_id)
	{
	    $last = $this->fetch("user_id='$user_id' ORDER BY ymd DESC LIMIT 1", 'ymd,serial_days,total_days');
	    $today = date('Ymd');
	    if( $last && $last['ymd'] == $today ) {
	        return array(false, '今日已签到！');
	    }
	    //第一次签到
	    if( empty($last) ) {
	        $sign = array('ymd'=>$today, 'serial_days'=>1, 'total_days'=>1);
	    } else {
	        $yestoday = date('Ymd', strtotime('-1 day'));
	        //连续签到
	        if( $yestoday == $last['ymd'] ) {
	            $sign = array('ymd'=>$today, 'serial_days'=>(int)$last['serial_days'] + 1, 'total_days'=>(int)$last['total_days'] + 1);
	        } else {
	            $sign = array('ymd'=>$today, 'serial_days'=>1, 'total_days'=>(int)$last['total_days'] + 1);
	        }
	    }
	    
	    $rules = include APPLICATION_PATH.'/application/cache/game/signreward.php';
	    foreach ($rules as &$row)
	    {
	        if( $row['type'] == 'serial' ) {
	            if( (int)$row['reset'] == 1 && $sign['serial_days'] > (int)$row['days'] ) {
	                $sign['serial_days'] = 1;
	            }
	        } elseif( $row['type'] == 'total' ) {
	            if( (int)$row['reset'] == 1 && $sign['total_days'] > (int)$row['days'] ) {
	                $sign['total_days'] = 1;
	            }
	        }
	    }
	    
	    $serial = null;
	    $total = null;
	    $desc_s = '';
	    $desc_t = '';
	    foreach ($rules as &$row)
	    {
	        if( $row['type'] == 'serial' ) {
	            if( $sign['serial_days'] >= (int)$row['days'] ) {
	                $serial = $row['reward'];
	                $desc_s = "连续签到{$sign['serial_days']}天";
	            }
	        } elseif( $row['type'] == 'total' ) {
	            if( $sign['total_days'] >= (int)$row['days'] ) {
	                $total = $row['reward'];
	                $desc_t = "累计签到{$sign['total_days']}天";
	            }
	        }
	    }
	    
	    $money = 0;
	    $integral = 0;
	    if( $serial ) {
	        foreach ($serial as &$row)
	        {
	            if( $row[0] == 'integral' ) {
	                $integral += (int)$row[2];
	            } elseif( $row[0] == 'money' ) {
	                $money += (int)$row[2];
	            }
	        }
	    }
	    if( $total ) {
	        foreach ($total as &$row)
	        {
	            if( $row[0] == 'integral' ) {
	                $integral += (int)$row[2];
	            } elseif( $row[0] == 'money' ) {
	                $money += (int)$row[2];
	            }
	        }
	    }
	    
	    $sign['user_id'] = $user_id;
	    $sign['rewards'] = '';
	    $pdo = $this->getPdo();
	    $pdo->beginTransaction();
	    if( $money || $integral ) {
	        $m_user = new UsersModel();
	        $m_user->changeIntegralMoney($user_id, $integral, $money);
	        $sign['rewards'] .= '<i class="i_gold"></i><span>';
	    }
	    if( $money ) {
	        $sign['rewards'] .= " +{$money}平台币";
	    }
	    if( $integral ) {
	        $sign['rewards'] .= " +{$integral}积分";
	    }
	    if( $money || $integral ) {
	        $comma = $desc_s && $desc_t ? '，' : '';
	        $sign['rewards'] .= "</span><strong>{$desc_s}{$comma}{$desc_t}</strong>";
	    }
	    $rs = $this->insert($sign, false);
	    if( $rs ) {
	        $pdo->commit();
	        $sign['rewards'] .= '<time>'.date('n-j').'</time>';
	        return array(true, $sign['rewards'], $sign['serial_days'], $sign['total_days']);
	    } else {
	        $pdo->rollBack();
	        return array(false, '签到奖励领取失败，请稍后重试！');
	    }
	}
}
