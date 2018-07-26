<?php

class SignrewardController extends F_Controller_Backend
{
    protected function beforeList()
    {
        $params = parent::beforeList();
        $params['orderby'] = 'days ASC';
        return $params;
    }
    
    protected function beforeEdit(&$info)
    {
        if( $info ) {
            $info['reward'] = $this->_model->unserializeReward($info['reward']);
        }
        $this->getView()->assign('types', $this->_model->_types);
    }
    
    protected function beforeUpdate($id, &$info)
    {
        $info['reward'] = $this->_model->serializeReward($info['reward']);
    }
    
    protected function afterUpdate($id, &$info)
    {
        $this->cache();
    }
    
    protected function afterDelete()
    {
        $this->cache();
    }
    
    private function cache()
    {
        $list = $this->_model->fetchAll("disabled=0", 1, 20, '*', 'days ASC');
        foreach ($list as &$row)
        {
            $row['reward'] = empty($row['reward']) ? array() : unserialize($row['reward']);
        }
        
        $file = APPLICATION_PATH.'/application/cache/game/signreward.php';
        $data = '<?php';
        $data .= "\n\nreturn ";
        $data .= var_export($list, true);
        $data .= ";\n";
        file_put_contents($file, $data);
    }
}
