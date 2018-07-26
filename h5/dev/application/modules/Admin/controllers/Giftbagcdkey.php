<?php

class GiftbagcdkeyController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['op'] = F_Helper_Html::Op_Null;
        return $params;
    }
    
    public function editAction()
    {
        exit;
    }
    
    public function updateAction()
    {
        exit;
    }
    
    public function deleteAction()
    {
        exit;
    }
    
    public function exportAction()
    {
        $gift_id = $this->getRequest()->get('gift_id', 0);
        
        $conds = "gift_id='{$gift_id}'";
        $pn = 1;
        $limit = 1000;
        
        $data = "兑换码,礼包ID,用户ID\r\n";
        while (1)
        {
            $tmp = $this->_model->fetchAll($gift_id, $pn, $limit, 'cdkey,user_id');
            if( empty($tmp) ) break;
            foreach ($tmp as $row)
            {
                $data .= "{$row['cdkey']},{$gift_id},{$row['user_id']}\r\n";
            }
            if( count($tmp) < $limit ) {
                break;
            }
            ++$pn;
        }
        
        header('Expires: 0');
        header('Content-Type: application/force-download');
        header('Content-Transfer-Encoding: binary');
        header('Cache-control: no-cache');
        header('Pragma: no-cache');
        header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: '.strlen($data));
        header("Content-Disposition: attachment; filename=\"giftbag-{$gift_id}-cdkey.csv\"");
        echo $data;
        exit;
    }
}
