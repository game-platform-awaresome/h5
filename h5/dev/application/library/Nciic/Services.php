<?php

class Nciic_Services
{
    private $_dir;
    private $_soap;
    private $_license;
    
    public function __construct()
    {
        $this->_dir = dirname(__FILE__).'/';
        $this->_license = file_get_contents($this->_dir.'zchljoy.lic');
        $wsdl = "file://{$this->_dir}nciic.wsdl";
        $this->_soap = new SoapClient($wsdl);
    }
    
    /**
     * 验证身份证号与真实姓名是否一致
     * 
     * @param string|array $id
     * @param string|null $name
     * @return string|array
     */
    public function checkIdName($id, $name = null)
    {
        $conds = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<ROWS>
    <INFO>
        <SBM>成都众创互乐科技有限公司</SBM>
    </INFO>
    <ROW>
        <GMSFHM>公民身份号码</GMSFHM>
        <XM>姓名</XM>
    </ROW>
XML;
        if( is_array($id) ) {
            foreach ($id as $row)
            {
                $conds .= <<<XML
    <ROW FSD="610041" YWLX="防沉迷验证">
        <GMSFHM>{$row['idno']}</GMSFHM>
        <XM>{$row['realname']}</XM>
    </ROW>
XML;
            }
        } else {
            $conds .= <<<XML
    <ROW FSD="610041" YWLX="防沉迷验证">
        <GMSFHM>{$id}</GMSFHM>
        <XM>{$name}</XM>
    </ROW>
XML;
        }
        $conds .= <<<XML
</ROWS>
XML;
        $rs = $this->_soap->nciicCheck(array('inLicense'=>$this->_license, 'inConditions'=>$conds));
        $rs = $rs->out;
        $log = new F_Helper_Log();
        $log->debug($rs);
        $log->debug("\r\n");
        
        if( strpos($rs, '<RESPONSE') !== false ) {
            if( preg_match_all('/<ErrorMsg>([^<]+)<\/ErrorMsg>/', $rs, $error) ) {
                return implode('；', $error[1]);
            } else {
                return '服务器错误。';
            }
        }
        
        $xml = simplexml_load_string($rs);
        if( $xml == false ) {
            return 'XML解析失败。';
        }
        $xml = (array)$xml;
        $data = array();
        if( array_key_exists(0, $xml['ROW']) ) {
        	foreach($xml['ROW'] as $row)
        	{
        		$item = array(
        			'idno' => $row->INPUT->gmsfhm->__toString(),
        			'realname' => $row->INPUT->xm->__toString(),
        		);
        		if( array_key_exists('errormessage', $row->OUTPUT->ITEM[0]) ) {
        			$id_rs = $row->OUTPUT->ITEM[0]->errormessage->__toString();
        			if( strpos($id_rs, '无此号') ) {
        			    $item['status'] = -2;
        			} else {
        			    $item['status'] = -1;
        			    $log->debug("{$item['idno']}\t{$item['realname']}\t{$id_rs}\r\n");
        			}
        		} else {
        			$id_rs = $row->OUTPUT->ITEM[0]->result_gmsfhm->__toString();
        			$rn_rs = $row->OUTPUT->ITEM[1]->result_xm->__toString();
        			if( $id_rs == '一致' && $rn_rs == '一致' ) {
        			    $item['status'] = 1;
        			} else {
        			    $item['status'] = -1;
        			}
        		}
        		$data[] = $item;
        	}
        } else {
        	$item = array(
        		'idno' => $xml['ROW']->INPUT->gmsfhm->__toString(),
        		'realname' => $xml['ROW']->INPUT->xm->__toString(),
        	);
        	if( array_key_exists('errormessage', $xml['ROW']->OUTPUT->ITEM[0]) ) {
        		$id_rs = $xml['ROW']->OUTPUT->ITEM[0]->errormessage->__toString();
        		if( strpos($id_rs, '无此号') ) {
        		    $item['status'] = -2;
        		} else {
        		    $item['status'] = -1;
        		    $log->debug("{$item['idno']}\t{$item['realname']}\t{$id_rs}\r\n");
        		}
        	} else {
        		$id_rs = $xml['ROW']->OUTPUT->ITEM[0]->result_gmsfhm->__toString();
        		$rn_rs = $xml['ROW']->OUTPUT->ITEM[1]->result_xm->__toString();
        		if( $id_rs == '一致' && $rn_rs == '一致' ) {
        		    $item['status'] = 1;
        		} else {
        		    $item['status'] = -1;
        		}
        	}
        	$data[] = $item;
        }
        return $data;
    }
}

/*
//异常返回示例
$xml = '<?xml version="1.0" encoding="UTF-8" ?>
<RESPONSE errorcode="xxx" code="0" countrows="1">
    <ROWS>
        <ROW>
            <ErrorCode>xxx</ErrorCode>
            <ErrorMsg>xxxxxx</ErrorMsg>
        </ROW>
    </ROWS>
</RESPONSE>';
*/
/*
//成功返回示例
$xml = '<?xml version="1.0" encoding="UTF-8" ?>
<ROWS>
	<ROW no="1">
		<INPUT>
			<gmsfhm>510121</gmsfhm>
			<xm>张三</xm>
		</INPUT>
		<OUTPUT>
			<ITEM>
				<gmsfhm />
				<result_gmsfhm>一致</result_gmsfhm>
			</ITEM>
			<ITEM>
				<xm />
				<result_xm>一致</result_xm>
			</ITEM>
		</OUTPUT>
	</ROW>
	<ROW no="2">
		<INPUT>
			<gmsfhm>510121</gmsfhm>
			<xm>李四</xm>
		</INPUT>
		<OUTPUT>
			<ITEM>
				<errormessage>查无此号</errormessage>
			</ITEM>
			<ITEM>
				<errormessagecol />
			</ITEM>
		</OUTPUT>
	</ROW>
</ROWS>';
*/
