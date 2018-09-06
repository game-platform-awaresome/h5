<?php

class GameModel extends F_Model_Pdo
{
	protected $_table = 'game';
	protected $_primary = 'game_id';
    public $_game_types = array('h5','微端');
	//类型
	public $_types = array('推荐','独家','BT版','满V版','GM版');
	//经典分类
	public $_classic = array(
	    '角色扮演', '动作过关', '休闲娱乐', '街机童年', '竞速狂飙', '卡牌回合', '体育竞技', '解密探险', '放置挂机', '音乐舞蹈',
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
	
	public function __construct()
	{
	    parent::__construct('h5');
	}
	
	public function getTableLabel()
	{
		return '游戏';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'game_id' => '游戏ID',
			'name' => '名称',
		    'game_type' => '类型',
		    'type' => '分类',
		    'classic' => '经典分类',
            'divide_into' => '分成比例',
            'corner' => function(&$row){
		        if( empty($row) ) return '角标';
		        return $this->_corner[$row['corner']];
		    },
		    'label' => function(&$row){
		        if( empty($row) ) return '后标';
		        return $this->_labels[$row['label']];
		    },
//		    'giftbag' => function(&$row){
//		        if( empty($row) ) return '礼包ID';
//		        return $row['giftbag'] ? "<a href=\"/admin/giftbag/list?search[gift_id]={$row['giftbag']}\">{$row['giftbag']}</a>" : '-';
//		    },
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
			'material_url' =>
                function(&$row){
                    if( empty($row) ) return '素材下载';
                    return "<a href=\"{$row['material_url']}\">{$row['material_url']}</a>";
                }
            ,
            'apk_url' =>
                function(&$row){
                    if( empty($row) ) return '下载地址';
                    if($row['game_type']=='h5') return '不需要';
                    return "
                        <a href=\"{$row['apk_url']}\">下载</a>|
                        <a  href=\"/admin/game/deletechannelapk?game_id={$row['game_id']}\" onclick=\"if(confirm('确定删除?')==false)return false;\">删除</a> 
                    ";
                }
        ,
		    'add_time' => function(&$row){
		        if( empty($row) ) return '添加时间';
		        return substr($row['add_time'], 0, 10);
		    },
		    'visible' => function(&$row){
		        if( empty($row) ) return '是否可见';
		        return $row['visible'] ? '是' : '-';
		    },
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
	
	//上线
	public function online($game_id)
	{
	    $conds = "game_id='{$game_id}'";
	    $this->update(array('visible'=>1), $conds);
	}
	//下线
	public function offline($game_id)
	{
	    $conds = "game_id='{$game_id}'";
	    $this->update(array('visible'=>0), $conds);
	}
}
