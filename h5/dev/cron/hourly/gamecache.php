<?php

/**
 * 更新游戏首字母缓存
 * 与后台工具 游戏管理->更新缓存 是一样的，只不过这是个自动任务
 */
function gamecache()
{
//    $m_search = new GameSearchModel();
//    $start = 0;
//    $limit = 100;
//    $data = array();
//    while (1)
//    {
//        $tmp = $m_search->fetchAllBySql("SELECT s.game_id,g.name,s.initial,s.visible,g.prepay FROM game_search AS s
//            LEFT JOIN game AS g ON s.game_id=g.game_id
//            WHERE s.initial<>'' AND s.game_id>{$start} ORDER BY s.initial ASC,s.weight ASC LIMIT {$limit}");
//        if( empty($tmp) ) {
//            break;
//        }
//        foreach ($tmp as $row)
//        {
//            $data[$row['initial']][] = $row;
//        }
//        if( count($tmp) < $limit ) {
//            break;
//        }
//        $start = $row['game_id'];
//    }
//    $file = APPLICATION_PATH."/application/cache/game/initial.php";
//    $content = '<?php';
//    $content .= "\r\n\r\nreturn ";
//    $content .= var_export($data, true);
//    $content .= ";\r\n";
//    file_put_contents($file, $content);
}
