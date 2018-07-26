<?php
/**
 * Author:		ideadawn
 * Copyright:	ideadawn
 * Contact:		ideadawn@126.com
 * Date:		2009.01
 * Description: Create or parse xml document.
 */

class F_Helper_Xml
{
	/**
	 * Store xml charset, UTF-8 for default.
	 *
	 * @var string
	 */
	private $encode = "UTF-8";
	
	/**
	 * Set XML document encoding.
	 *
	 * @param string $encoding
	 * @return null
	 */
	public function setEncoding($encoding = 'UTF-8')
	{
		$this->encode = $encoding;
		return null;
	}
	
	/**
	 * Create xml document.
	 *
	 * @param array $data, null to use $this->data
	 * @return string $xml
	 */
	public function create($data, $xmlbox = "", $layer = 0, $cdata = true)
	{
		if( empty($data) || ! is_array($data) ) {
			return false;
		}
		
		$xml = "";
		if( !empty($xmlbox) ) {
			$xml .= str_repeat("\t",$layer)."<".$xmlbox.">\r\n";
		}
		foreach( $data as $key=>$value )
		{
			++$layer;
			if( is_array($value) && array_key_exists(0, $value) ) {
				foreach( $value as $k=>$v )
				{
					$xml .= $this->create($v, $key, $layer, $cdata);
				}
			} elseif( is_array($value) ) {
				$xml .= $this->create($value, $key, $layer, $cdata);
			} elseif( $cdata ) {
				$xml .= str_repeat("\t",$layer).'<'.$key.'><![CDATA['.trim($value).']]></'.$key.'>'."\r\n";
			} else {
				$xml .= str_repeat("\t",$layer).'<'.$key.'>'.trim($value).'</'.$key.'>'."\r\n";
			}
			$layer--;
		}
		if( ! empty($xmlbox) ) {
			$xml .= str_repeat("\t",$layer)."</".$xmlbox.">\r\n";
		}
		
		return $xml;
	}
	
	/**
	 * Parse xml document to array.
	 *
	 * @param string $xml
	 * @param string $type
	 * @return array
	 */
	public function parse($xml, $type = "string")
	{
		if( $type == "file" ) {
			$obj = simplexml_load_file($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		} else {
			$obj = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
		}
		if( $obj == false ) return false;
		$array = (array)$obj;
		foreach ($array as $key=>$item)
		{
			$array[$key]  =  $this->objectToArray($item);
		}
		return $array;
	}
	
	/**
	 * Change xml object to array.
	 *
	 * @param object(array) $object
	 * @return array $return
	 */
	public function objectToArray($object)
	{
		$return = NULL;
		
		if( is_array($object) ) {
			foreach($object as $key => $value) {
				$return[$key] = $this->objectToArray($value);
			}
		} elseif( is_object($object) ) {
			$var = get_object_vars($object);
			if( $var ) {
				foreach($var as $key => $value) {
					$return[$key] = $this->objectToArray($value);
				}
			} else {
				$return = $object;
			}
		} else {
		    $return = $object;
		}
		
		return $return;
	}
	
	/**
	 * Create xml header and return it.
	 *
	 * @return string
	 */
	public function getHeader()
	{
		return '<?xml version="1.0" encoding="'.$this->encode.'"?>'."\r\n\r\n";
	}
}
