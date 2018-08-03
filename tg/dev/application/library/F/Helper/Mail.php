<?php
/**
 * 邮件发送
 * based on discuz sendmail.inc.php
 */
class F_Helper_Mail
{
    public $delimiter = "\r\n";
    public $charset = 'UTF-8';
    public $send_by = 'smtp';
    public $use_nick = true;
    public $mailer = 'dxj';
    
    public $smtp_host = '';
    public $smtp_port = 25;
    public $smtp_user = '';
    public $smtp_pass = '';
    
    public $from_name = '久乐游戏客户服务';
    public $from_email = 'service@zchljoy.com';
    
    /**
     * 使用Yaf的配置来配置当前参数
     */
    public function useYafConf()
    {
        $conf = Yaf_Application::app()->getConfig();
        $conf = $conf['mail'];
        $this->send_by = $conf['send_by'];
        $this->mailer = $conf['mailer'];
        $this->smtp_host = $conf['smtp_host'];
        $this->smtp_port = $conf['smtp_port'];
        $this->smtp_user = $conf['smtp_user'];
        $this->smtp_pass = $conf['smtp_pass'];
        $this->from_name = $conf['from_name'];
        $this->from_email = $conf['from_email'];
    }
    
    /**
     * 发送邮件
     * 
     * @param string $to
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public function send($to, $subject, $message)
    {
        $subject = '=?'.$this->charset.'?B?'.base64_encode(str_replace("\r", '', str_replace("\n", '', $subject))).'?=';
        $message = chunk_split(base64_encode(str_replace("\r\n.", " \r\n..", str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message)))))));
        
        $email_from = $this->from_name ? '=?'.$this->charset.'?B?'.base64_encode($this->from_name)."?= <{$this->from_email}>" : $this->from_email;
        
        foreach(explode(',', $to) as $touser) {
            $tousers[] = preg_match('/^(.+?) \<(.+?)\>$/', $touser, $tmp) ? ($this->use_nick ? '=?'.$this->charset.'?B?'.base64_encode($tmp[1])."?= <$tmp[2]>" : $tmp[2]) : $touser;
        }
        $email_to = implode(',', $tousers);
        
        $headers = "From: {$email_from}{$this->delimiter}X-Priority: 3{$this->delimiter}X-Mailer: {$this->mailer}{$this->delimiter}MIME-Version: 1.0{$this->delimiter}Content-type: text/html; charset={$this->charset}{$this->delimiter}Content-Transfer-Encoding: base64{$this->delimiter}";
        
        if($this->send_by == 'sendmail' && function_exists('mail')) {
            return @mail($email_to, $subject, $message, $headers);
            
        } elseif($this->send_by == 'smtp') {
            //$log = new F_Helper_Log();
            //$log->debug("Send Start:\r\n");
            if(!$fp = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 10)) {
                //$log->debug($errstr."\r\n");
                return false;
            }
            stream_set_blocking($fp, true);
            
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != '220') {
                //$log->debug("connect $lastmessage\r\n");
                return false;
            }
            
            $need_auth = $this->smtp_user != '' && $this->smtp_pass != '';
            fputs($fp, ($need_auth ? 'EHLO' : 'HELO')." {$this->mailer}\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
                //$log->debug("hello $lastmessage\r\n");
                return false;
            }
            
            while(1) {
                if(substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
                    break;
                }
                $lastmessage = fgets($fp, 512);
            }
            
            if($need_auth) {
                fputs($fp, "AUTH LOGIN\r\n");
                $lastmessage = fgets($fp, 512);
                if(substr($lastmessage, 0, 3) != 334) {
                    //$log->debug("login $lastmessage\r\n");
                    return false;
                }
                
                fputs($fp, base64_encode($this->smtp_user)."\r\n");
                $lastmessage = fgets($fp, 512);
                if(substr($lastmessage, 0, 3) != 334) {
                    //$log->debug("user $lastmessage\r\n");
                    return false;
                }
                
                fputs($fp, base64_encode($this->smtp_pass)."\r\n");
                $lastmessage = fgets($fp, 512);
                if(substr($lastmessage, 0, 3) != 235) {
                    //$log->debug("pass $lastmessage\r\n");
                    return false;
                }
                
                $email_from = $this->from_email;
            }
            
            fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 250) {
                fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
                $lastmessage = fgets($fp, 512);
                if(substr($lastmessage, 0, 3) != 250) {
                    //$log->debug("mail_from $lastmessage\r\n");
                    return false;
                }
            }
            
            $email_tos = array();
            foreach(explode(',', $email_to) as $touser) {
                $touser = trim($touser);
                if($touser) {
                    fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $touser).">\r\n");
                    $lastmessage = fgets($fp, 512);
                    if(substr($lastmessage, 0, 3) != 250) {
                        //$log->debug("rcpt_to $lastmessage\r\n");
                        fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $touser).">\r\n");
                        $lastmessage = fgets($fp, 512);
                        return false;
                    }
                }
            }
            
            fputs($fp, "DATA\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 354) {
                //$log->debug("data $lastmessage\r\n");
                return false;
            }
            
            $headers .= 'Message-ID: <'.gmdate('YmdHs').'.'.substr(md5($message.microtime()), 0, 6).rand(100000, 999999).'@'.$_SERVER['HTTP_HOST'].">{$this->delimiter}";
            
            fputs($fp, "Date: ".gmdate('r')."\r\n");
            fputs($fp, "To: ".$email_to."\r\n");
            fputs($fp, "Subject: ".$subject."\r\n");
            fputs($fp, $headers."\r\n");
            fputs($fp, "\r\n\r\n");
            fputs($fp, "$message\r\n.\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 250) {
                //$log->debug("content $lastmessage\r\n");
                return false;
            }
            
            fputs($fp, "QUIT\r\n");
            return true;
            
        } elseif($this->send_by == 'mail') {
            ini_set('SMTP', $this->smtp_host);
            ini_set('smtp_port', $this->smtp_port);
            ini_set('sendmail_from', $email_from);
            
            return @mail($email_to, $subject, $message, $headers);
        } else {
            return false;
        }
    }
}
