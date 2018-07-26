<?php
/**
 * 图片验证码
 */

class F_Helper_ImgCode
{
    const TYPE_NUMERIC = 1;
    const TYPE_CHARACTER_UPPER = 2;
    const TYPE_CHARACTER_LOWER = 4;
    const TYPE_CHARACTER_BOTH = 6;
    const TYPE_ALL = 7;

    public $width = 166;
    public $height = 70;
    public $w_pad = 20; //字体左侧基线
    public $h_pad = 20; //字体底部基线
    public $bg_file;
    public $bg_color = '1e2830';
    public $ft_file = 'verdana.ttf';
    public $ft_color = 'ffffff';
    public $ft_size = 36;
    public $length = 4;
    public $char_type = self::TYPE_ALL;
    
    public $img_type = 'jpeg';
    public $sess_name = 'img_code';
    
    public function create()
    {
        $dir = dirname(__FILE__).'/';
        $bg_file = $this->bg_file ? $dir.$this->bg_file : '';
        $ft_file = $this->ft_file ? $dir.$this->ft_file : 'verdana.ttf';
        
        $char_u = array('A','B','C','D','E','F','G','H','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z');
        $char_l = array('a','b','c','d','e','f','g','h','i','j','k','m','n','p','q','r','s','t','u','v','w','x','y','z');
        $char_n = array('2','3','4','5','6','7','8','9');
        switch ($this->char_type)
        {
            case self::TYPE_NUMERIC:
                $char_arr = $char_n;
                break;
            case self::TYPE_CHARACTER_UPPER:
                $char_arr = $char_u;
                break;
            case self::TYPE_CHARACTER_LOWER:
                $char_arr = $char_l;
                break;
            case self::TYPE_CHARACTER_BOTH:
                $char_arr = array_merge($char_u, $char_l);
                break;
            case self::TYPE_ALL:
            default:
                $char_arr = array_merge($char_u, $char_l, $char_n);
                break;
        }
        shuffle($char_arr);
        $xcode = '';
        
        $width_i = (int)($this->width - $this->w_pad*2)/$this->length;
        
        $padding_x = $this->w_pad;
        $padding_y = $this->height - $this->h_pad;
        
        $im = imagecreatetruecolor($this->width, $this->height);
        if( file_exists($bg_file) ) {
            $tmp = getimagesize($bg_file);
            switch ($tmp[2])
            {
                case 1: $bg_im = imagecreatefromgif($this->bg_file); break;
                case 2: $bg_im = imagecreatefromjpeg($this->bg_file); break;
                case 3: $bg_im = imagecreatefrompng($this->bg_file); break;
                case 6: $bg_im = imagecreatefromwbmp($this->bg_file); break;
            }
            if( isset($bg_im) ) {
                imagecopy($im, $bg_im, 0, 0, 0, 0, $tmp[0], $tmp[1]);
                imagedestroy($bg_im);
            }
        } else if( $this->bg_color ) {
            $color = imagecolorallocate($im, hexdec(substr($this->bg_color, 0, 2)), hexdec(substr($this->bg_color, 2, 2)), hexdec(substr($this->bg_color, 4, 2)));
            imagefilledrectangle($im, 0, 0, $this->width, $this->height, $color);
        }
        
        $color = imagecolorallocate($im, hexdec(substr($this->ft_color, 0, 2)), hexdec(substr($this->ft_color, 2, 2)), hexdec(substr($this->ft_color, 4, 2)));
        
        for($i = 0; $i < $this->length; ++$i)
        {
            $angle = mt_rand(-30, 30);
            imagettftext($im, $this->ft_size, $angle, $padding_x + ($width_i * $i), $padding_y, $color, $ft_file, $char_arr[$i]);
            $xcode .= $char_arr[$i];
        }
    
        header("Expires: Sat, 1 Jan 2000 08:00:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);      // HTTP/1.0
        header("Pragma: no-cache");
        switch ($this->img_type)
        {
            case 'gif':
                header("Content-type: image/gif");
                imagegif($im);
                break;
            case 'png':
                header("Content-type: image/png");
                imagepng($im);
                break;
            case 'jpg':
            case 'jpeg':
            default:
                header("Content-type: image/jpeg");
                imagejpeg($im);
                break;
        }
        imagedestroy($im);
    
        Yaf_Session::getInstance()->set($this->sess_name, $xcode);
    }
    
    /**
     * 校验图片验证码
     * 
     * @param string $code
     * @param bool $ignore
     * @return bool
     */
    public function check($code, $ignore = true)
    {
        $xcode = Yaf_Session::getInstance()->get($this->sess_name);
        if( empty($xcode) || empty($code) ) {
            return false;
        }
        if( strlen($xcode) != strlen($code) ) {
            return false;
        }
        if( $ignore ) {
            $xcode = strtoupper($xcode);
            $code = strtoupper($code);
        }
        return strcmp($xcode, $code) == 0 ? true : false;
    }
}
