<meta charset="utf8" />
<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/17 0017
 * Time: 上午 11:08
 */
//引入自动加载文件
require 'vendor/autoload.php';
use QL\QueryList;
use Medoo\Medoo;

// 初始化配置 连接数据库
$database = new medoo([
    'database_type' => 'mysql',
    'database_name' => 'jingdian',
    'server' => 'localhost',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8'
]);

$html = <<<STR
STR;

for ($i=57;$i>0;$i--){
    $url = 'http://www.mafengwo.cn/ajax/router.php';
    $post_data['sAct']       = 'KMdd_StructWebAjax|GetPoisByTag';
    $post_data['iMddid']      = '10208';
    $post_data['iTagId'] = '0';
    $post_data['iPage']    = $i;

    $res = request_post($url, $post_data);
    $data = json_decode($res,true);
    $html = $data['data']['list'];


    $rules = array(
    'title' => array('li h3','text'),
    'pic' => array('li a .img img','src'),
    'detail_url' => array('li a','href'),
);

// 过程:设置HTML=>设置采集规则=>执行采集=>获取采集结果数据
$data = QueryList::html($html)->rules($rules)->query()->getData();
//打印结果
$res = $data->all();

foreach ($res as $k=>$v){
    $res[$k]['detail_url'] = 'http://www.mafengwo.cn'.$v['detail_url'];
}

//var_dump($res);
$list_data = $res;
//通过列表页获取详情页的内容
foreach ($list_data as $key=>$v){
    echo "开始获取《{$list_data[$key]['title']}》的详情\r\n";
    $detail_rule = [
        'tel'=>['body > div.container > div:nth-child(6) > div.mod.mod-detail > ul > li.tel > div.content','text'],
        'content'=>['body > div.container > div:nth-child(6) > div.mod.mod-detail > div','html'],
        'time'=>['body > div.container > div:nth-child(6) > div.mod.mod-detail > dl:nth-child(5) > dd','text'],
        'address'=>['body > div.container > div:nth-child(6) > div.mod.mod-location > div.mhd > p','text']
    ];

    $detail_data = @crawl_data($v['detail_url'],$detail_rule);


    //组合数据
    $db_data = [];
    $db_data['title'] = $list_data[$key]['title'];
    //$db_data['price'] = $list_data[$key]['price'];
    $db_data['pic'] = $list_data[$key]['pic'];
    //$db_data['type'] = $list_data[$key]['type'];
    $db_data['address'] = $detail_data[0]['address'];
    $db_data['tel'] = $detail_data[0]['tel'];
    $db_data['content'] = $detail_data[0]['content'];

    echo "开始写入数据库...\r\n";
    //写入数据库
    $GLOBALS['database']->insert('news',$db_data);
    $res_id = $GLOBALS['database']->id();
    if($res_id){
        echo "写入数据库成功！\n";
    }else{
        exit('失败');

    }

}

}

function crawl_data($url,$rule){
    return QueryList::get($url)->rules($rule)->query()->getData()->all();

}


/**
 * 模拟post进行url请求
 * @param string $url
 * @param string $param
 */
function request_post($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}