<?php

class GameModel extends F_Model_Pdo
{
	protected $_table = 'game';
	protected $_primary = 'game_id';
	
	//类型
	public $_types = array('推荐','独家','互动','网游','单机');
	//经典分类
	public $_classic = array(
	    '角色扮演', '动作过关', '休闲娱乐', '街机童年', '竞速狂飙', '亲子益智', '体育竞技', '解密探险', '棋牌竞技', '音乐舞蹈',
	);
	//角标
	public $_corner = array(
	    'normal' => '-',
	    'promotion' => '优惠',
	    'discount' => '折扣',
	    'rebate' => '返利',
	    'activity' => '活动',
	);
	//后标
	public $_labels = array(
	    'normal' => '-',
	    'hot' => '人气',
	    'new' => '上新',
	);
	//渠道
	public $_channels = array(
	    'self' => '-',
	    'egret' => '白鹭',
	);
	public $_load_types = array(
	    'iframe' => 'iframe标签',
	    'object' => 'object标签',
	    'redirect' => '跳转（域名会变，慎用）',
	);
	public $_screens = array(
	    'auto' => '自适应',
	    'vertical' => '竖屏',
	    'horizontal' => '横屏',
	);
	
	//特殊分类
	public $_special_all = array('all' => '全部');
	public $_special = array(
	    'new' => '最新',
	    'support' => '人气',
	    'grade' => '星级',
	);
	
