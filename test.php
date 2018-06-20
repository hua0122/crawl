<meta charset="utf8" />
<?php
set_time_limit(0);
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

for ($i=3;$i<69;$i++){
    $url = 'http://www.mafengwo.cn/ajax/router.php';
    $post_data['sAct']       = 'KMdd_StructWebAjax|GetPoisByTag';
    $post_data['iMddid']      = '10208';
    $post_data['iTagId'] = '0';
    $post_data['iPage']    = $i;
    echo "当前第{$i}页\r\n";

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

/*
foreach ($res as $k=>$v){
    $res[$k]['detail_url'] = 'http://www.mafengwo.cn'.$v['detail_url'];
}*/

//var_dump($res);
$list_data = $res;
//通过列表页获取详情页的内容
foreach ($list_data as $key=>$v){
    echo "开始获取《{$list_data[$key]['title']}》的详情\r\n";
    $detail_rule = [
        'tel'=>['body > div.container > div:nth-child(6) > div.mod.mod-detail > ul > li.tel > div.content','text'],
        'content'=>['body > div.container > div:nth-child(6) > div.mod.mod-detail > div','html'],
        'time'=>['body > div.container > div:nth-child(6) > div.mod.mod-detail > dl:nth-child(5) > dd','text'],
        'address'=>['body > div.container > div:nth-child(6) > div.mod.mod-location > div.mhd > p','text'],
        'detail_pic_url'=>['body > div.container > div:nth-child(6) > div.row.row-picture.row-bg > div > a','href']
    ];

    $detail_data = @crawl_data('http://www.mafengwo.cn'.$v['detail_url'],$detail_rule);
    //var_dump($list_data);

    //var_dump(strpos($list_data[$key]['detail_url'],'.'));
    //var_dump(strripos($list_data[$key]['detail_url'],'/'));
    echo $poiid=substr($list_data[$key]['detail_url'],strripos($list_data[$key]['detail_url'],'/')+1,strpos($list_data[$key]['detail_url'],'.')-strripos($list_data[$key]['detail_url'],'/')-1);


    //获取详情页的图片列表 poiid 变化
    $url = 'http://www.mafengwo.cn/mdd/ajax_photolist.php?act=getPoiPhotoList&poiid='.$poiid.'&page=1';
    echo $url;
    $html1 = file_get_contents($url);
    $rules1 = array(
        'pics' => array('li a img','src')
    );
    $pic_data = QueryList::html($html1)->rules($rules1)->query()->getData()->all();
    foreach ($pic_data as $k=>$pic){
        //echo strpos($pic['pics'],'?');
        $pic_data[$k]['pics'] = substr($pic['pics'],0,strpos($pic['pics'],'?'));

    }
    foreach ($pic_data as $k=>$pic){

        //创建文件写入图片地址
        create_file($i.'-'.$key,$pic['pics']."\r\n");
        //下载图片
        download($pic['pics'],$i.'-'.$key.'/');

    }





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
        $GLOBALS['database']->insert('news', $db_data);
        $res_id = $GLOBALS['database']->id();
        if ($res_id) {
            echo "写入数据库成功！\r\n";
        } else {
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

//创建文件写入图片地址
function create_file($filename,$StrConents){
    //创建文件夹
    @mkdir($filename);
    //要创建的文件
    $TxtFileName = $filename.'/'.$filename.".txt";
    //以读写方式打写指定文件，如果文件不存则创建
    if( ($TxtRes=fopen ($TxtFileName,"a+")) === FALSE){
        echo("创建可写文件：".$TxtFileName."失败");
        exit();
    }
    echo ("创建可写文件".$TxtFileName."成功！</br>");
    //$StrConents = "Welcome To ItCodeWorld!";//要 写进文件的内容
    if(!fwrite ($TxtRes,$StrConents)){ //将信息写入文件
        echo ("尝试向文件".$TxtFileName."写入".$StrConents."失败！");
        fclose($TxtRes);
        exit();
    }
    echo ("尝试向文件".$TxtFileName."写入".$StrConents."成功！");
    fclose ($TxtRes); //关闭指针
}

//下载图片到本地
function download($url, $path = 'images/')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $file = curl_exec($ch);
    curl_close($ch);
    $filename = pathinfo($url, PATHINFO_BASENAME);
    $resource = fopen($path . $filename, 'a');
    fwrite($resource, $file);
    fclose($resource);
}