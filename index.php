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
    'database_name' => 'qunar',
    'server' => 'localhost',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8'
]);

//写一个主函数来实现采集网页数据
function index(){
    //获取前20页的数据
    for($i=1;$i<2;$i++){
        $url = "http://www.tuniu.com/zhoubian/shanshui/tours-cq-0/list-h0-i-j0_0/{$i}/";
        $list_rule = [
            'title'=>['#niuren_list .title','text'],
            'price'=>['#niuren_list .tnPrice > em','text'],
            'pic'=>['#niuren_list .img > img','src'],
            'detail_url'=>['#niuren_list ul > li:nth-child(1) > div > a','href']
        ];
        $list_data = @crawl_data($url,$list_rule)->all();


        //通过列表页获取详情页的内容
        foreach ($list_data as $key=>$v){
            $detail_rule = [
                'content'=>['.detail-feature > div.section-box-body','html']
            ];

            $detail_data = @crawl_data($v['detail_url'],$detail_rule)->all();

            //组合数据
            $db_data = [];
            $db_data['title'] = $list_data[$key]['title'];
            $db_data['price'] = $list_data[$key]['price'];
            $db_data['pic'] = $list_data[$key]['pic'];
            //$db_data['content'] = $detail_data[0]['content'];


            //写入数据库
            $res = $GLOBALS['database']->insert('news',$db_data);


        }

    }






}
//querylist 爬取数据的函数
function crawl_data($url,$rule){
    return QueryList::get($url)->rules($rule)->query()->getData();

}
index();
