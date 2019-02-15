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
    /**
     * 权限控制
     */

    public function __construct()
    {
        /**
         * 权限类
         */

        /**
         * 路由
         */


        /**
         * 包含
         */

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
     * @Created 2019/2/15 23:02
     *
     * @param     $data 需要返回的数据
     * @param     $msg 状态说明
     * @param     $code 状态码
     * @param int $count 数据数量
     * @return array
     * @title  控制器成功返回
     *
     */
    public function succeed($data,$msg=__INIT__['SuccessReturnJsonMsg']['value'],$code=__INIT__['SuccessReturnsJsonCode']['value'],$count=0)
    {
        $result =  [
            __INIT__['SuccessReturnJsonMsg']['name']=>$msg,
            __INIT__['SuccessReturnsJsonCode']['name']=>$code,
            __INIT__['ReturnJsonData']=>$data,
        ];
        if($count>0){
            $result[__INIT__['ReturnJsonCount']] = $count;
        }else{
            $result[__INIT__['ReturnJsonCount']] = count($data);
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
    public function error($data,$msg=__INIT__['ErrorReturnJsonMsg']['value'],$code=__INIT__['ErrorReturnsJsonCode']['value'])
    {
        $result =  [
            __INIT__['SuccessReturnJsonMsg']['name']=>$msg,
            __INIT__['SuccessReturnsJsonCode']['name']=>$code,
            __INIT__['ReturnJsonData']=>$data,
        ];
        return $result;
    }




}