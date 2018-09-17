<?php

class PayModel extends F_Model_Pdo
{
	protected $_table = 'pay';
	protected $_primary='pay_id';
    public function __construct()
    {
        parent::__construct('h5');
    }
	public $_types = array(
	    'alipay' => '支付宝',
	    'wxpay' => '微信',
	    'iapppay' => '爱贝',
	    'deposit' => '平台币',
        ''=>'未知'
	);
	
	public function getTableLabel()
	{
		return '支付记录';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'pay_id' => function(&$row){
		        if( empty($row) ) return '支付ID';
		        return sprintf('%16.0f', $row['pay_id']);
		    },
		    'username' => function(&$row){
                if( empty($row) ) return '玩家账号';
                if($row['player_channel']){
                    return "(玩家推广)".$row['username'];
                }else{
                    return $row['username'];
                }
            }
            ,
		    'to_user' => '充入账号',
		    'tg_channel' => '渠道id',
		    'game_name' => '游戏名称',
			'server_name' => '区/服名称',
		    'money' => function(&$row){
		        if( empty($row) ) return '充值金额';
		        return number_format($row['money']).'￥';
		    },
		    'deposit' => function(&$row){
		        if( empty($row) ) return '平台币存量';
		        return $row['game_name'] ? '-' : number_format($row['deposit']).'￥';
		    },
            'player_channel' => function(&$row){
                if( empty($row) ) return '获得分成';
                if($row['player_channel']){
                    if($row['tg_channel']==$_SESSION['admin_id']){
                        //自推广
                        switch ($_SESSION['cps_type']){
                            case 1:
                                return 0;
                            case 2:
                                $game=new GameModel();
                                $divide_into=$game->fetch("game_id={$row['game_id']}",'divide_into');
                                $divide_into=(int)($divide_into['divide_into']-20);
                                return $row['money']*$divide_into/100;
                            case 3:
                                $admin=new AdminModel();
                                $divide_into=$admin->fetch("admin_id = {$_SESSION['admin_id']}",'divide_into');
                                $divide_into=(int)($divide_into['divide_into']-20);
                                return $row['money']*$divide_into/100;
                            default:
                                break;
                        }
                    }else{
                        //下级代理推广
                        //自推广
                        switch ($_SESSION['cps_type']){
                            case 1:
                                return 0;
                            case 2:
                                $game=new GameModel();
                                $divide_into=$game->fetch("game_id={$row['game_id']}",'divide_into');
                                $divide_into_game=$divide_into['divide_into'];
                                $admin=new AdminModel();
                                $divide_into_admin=$admin->fetch("admin_id = {$row['tg_channel']}",'divide_into');
                                $divide_into_admin=$divide_into_admin['divide_into'];
                                return $row['money']*((int)$divide_into_game-(int)$divide_into_admin)/100;
                            case 3:
                                return 0;
                            default:
                                break;
                        }
                    }
                }else{
                    if($row['tg_channel']==$_SESSION['admin_id']){
                        //自推广
                        switch ($_SESSION['cps_type']){
                            case 1:
                                return 0;
                            case 2:
                                $game=new GameModel();
                                $divide_into=$game->fetch("game_id={$row['game_id']}",'divide_into');
                                $divide_into=$divide_into['divide_into'];
                                return $row['money']*$divide_into/100;
                            case 3:
                                $admin=new AdminModel();
                                $divide_into=$admin->fetch("admin_id = {$_SESSION['admin_id']}",'divide_into');
                                $divide_into=$divide_into['divide_into'];
                                return $row['money']*$divide_into/100;
                            default:
                                break;
                        }
                    }else{
                        //下级代理推广
                        //自推广
                        switch ($_SESSION['cps_type']){
                            case 1:
                                return 0;
                            case 2:
                                $game=new GameModel();
                                $divide_into=$game->fetch("game_id={$row['game_id']}",'divide_into');
                                $divide_into_game=$divide_into['divide_into'];
                                $admin=new AdminModel();
                                $divide_into_admin=$admin->fetch("admin_id = {$row['tg_channel']}",'divide_into');
                                $divide_into_admin=$divide_into_admin['divide_into'];
                                return $row['money']*((int)$divide_into_game-(int)$divide_into_admin)/100;
                            case 3:
                                return 0;
                            default:
                                break;
                        }
                    }
                }
            },
		    'type' => function(&$row){
		        if( empty($row) ) return '支付渠道';
		        return $this->_types[$row['type']];
		    },
		    'pay_type' => '支付方式',
			'trade_no' => '第三方流水号',
		    'add_time' => function(&$row){
		        if( empty($row) ) return '下单时间';
		        return date('Y-m-d H:i:s', $row['add_time']);
		    },
		    'pay_time' => function(&$row){
		        if( empty($row) ) return '付款时间';
		        return $row['pay_time'] ? date('Y-m-d H:i:s', $row['pay_time']) : '-';
		    },
		    'finish_time' => function(&$row){
		        if( empty($row) ) return '到账时间';
		        return $row['finish_time'] ? date('Y-m-d H:i:s', $row['finish_time']) : '-';
		    },
		);
	}
	
	public function getFieldsSearch()
	{
	    return array(
            'add_begin' => array('开始日期', 'datepicker', null, ''),
            'add_end' => array('结束日期', 'datepicker', null, ''),
	        'pay_type' => array('付款方式', 'select', $this->_types, ''),
	        'tg_channel' => array('渠道id', 'input', null, ''),
	        'pay_id' => array('支付ID', 'input', null, ''),
	        'username' => array('用户名', 'input', null, ''),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'server_id' => array('区/服ID', 'input', null, ''),
	        'trade_no' => array('流水号', 'input', null, ''),
	    );
	}
	
	/**
	 * 生成16位订单ID，在数据库中是bigint型
	 * 
	 * @return string
	 */
	public function createPayId()
	{
	    list($usec, $sec) = explode(' ', microtime());
	    $usec = str_replace('0.', '', $usec);
	    $pay_id = substr($sec, 1);
	    $len = strlen($usec);
	    $pad = 100;
	    if( $len < 4 ) {
	        $pad *= pow(10, 4 - $len);
	    } else {
	        $len = 4;
	    }
	    $pay_id .= substr($usec, 0, $len);
	    $pay_id .= mt_rand($pad, $pad*10-1);
	    return $pay_id;
	}
}
