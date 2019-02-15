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
     * 成功
     */
    public function succeed($data,$msg='succeed',$count=0,$code=__INIT__['SuccessReturnsJsonCode']['value'])
    {
        $result =  [
            __INIT__['ReturnJsonData']=>$data,
            __INIT__['SuccessReturnsJsonCode']['name']=>$code
        ];
        if($count>0){
            $result[__INIT__['SuccessReturnsJsonCode']] = $count;
        }

        return $result;
    }
    /**
     *
     */
    public function error()
    {

    }




}