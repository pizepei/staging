<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 15:47
 * @title 脚手架启动文件
 */
namespace pizepei\staging;
use pizepei\staging\Route;
use pizepei\staging\Request;
use pizepei\config\Config;
class Start
{
    /**
     * 加载配置
     *      判断是否有有效缓存
     *      获取模块目录下路由配置、常规配置、数据库配置
     *      合并配置 进行缓存
     *
     * 对配置进行判断实现配置
     *      环境检测
     *      判断是否是开发模式
     *      默认入口路由
     *
     *
     *注册异常处理日志处理
     *
     *启动路由
     *
     */
    /***
     * Start constructor.
     */
    public function __construct($environment = 'exploit')
    {
        /**
         * 获取配置
         * 判断环境
         */

        if(PHP_VERSION <= 7){
            exit('PHP版本必须<=7,当前版本'.PHP_VERSION);
        }
        /**
         * 服务器版本
         */
       // php_uname('s').php_uname('r');
        /**
         * 最大执行时间
         *
         */
        //get_cfg_var("max_execution_time")."秒 ";

        /**
         * 获取配置到define;
         */
        define('__INIT__',Config::UNIVERSAL['init']);//初始化配置

        define('__ROUTE__',Config::UNIVERSAL['route']);//路由配置

        /**
         * 判断模式
         */
        if(__INIT__['pattern'] == 'exploit'){
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        }else{
            // 关闭所有PHP错误报告
            error_reporting(0);
        }
        /**
         * 请求类
         */
        Request::init();
        /**
         * 简单路由
         */
        $Route = Route::init();

    }


    /**
     * 基本初始化
     */
    protected function init()
    {


    }

    /**
     * 开始
     */
    public function start()
    {


    }





}