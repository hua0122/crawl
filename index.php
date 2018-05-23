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

//写一个主函数来实现采集网页数据
function index(){
    echo "开始 \r\n";
    //获取前20页的数据
    for($i=48;$i<51;$i++){
        echo "开始获取第{$i}页的数据:\r\n";
        $url = "http://www.dianping.com/chongqing/ch35/p{$i}";
        echo $url."\r\n";
        $list_rule = [
            'title'=>['#shop-all-list .txt > div.tit > a > h4','text'],

            //'price'=>['#niuren_list .tnPrice > em','text'],
            'type'=>['#shop-all-list .txt > div.tag-addr > a:nth-child(1) > span','text'],
            'address'=>['#shop-all-list div.txt > div.tag-addr > span','text'],
            'pic'=>['#shop-all-list div.pic > a > img','data-src'],
            'detail_url'=>['#shop-all-list div.pic > a','href']
        ];

        $list_data = @crawl_data($url,$list_rule);



        //通过列表页获取详情页的内容
        foreach ($list_data as $key=>$v){
            echo "开始获取《{$list_data[$key]['title']}》的详情\r\n";
            $detail_rule = [
                'tel'=>['.tel .item','text'],
                //'content'=>['#reviewlist-wrapper','html']
            ];

            $detail_data = @crawl_data($v['detail_url'],$detail_rule);


            //组合数据
            $db_data = [];
            $db_data['title'] = $list_data[$key]['title'];
            //$db_data['price'] = $list_data[$key]['price'];
            $db_data['pic'] = $list_data[$key]['pic'];
            $db_data['type'] = $list_data[$key]['type'];
            $db_data['address'] = $list_data[$key]['address'];
            //$db_data['tel'] = $detail_data[0]['tel'];
            //$db_data['content'] = $detail_data[0]['content'];

            echo "开始写入数据库...\r\n";
            //写入数据库
            $GLOBALS['database']->insert('news',$db_data);
            $res_id = $GLOBALS['database']->id();
            if($res_id){
                echo "写入数据库成功！\n";
            }else{
                echo "失败！";
            }



        }

    }

}
//querylist 爬取数据的函数
function crawl_data($url,$rule){
    return QueryList::get($url)->rules($rule)->query()->getData()->all();

}
index();
