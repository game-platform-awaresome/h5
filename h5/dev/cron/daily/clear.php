<?php

/**
 * 清除数据库临时数据及无用数据
 */
function clear()
{
    //清除未支付订单，1天以前
    $time = strtotime('-1 days');
    $m_pay = new PayModel();
    $m_pay->delete("pay_time=0 AND finish_time=0 AND add_time<{$time}");
    
    //清除过期登录日志，半月以前
    $m_signlog = new SignonlogModel();
    $m_signlog->delete("time<{$time}");
    
    //重置短信验证码发送日志
    $m_sms_m = new SmscodeMobileModel();
    $m_sms_m->clear();
    
    //重置邮箱验证码发送日志
    $m_xc_em = new XcodeEmailModel();
    $m_xc_em->clear();
    
    //清理过期的签到活动记录
    $ymd = date('Ymd', strtotime('-30 days'));
    $m_book = new SignbookModel();
    $m_book->delete("ymd<{$ymd}");
    
    $ymd = date('Ymd', $time + 86400);
    $m_fcmlog = new UserfcmlogModel();
    $m_fcmlog->delete("day<{$ymd}");
}
