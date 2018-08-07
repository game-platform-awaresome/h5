<?php

/**
 * 自动更新网站地图
 */
function sitemap()
{
    $domain = 'www.namiyx.com';
    $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml[] = '<urlset>';
    
    //基础地址
    $base = "http://{$domain}";
    //一级链接
    $data = array(
        "{$base}/",
        "{$base}/game/index.html",
        "{$base}/game/center.html",
        "{$base}/info/index.html",
        "{$base}/activity/index.html",
    );
    $date = date('Y-m-d');
    foreach ($data as $url)
    {
        $xml[] = "\t<url>";
        $xml[] = "\t\t<loc>{$url}</loc>";
        $xml[] = "\t\t<lastmod>{$date}</lastmod>";
        $xml[] = "\t\t<changefreq>hourly</changefreq>";
        $xml[] = "\t\t<priority>1.0</priority>";
        $xml[] = "\t</url>";
    }
    
    //一级分类
    $pre = "{$base}/game/tc.html?tc=";
    $m_game = new GameModel();
    $types = $m_game->_classic;
    $types[] = '精品推荐';
    $types[] = '独家首发';
    $types[] = '变态版本';
    $types[] = '网络游戏';
    $types[] = '单机游戏';
    foreach ($types as $tp)
    {
        $xml[] = "\t<url>";
        $xml[] = "\t\t<loc>{$pre}{$tp}</loc>";
        $xml[] = "\t\t<lastmod>{$date}</lastmod>";
        $xml[] = "\t\t<changefreq>daily</changefreq>";
        $xml[] = "\t\t<priority>1.0</priority>";
        $xml[] = "\t</url>";
    }
    
    //最热门的100个游戏
    $pre = "{$base}/game/detail.html?game_id=";
    $games = $m_game->fetchAll("visible=1", 1, 100, 'game_id,add_time', 'weight ASC,game_id DESC');
    foreach ($games as $row)
    {
        $date = substr($row['add_time'], 0, 10);
        $xml[] = "\t<url>";
        $xml[] = "\t\t<loc>{$pre}{$row['game_id']}</loc>";
        $xml[] = "\t\t<lastmod>{$date}</lastmod>";
        $xml[] = "\t\t<changefreq>weekly</changefreq>";
        $xml[] = "\t\t<priority>0.9</priority>";
        $xml[] = "\t</url>";
    }
    
    //最新的100篇资讯
    $pre = "{$base}/info/detail.html?article_id=";
    $m_article = new ArticleModel();
    $news = $m_article->fetchAll('visible=1', 1, 100, 'article_id,up_time', 'weight ASC,article_id DESC');
    foreach ($news as $row)
    {
        
        $date = date('Y-m-d', $row['up_time']);
        $xml[] = "\t<url>";
        $xml[] = "\t\t<loc>{$pre}{$row['article_id']}</loc>";
        $xml[] = "\t\t<lastmod>{$date}</lastmod>";
        $xml[] = "\t\t<changefreq>monthly</changefreq>";
        $xml[] = "\t\t<priority>0.8</priority>";
        $xml[] = "\t</url>";
    }
    
    $xml[] = '</urlset>';
    $xml = implode("\n", $xml);
    
    $file = APPLICATION_PATH.'/public/sitemap.xml';
    file_put_contents($file, $xml);
}
