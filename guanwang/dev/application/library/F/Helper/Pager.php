<?php
/**
 * @Author ideadawn@gmail.com
 * @Date 2008.12
 * 
 * @Usage
 * 
 * $total = 2;
 * $pagelink = new F_Helper_Pager($total);
 * echo $pagelink;
 */

class F_Helper_Pager
{
	protected $_pagelink = '';
	
	public function __construct($total, $url = null, $marker = 'pn', $half = 5, $perpage = null)
	{
		$times = preg_match("#({$marker}(?:=|/)(\d+))#", $_SERVER['REQUEST_URI'], $match);
		if( $times ) {
			$current = intval($match[2]);
			$current = max(1, $current);
		} else {
			$current = 1;
		}
		$current = ( $current > $total ) ? $total : $current;
		if( $total == 1 ) {
			return ;
		}
		
		if( empty($url) ) {
			$replace = "{$marker}=[{$marker}]";
			if( $times ) {
				$url = str_replace($match[1], $replace, $_SERVER['REQUEST_URI']);
			} elseif( strpos($_SERVER['REQUEST_URI'], '?') !== false ) {
				$url = "{$_SERVER['REQUEST_URI']}&{$replace}";
			} else {
				$url = "{$_SERVER['REQUEST_URI']}?{$replace}";
			}
		}
		$marker = "[{$marker}]";
		
		if( $current > 1 ) {
			$this->_pagelink .= '<a href="'.str_replace($marker,1,$url).'">首页</a>&nbsp;';
			$this->_pagelink .= '<a href="'.str_replace($marker,$current-1,$url).'">上一页</a>&nbsp;';
		}
		
		for($i = $current - $half, $i = ($i > 0) ? $i : 1, $j = $current + $half, $j = ($j > $total) ? $total : $j; $i <= $j; $i++)
		{
			( $i == $current ) ? 
				$this->_pagelink .= '<a class="current">'.$i.'</a>&nbsp;' : 
				$this->_pagelink .= '<a href="'.str_replace($marker,$i,$url).'">'.$i.'</a>&nbsp;';
		}
		
		if( $current < $total ) {
			$this->_pagelink .= '<a href="'.str_replace($marker,$current+1,$url).'">下一页</a>';
			$this->_pagelink .= '<a href="'.str_replace($marker,$total,$url).'">尾页</a>';
		}
		
		if( $perpage ) {
			$pp_url = str_replace($marker,$current,$url);
			$pp_url .= strpos($url, '?') ? "&{$perpage}=" : "?{$perpage}=";
			$this->_pagelink .= '<script>function ChangePerpage(o){';
			$this->_pagelink .= 'var pp_url = "';
			$this->_pagelink .= $pp_url;
			$this->_pagelink .= '";';
			$this->_pagelink .= 'var pp_val = o.value; if(pp_val==""){return false;}';
			$this->_pagelink .= 'location.href = pp_url+pp_val;';
			$this->_pagelink .= '}</script>';
			$this->_pagelink .= '<select onchange="javascript:ChangePerpage(this);">';
			$this->_pagelink .= '<option value="">每页条数</option>';
			$this->_pagelink .= '<option value="10">10</option>';
			$this->_pagelink .= '<option value="20">20</option>';
			$this->_pagelink .= '<option value="30">30</option>';
			$this->_pagelink .= '<option value="40">40</option>';
			$this->_pagelink .= '<option value="50">50</option>';
			$this->_pagelink .= '<option value="100">100</option>';
			$this->_pagelink .= '<option value="200">200</option>';
			$this->_pagelink .= '</select>';
		}
	}
	
	public function __toString()
	{
		return $this->_pagelink;
	}
}
