<?php

class AdposModel extends F_Model_Pdo
{
	protected $_table = 'ad_pos';
	protected $_primary='pos_id';
	
	public $_target = array(
	    '_self' => '本页面',
	    '_blank' => '新页面',
	    '_top' => '顶层页面',
	);
	
	public function getTableLabel()
	{
		return '广告位';
	}
	
	public function getFieldsLabel()
	{
		return array(
			'pos_id' => '广告位ID',
		    'pos_code' => '程序调用代码',
		    'preview' => function(&$row){
		        if( empty($row) ) return '预览图';
		        return $row['preview'] ? "<a class=\"lightbox\" href=\"{$row['preview']}\"><img src=\"{$row['preview']}\" style=\"max-height:32px; max-width:300px;\"></a>" : '';
		    },
			'name' => '名称',
		    'width' => '宽度',
		    'height' => '高度',
		    'image' => function(&$row){
		        if( empty($row) ) return '默认图片';
		        return $row['image'] ? "<a class=\"lightbox\" href=\"{$row['image']}\"><img src=\"{$row['image']}\" style=\"max-height:32px; max-width:300px;\"></a>" : '';
		    },
		    'subject' => '默认标题',
		    'url' => '默认链接',
		    'target' => function(&$row){
		        if(empty($row)) return '打开方式';
		        return $this->_target[$row['target']];
		    },
		    'can_apply' => function(&$row){
		        if( empty($row) ) return '是否可申请';
		        return $row['can_apply'] ? '是' : '否';
		    },
		    'display' => '显示顺序',
		);
	}
	
	public function getFieldsPadding()
	{
	    return array(
	        function(&$row){
	            if( empty($row) ) return '广告列表';
	            return "<a href=\"/admin/adinstance/list?search[pos_id]={$row['pos_id']}\">查看列表</a>";
	        },
	        function(&$row){
	            if( empty($row) ) return '添加广告';
	            return "<a href=\"/admin/adinstance/edit?search[pos_id]={$row['pos_id']}\">添加广告</a>";
	        },
	    );
	}
	
	public function getFieldsSearch()
	{
	    return array(
	        'pos_id' => array('广告ID', 'input', null, ''),
	        'pos_code' => array('程序调用代码', 'input', null, ''),
	    );
	}
	
	/**
	 * 使用调用代码获取广告内容
	 * 
	 * @param string $code
	 * @param int $limit
	 * @return array
	 */
	public function getByCode($code, $limit = 0)
	{
	    $file = APPLICATION_PATH;
	    $file .= "/application/cache/adpos/{$code}.php";
	    if( ! file_exists($file) ) {
	        return array();
	    }
	    $data = include $file;
	    $count = count($data['ads']);
	    if( $limit != 0 && $count > $limit ) {
	        shuffle($data['ads']);
	    }
	    $time = time();
	    foreach ($data['ads'] as $k=>$ins)
	    {
	        if( $ins['on_time'] != 0 && $ins['on_time'] > $time ) {
	            unset($data['ads'][$k]);
	            continue;
	        }
	        if( $ins['off_time'] != 0 && $ins['off_time'] < $time ) {
	            unset($data['ads'][$k]);
	            continue;
	        }
	        $data['ads'][$k]['url'] = $ins['url'] ? $ins['url'] : 'javascript:void(0);';
	        $data['ads'][$k]['target'] = $ins['url'] ? " target=\"{$ins['target']}\"" : '';
	    }
	    $count = count($data['ads']);
	    if( $count == 0 ) {
	        $data['ads'][] = array(
	            'ad_id' => 0,
	            'image' => $data['image'],
	            'subject' => $data['subject'],
	            'url' => $data['url'] ? $data['url'] : 'javascript:void(0);',
	            'target' => $data['url'] ? " target=\"{$data['target']}\"" : '',
	        );
	    } elseif( $limit != 0 && $count > $limit ) {
	        $data['ads'] = array_slice($data['ads'], 0, $limit);
	    }
	    return $data;
	}
}
