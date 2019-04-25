<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 17:48
 * @title 控制器基类
 */
namespace pizepei\staging;
use Whoops\Run;
class Controller
{
    /**
     * 初始化
     */
    public function __construct()
    {
        /**
         * 权限类
         */
        //路由
        $Route = Route::init();
        /**
         * 判断是否有设置权限控制器
         */
        if(isset($Route->baseAuth[0]) && isset($Route->baseAuth[1]) && $Route->baseAuth[1] !='public')
        {
            $className = 'authority\\'.__APP__.'\\controller\\'.$Route->baseAuth[0];
            $functionName = $Route->baseAuth[1];
            $class = new $className('common');
            $authResult = $class->init($Route->baseAuth[1]);
            $this->authExtend = $class->authExtend;
            $this->Payload = $class->Payload;
            //var_dump($this->Payload);
        }
        /**
         * 路由
         */


        /**
         * 包含
         */

    }

    /**
     * 获取Extend
     * @param string $key
     * @return mixed
     */
    public function getAuthExtend($key ='')
    {
        return $this->jurisdictionExtend[$key]??null;
    }

    /**
     * 视图
     * @param string $name
     * @param array  $data
     */
    public function view($name = '',array$data = [])
    {
        require(__TEMPLATE__.$name.'.html');
    }

    /**
     * @Author pizepei
     * @Created 2019/2/15 23:14
     *
     * @Author pizepei
     * @Created 2019/2/15 23:02
     *
     * @param     $data
     * @param     $msg 状态说明
     * @param     $code 状态码
     * @param int $count sss
     *
     * @return array
     * @title  控制器成功返回
     */

    public function succeed($data,$msg=__INIT__['SuccessReturnJsonMsg']['value'],$code=__INIT__['SuccessReturnJsonCode']['value'],$count=0)
    {
        $result =  [
            __INIT__['SuccessReturnJsonMsg']['name']=>$msg,
            __INIT__['SuccessReturnJsonCode']['name']=>$code,
            __INIT__['ReturnJsonData']=>$data,
        ];
        if($count>0){
            $result[__INIT__['ReturnJsonCount']] = $count;
        }else{
            $result[__INIT__['ReturnJsonCount']] = is_array($data)?count($data):0;
        }
        return $result;
    }

    /**
     * @Author pizepei
     * @Created 2019/2/15 23:09
     *
     * @param $data 错误详细信息
     * @param $msg  错误说明
     * @param $code  错误代码
     * @return array
     * @title  控制器错误返回
     */
    public function error($data,$msg=__INIT__['ErrorReturnJsonMsg']['value'],$code=__INIT__['ErrorReturnJsonCode']['value'])
    {
        $result =  [
            __INIT__['ErrorReturnJsonMsg']['name']=>$msg,
            __INIT__['ErrorReturnJsonCode']['name']=>$code,
            __INIT__['ReturnJsonData']=>$data,
        ];
        return $result;
    }




}