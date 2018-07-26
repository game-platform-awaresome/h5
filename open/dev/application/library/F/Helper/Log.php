<?php

class F_Helper_Log
{
    private $fp;
    
    public function __construct($file = '')
    {
        if( empty($file) ) {
            $file = APPLICATION_PATH.'/logs/error.log';
        }
        $this->fp = fopen($file, 'ab');
    }
    
    public function debug($err)
    {
        if( $this->fp ) {
            fwrite($this->fp, $err);
        }
    }
    
    public function __destruct()
    {
        if( $this->fp ) {
            fclose($this->fp);
        }
    }
}
