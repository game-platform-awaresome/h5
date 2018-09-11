<?php
/**
 * HTML辅助类
 * 
 * @author ideadawn@gmail.com
 */
class F_Helper_Html
{
    const Op_Null = 0;
	const Op_View = 1;
	const Op_Edit = 2;
	const Op_Delete = 4;
	const Op_VE = 3;
	const Op_VD = 5;
	const Op_ED = 6;
	const Op_All = 7;
	
	/**
	 * @var Yaf_Request_Abstract
	 */
	protected $_request;
	
	protected $_op_add;
	protected $_op_edit;
	protected $_op_delete;
	
	/**
	 * 数据模型对象
	 * 
	 * @var F_Model_Pdo
	 */
	protected $_model;
	
	/**
	 * 分页标识
	 * 
	 * @var string
	 */
	protected $_marker = 'pn';
	/**
	 * 页码显示数量
	 * 
	 * @var int
	 */
	protected $_half = 5;
	/**
	 * 分页地址
	 * 
	 * @var string
	 */
	protected $_url;
	/**
	 * 分页类
	 * 
	 * @var string
	 */
	protected $_pager = 'F_Helper_Pager';
	
	public function __construct(Yaf_Request_Abstract $request, &$model = null)
	{
		$this->_request = $request;
		$this->_model = $model;
	}
	
	/**
	 * 设置分页类及其参数
	 * 
	 * @param string $pager
	 */
	public function setPager($pager = null, $marker = null, $half = null, $url = null)
	{
		$pager && $this->_pager = $pager;
		$marker && $this->_marker = $marker;
		$half && $this->_half = $half;
		$url && $this->_url = $url;
	}
	
	/**
	 * 生成搜索条
	 * 
	 * @param array &$cnf
	 * @return string $html
	 */
	public function SearchBar(&$cnf)
	{
		/*$cnf = array(
			'table_field' => array('name', 'html_dom_type', 'data_array', 'default_value', 'attr_mixed', 'css_mixed'),
			'id' => array('ID', 'input', null, '', array('required', 'placeholder'=>'序号'), array('width'=>'80px')),
			'status' => array('状态', 'select', array(0=>'关闭',1=>'开启'), 0),
		);*/
	    $search = isset($_GET['search']) ? $_GET['search'] : null;
	    $html = '<form id="search_form" action="" method="get">';
	    foreach ($cnf as $field=>$row)
	    {
	        if( isset($search[$field]) ) {
	            $row[3] = $search[$field];
	        }
	        
	        if( $row[1] == 'select' ) {
    	        $html .= "<select id=\"s_{$field}\" name=\"search[{$field}]\"";
	        } else {
	            //$html .= "<span>{$row[0]}：</span><input type=\"text\" id=\"s_{$row[1]}\" name=\"search[{$row[1]}]\"";
	            $html .= "<input type=\"text\" id=\"s_{$field}\" name=\"search[{$field}]\" placeholder=\"{$row[0]}\"";
	            if( $row[1] == 'datepicker' ) {
	                $row[2] = empty($row[2]) ? '' : $row[2];
	                $html .= " onclick=\"WdatePicker({$row[2]})\"";
	            }
	        }
	        
	        if( isset($row[4]) ) {
	            if( is_array($row[4]) ) {
	                foreach ($row[4] as $ak=>$av)
	                {
	                    $html .= is_int($ak) ? " {$av}=\"{$av}\"" : " {$ak}=\"{$av}\"";
	                }
	            } else {
	                $html .= " {$row[4]}";
	            }
	        }
	        if( isset($row[5]) ) {
	            if( is_array($row[5]) ) {
	                $html .= ' style="';
	                foreach ($row[5] as $ck=>$cv)
	                {
	                    $html .= " {$ak}:{$av};";
	                }
	                $html .= '"';
	            } else {
	                $html .= " style=\"{$row[5]}\"";
	            }
	        }
	        
	        if( $row[1] == 'select' ) {
	            $html .= '>';
    	        $html .= "<option value=\"\">--{$row[0]}--</option>";
    	        foreach ($row[2] as $sk=>$sv)
    	        {
    	            if( is_int($sk) ) {
    	                $selected = ($row[3] !== null && $row[3] == $sv) ? ' selected="selected"' : '';
    	                $html .= "<option value=\"{$sv}\"{$selected}>{$sv}</option>";
    	            } else {
    	                $selected = ($row[3] !== null && $row[3] == $sk) ? ' selected="selected"' : '';
    	                $html .= "<option value=\"{$sk}\"{$selected}>{$sv}</option>";
    	            }
    	        }
    	        $html .= '</select>';
	        } else {
	            if( isset($row[3]) ) {
	                $html .= " value=\"{$row[3]}\"";
	            }
	            $html .= '>';
	        }
	    }
	    $html .= '<input type="button" class="button" value="搜索">';
	    $html .= '</form>';
	    return $html;
	}
	
