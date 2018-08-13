<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13/013
 * Time: 10:29
 */

class F_Helper_Csv
{
    public function __construct()
    {

    }

    function input_csv($handle)
    {
        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 10000))
        {
            $num = count($data);
            for ($i = 0; $i < $num; $i++)
            {
                $out[$n][$i] =  iconv('gb2312','utf-8',$data[$i]);
            }
            $n++;
        }
        return $out;
    }
}