<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/18 0018
 * Time: 上午 10:14
 */
require 'vendor/autoload.php';
use QL\QueryList;
use Medoo\Medoo;

// 初始化配置 连接数据库
$database = new medoo([
    'database_type' => 'mysql',
    'database_name' => 'news_db',
    'server' => 'localhost',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8'
]);
function index(){
    echo "开始\n\r";
    for($i=1;$i<2;$i++){
        echo "第{$i}页：\n\r";
        $list_url = "http://top.jobbole.com/page/{$i}/";
        echo $list_url."\n\r";
        //列表页规则
        $list_rule = [
            'title'=>['.left-content h3 a','text'],
            'add_time'=>['.left-content p > span:nth-child(1)','text'],
            'detail_url'=>['.left-content h3 a','href']
        ];
        $list_data = crawl_data($list_url,$list_rule);

        foreach ($list_data as $k=>$v){
            echo "开始获取《{$list_data[$k]['title']}》的详情";
           //内容页规则
            $detail_rule = [
                'content'=>['#artibody','html']
            ];
            $detail_data = crawl_data($v['detail_url'],$detail_rule);
            var_dump($v['detail_url']);
            exit;
            //组装数据
            $db_data =[];
            $db_data['title']=$list_data[$k]['title'];
            $db_data['add_time']=$list_data[$k]['add_time'];
            $db_data['content'] = $detail_data[0]['content'];

            echo "开始写入数据库\n\r";
            $GLOBALS['database']->insert('news',$db_data);
            $res_id = $GLOBALS['database']->id();
            if($res_id){
                echo "写入数据库成功！\n\r";
            }else{
                echo "失败";
                die();
            }

        }


    }
    echo "结束";
}

function crawl_data($url,$rule){
    return QueryList::get($url)->rules($rule)->query()->getData()->all();
}
index();