	public function getTableLabel()
	{
		return '游戏';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'game_id' => '游戏ID',
			'name' => '名称',
		    'type' => '分类',
		    'classic' => '经典分类',
		    'corner' => function(&$row){
		        if( empty($row) ) return '角标';
		        return $this->_corner[$row['corner']];
		    },
		    'label' => function(&$row){
		        if( empty($row) ) return '后标';
		        return $this->_labels[$row['label']];
		    },
		    'giftbag' => function(&$row){
		        if( empty($row) ) return '礼包ID';
		        return $row['giftbag'] ? "<a href=\"/admin/giftbag/list?search[gift_id]={$row['giftbag']}\">{$row['giftbag']}</a>" : '-';
		    },
		    'logo' => function(&$row){
		        if( empty($row) ) return '图标';
		        return $row['logo'] ? "<a class=\"lightbox\" href=\"{$row['logo']}\"><img style=\"max-width:32px;\" src=\"{$row['logo']}\"></a>" : '';
		    },
		    'support' => '人气',
		    'grade' => '评级',
		    'weight' => '排序',
		    'version' => '当前版本',
		    'trade_money' => '总流水',
			'play_times' => '游戏次数',
		    'add_time' => function(&$row){
		        if( empty($row) ) return '添加时间';
		        return substr($row['add_time'], 0, 10);
		    },
		    'visible' => function(&$row){
		        if( empty($row) ) return '可见';
		        return $row['visible'] ? '是' : '-';
		    },
		    'prepay' => function(&$row){
		        if( empty($row) ) return '直充';
		        return $row['prepay'] ? '是' : '-';
		    },
            'divide_into' =>'分成比例',
//		    'channel' => function(&$row){
//		        if( empty($row) ) return '合作渠道';
//		        return $this->_channels[$row['channel']];
//		    },
		);
	}
	
	public function getFieldsPadding()
	{
	    return array(
	        function(&$row){
	            if( empty($row) ) return '区/服列表';
	            return "<a href=\"/admin/server/list?search[game_id]={$row['game_id']}\">查看</a>";
	        }
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'type' => array('分类', 'select', $this->_types, null),
	        'game_id' => array('游戏ID', 'input', null, ''),
	        'add_begin' => array('开始日期', 'datepicker', null, ''),
	        'add_end' => array('结束日期', 'datepicker', null, ''),
	    );
	}
	
	/**
	 * 生成评级HTML
	 * 
	 * @param float $grade
	 * @return string
	 */
	public function gradeHtml($grade)
	{
	    $grade = round($grade);
	    $html = str_repeat('<i class="icons i_start1"></i>', $grade);
	    $html .= str_repeat('<i class="icons i_start2"></i>', 5 - $grade);
	    return $html;
	}
	
	/**
	 * 格式化人气数
	 * 
	 * @param int $support
	 * @return string
	 */
	public function supportFormat($support)
	{
	    if( $support > 10000000 ) {
	        return floor($support/10000000).'千万+';
	    } elseif( $support > 1000000 ) {
	        return floor($support/1000000).'百万+';
	    } elseif( $support > 10000 ) {
	        return floor($support/10000).'万+';
	    } elseif( $support > 1000 ) {
	        return floor($support/1000).'千+';
	    }
	    return $support;
	}
	
	/**
	 * 根据类型返回游戏总数，没有指定类型时返回所有类型游戏的总数
	 * 指定类型时返回INT型，没有指定类型时返回数组
	 * 
	 * @param string $type
	 * @return mixed
	 */
	public function getCountByType($type = '')
	{
	    if( $type != '' ) {
	        return $this->fetchCount("visible=1 AND type='{$type}'");
	    }
	    
	    $data = array();
	    foreach ($this->_types as $type)
	    {
	        $data[$type] = $this->fetchCount("visible=1 AND type='{$type}'");
	    }
	    return $data;
	}
	
	/**
	 * 根据类型返回游戏列表，没有指定类型则返回所有类型的游戏列表
	 * 指定类型时返回二位数组，没有指定类型时返回三维数组
	 * 
	 * @param int $pn
	 * @param int $limit
	 * @param string $type
	 * @return array
	 */
	public function getTopByType($pn = 1, $limit = 3, $type = '')
	{
	    if( in_array($type, $this->_types) ) {
	        $data = $this->fetchAll("visible=1 AND type='{$type}'", $pn, $limit, 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times', 'weight ASC');
	        foreach ($data as &$row)
	        {
	            $row['grade'] = $this->gradeHtml($row['grade']);
	            $row['support'] = $this->supportFormat($row['support'] + $row['play_times']);
	        }
	        return $data;
	    }
	    
	    $data = array();
	    foreach ($this->_types as $type)
	    {
	        $data[$type] = $this->fetchAll("visible=1 AND type='{$type}'", $pn, $limit, 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times', 'weight ASC');
	        foreach ($data[$type] as &$row)
	        {
	            $row['grade'] = $this->gradeHtml($row['grade']);
	            $row['support'] = $this->supportFormat($row['support'] + $row['play_times']);
	        }
	    }
	    return $data;
	}
	
	/**
	 * 根据游戏属性获取游戏列表
	 * 
	 * @param string $attr
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function getListByAttr($attr, $pn = 1, $limit = 10)
	{
	    switch ($attr)
	    {
	        case 'support':
	            $order = 'support DESC';
	            break;
	        case 'new':
	            $order = 'game_id DESC';
	            break;
	        case 'grade':
	            $order = 'grade DESC';
	            break;
	    }
	    $conds = 'visible=1';
	    $select = 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times';
	    
	    $games = $this->fetchAll($conds, $pn, $limit, $select, $order);
	    foreach ($games as &$row)
	    {
	        $row['grade'] = $this->gradeHtml($row['grade']);
	        $row['support'] = $this->supportFormat($row['support'] + $row['play_times']);
	    }
	    return $games;
	}
	
	/**
	 * 特殊列表获取方式
	 * 
	 * @param string $type
	 * @param int $pn
	 * @param int $limit
	 * @return mixed
	 */
	public function getListBySpecial($type, $pn = 1, $limit = 10)
	{
	    $conds = 'visible=1';
	    if( $type == 'all' ) {
	        $order = '';
	    } elseif( in_array($type, $this->_types) ) {
	        $conds .= " AND type='{$type}'";
	        $order = '';
	    } elseif( array_key_exists($type, $this->_special) ) {
	        if( $type == 'new' ) {
	            $order = "game_id DESC";
	        } else {
	            $order = "{$type} DESC";
	        }
	    } else {
	        $ord_s = ord('A');
	        $ord_e = ord('Z');
	        $ord_t = ord($type);
	        if( $ord_t >= $ord_s && $ord_t <= $ord_e ) {
	            $initial = include APPLICATION_PATH.'/application/cache/game/initial.php';
	        }
	    }
	    
	    if( isset($order) ) {
	        $order .= $order ? ',weight ASC' : 'weight ASC';
	        $select = 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times';
	        
	        $games = $this->fetchAll($conds, $pn, $limit, $select, $order);
	        foreach ($games as &$row)
	        {
	            $row['grade'] = $this->gradeHtml($row['grade']);
	            $row['support'] = $this->supportFormat($row['support'] + $row['play_times']);
	        }
	        return $games;
	    }
	    
	    if( isset($initial) && array_key_exists($type, $initial) ) {
	        $total = count($initial[$type]);
	        $offset = ($pn - 1) * $limit;
	        if( $total <= $offset ) {
	            return null;
	        }
	        
	        $tmp = array_slice($initial[$type], $offset, $limit);
	        $ids = array();
	        foreach ($tmp as &$row)
	        {
	            $ids[] = $row['game_id'];
	        }
	        $ids = implode(',', $ids);
	        
	        $conds = "game_id IN({$ids})";
	        $select = 'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times';
	        $order = 'weight ASC';
	        
	        $games = $this->fetchAll($conds, $pn, $limit, $select, $order);
	        foreach ($games as &$row)
	        {
	            $row['grade'] = $this->gradeHtml($row['grade']);
	            $row['support'] = $this->supportFormat($row['support'] + $row['play_times']);
	        }
	        return $games;
	    }
	    
	    return null;
	}
	
    /**
	 * 搜索游戏
	 * 
	 * @param string $name
	 * @param int $pn
	 * @param int $limit
	 * @return array
	 */
	public function search($name, $pn = 1, $limit = 10)
	{
	    $data = $this->fetchAll("visible=1 AND search LIKE '%{$name}%'", $pn, $limit,
	       'game_id,name,logo,corner,label,giftbag,support,grade,in_short,play_times', 'weight ASC');
	    foreach ($data as &$row)
	    {
	        $row['grade'] = $this->gradeHtml($row['grade']);
            $row['support'] = $this->supportFormat($row['support'] + $row['play_times']);
	    }
	    return $data;
	}
}
