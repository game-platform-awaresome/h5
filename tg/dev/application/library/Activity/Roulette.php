<?php

class Activity_Roulette extends Activity_Abstract
{
    /**
     * multi: 重复性多选项，每项设置一样，但是不确定项数
     * fixed: 固定选项，每项设置均可不一样
     * 
     * @var string
     */
    public $type = 'multi';
    
    /**
     * 统一使用odds来表示中奖几率
     * 
     * @var array
     */
    public $config = array(
        'name' => array('奖项名称', 'input'),
        'bold' => array('名称加粗部分', 'input'),
        'odds' => array('中奖几率（万分之）', 'input'),
        'code' => array('奖品代码', 'input'),
        'nums' => array('奖品数量', 'input'),
    );
}