	/**
	 * 生成Table数据列表
	 * 
	 * @param mixed $conditions null为不读取数据，只生成HTML，''为读取所有数据
	 * @param int $limit
	 * @param string $orderby
	 * @param int $op
	 * @param string $perpage
	 * @return string $html
	 */
	public function DataList($conditions = null, $limit = 30, $orderby = null, $op = self::Op_ED, $perpage = null)
	{
		if( $conditions === null ) {
			$total = 0;
		} else {
			$total = $this->_model->fetchCount($conditions);
		}
		
		$html = '<div class="content-box">';
		$html .= '<div class="content-box-header"><h3>';
		$html .= $this->_model->getTableLabel();
		$html .= '<small>（总计：'.number_format($total).' 条数据）</small>';
		$html .= '</h3>'; //<a class="go_back" href="javascript:history.go(-1);">返回上一页</a></div>
		
		$sch_cnf = $this->_model->getFieldsSearch();
		if( $sch_cnf ) {
		    $html .= $this->SearchBar($sch_cnf);
		} else {
		    $html .= '<a class="go_back" href="javascript:history.go(-1);">返回上一页</a>';
		}
		$html .= '</div>';
		
		$html .= '<div class="content-box-content"><div class="tab-content default-tab"><table>';
		$labels = $this->_model->getFieldsLabel();
		$padding = $this->_model->getFieldsPadding();
		$html .= '<thead><tr>';
		$row = null;
		$select = array();
		foreach ($labels as $f=>$l)
		{
		    if( is_string($f) ) {
		        $select[] = $f;
		    }
			if( is_callable($l) ) {
				$html .= '<th>';
				$html .= $l($row);
				$html .= '</th>';
			} else if( $l == null ) {
				continue;
			} else {
				$html .= "<th>{$l}</th>";
			}
		}
		$select = '`'.implode('`,`', $select).'`';
		foreach ($padding as $l)
		{
		    $html .= '<th>';
		    $html .= $l($row);
		    $html .= '</th>';
		}
		if( $op ) {
			$html .= '<th>操作</th>';
			
			$url_pre = '/';
			$module = $this->_request->getModuleName();
			if( $module ) {
				$url_pre .= strtolower($module).'/';
			}
			$url_pre .= strtolower($this->_request->getControllerName()).'/';
		}
		$html .= '</tr></thead>';
		
		if( $total <= $limit ) {
			$pagelink = '';
		} else {
			$total = ceil($total/$limit);
			$pagelink = new $this->_pager($total, null, $this->_marker, 5, $perpage);
		}
		$html .= '<tfoot><tr><td colspan="200"><div class="pagination">';
		$html .= $pagelink;
		$html .= '</div></td></tr></tfoot><tbody>';
		
		if( $conditions !== null ) {
			$times = preg_match("/({$this->_marker}=(\d*))/", $_SERVER['REQUEST_URI'], $match);
			if( $times ) {
				$current = intval($match[2]);
				$current = max(1, $current);
			} else {
				$current = 1;
			}
			if( $current > $total ) {
				$current = $total;
			}
			$rows = $this->_model->fetchAll($conditions, $current, $limit, $select, $orderby);
			$i = 1;
			foreach ($rows as &$row)
			{
				if( $i % 2 ) {
					$html .= '<tr class="alt-row">';
				} else {
					$html .= '<tr>';
				}
				foreach ($labels as $f=>$l)
				{
					if( is_callable($l) ) {
						$html .= '<td>';
						$html .= $l($row);
						$html .= '</td>';
					} else if( $l == null ) {
						continue;
					} else {
						$html .= "<td>{$row[$f]}</td>";
					}
				}
				foreach ($padding as $l)
				{
				    $html .= '<td>';
				    $html .= $l($row);
				    $html .= '</td>';
				}
				if( $op ) {
					$html .= '<td>';
					
					$callback = $this->_model->getListCallback();
					$primary = $this->_model->getPrimary();
					$query = '?'.$_SERVER['QUERY_STRING'];
					$cmm = empty($_SERVER['QUERY_STRING']) ? '' : '&';
					if( is_array($primary) ) {
						foreach ($primary as $_pk)
						{
							$query .= "{$cmm}{$_pk}={$row[$_pk]}";
							$cmm = '&';
						}
					} else {
						$query .= "{$cmm}{$primary}={$row[$primary]}";
					}
					
					if( $op & self::Op_View ) {
					    if( empty($callback['op_view']) || $callback['op_view']($row) ) {
    						$html .= "<a href=\"{$url_pre}view{$query}\"><img src=\"/admin/images/icons/search.png\" /></a>&nbsp;";
					    }
					}
					if( $op & self::Op_Edit ) {
					    if( empty($callback['op_edit']) || $callback['op_edit']($row) ) {
    						$html .= "<a href=\"{$url_pre}edit{$query}\"><img src=\"/admin/images/icons/pencil.png\" /></a>&nbsp;";
					    }
					}
					if( $op & self::Op_Delete ) {
					    if( empty($callback['op_delete']) || $callback['op_delete']($row) ) {
    						$html .= "<a class=\"op_delete\" href=\"{$url_pre}delete{$query}\"><img src=\"/admin/images/icons/cross.png\" /></a>";
					    }
					}
					
					$html .= '</td>';
				}
				$html .= '</tr>';
				++$i;
			}
		}
		
		$html .= '</tbody></table></div></div>';
		$html .= '</div>';
		return $html;
	}
	
	/**
	 * 生成Table数据列表
	 * 
	 * @param string $sql
	 * @return string $html
	 */
	public function DataListBySql($sql)
	{
		
	}
	
	/**
	 * 生成Form表单
	 * 
	 * @param array &$cnf 与SearchBar的配置类似，但是支持更多的类型，如checkbox,radio,textarea等
	 * @return string $html
	 */
	public function Form(&$cnf)
	{
		
	}
}
