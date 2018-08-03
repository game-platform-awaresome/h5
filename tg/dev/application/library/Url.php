<?php

class Url
{
    /**
     * URL重写后缀
     * 
     * @var string
     */
    static public $suffix;
    
	/**
	 * 生成URL地址（可变参数）
	 * 
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param mixed $params
	 * @param mixed $query
	 * @return string
	 */
	static public function rewrite($module = '', $controller = '', $action = '', $params = null, $query = null)
	{
	    if( empty($module) && empty($controller) ) {
	        return '/';
	    }
	    
	    $u = '';
		if( $module && $module != 'index' ) {
		    $u .= "/{$module}";
		}
		if( $controller ) {
    		$u .= "/{$controller}";
		}
		if( $action ) {
		    $u .= "/{$action}";
		}
		
		if( is_array($params) ) {
			foreach ($params as $k=>$v)
			{
				$u .= "/{$k}/{$v}";
			}
		} else if( $params ) {
			$u .= "/{$params}";
		}
		$u .= self::$suffix;
		
		if( is_array($query) ) {
		    $cmm = '?';
		    foreach ($query as $k=>$v)
		    {
		        $u .= "{$cmm}{$k}={$v}";
		        $cmm = '&';
		    }
		} else if( $query ) {
		    $u .= "?{$query}";
		}
		
		return $u;
	}
}
