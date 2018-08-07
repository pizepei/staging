<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 16:06
 * @title 路由
 */

namespace pizepei\staging;
use pizepei\config\Config;


class Route
{
    /**
     * 当前对象
     * @var null
     */
    private static $object = null;

    protected $Config = null;
    /**
     * 当前请求的控制器
     * @var string
     */
    protected $controller = '';
    /**
     * 当前请求的模块
     * @var string
     */
    protected $module = '';
    /**
     * 当前请求的方法
     * @var string
     */
    protected $method = '';
    /**
     * 当前路径
     * @var string
     */
    protected $atpath = '';

    /**
     * 当前路由
     * @var string
     */
    protected $atroute = '';
    /**
     * 路由数据
     * @var null
     */
    protected $routeData = null;




    /**
     *构造方法
     */
    protected function __construct()
    {
//        $this->getRouteConfig();

        $s = isset($_GET['s'])?$_GET['s']:'/'.__ROUTE__['expanded'];
        $this->atroute = &$s;
        unset($_GET['s']);
        /**
         * 获取路由
         */
        $routeData = require('../'.__APP__.'/route/index.php');
        /**
         * 获取到
         */
        if(__ROUTE__['expanded'] != ''){
            $sstr = strrchr($s,'.');

            if($sstr != __ROUTE__['expanded'] ){
                /**
                 * 如果路由没有expanded
                 */
                echo $s = '/'.__ROUTE__['expanded'];
            }
        }
        /**
         * 请求类型
         */
        $_SERVER['REQUEST_METHOD'];
        /**
         * 请求url
         */
        $_SERVER['REQUEST_URI'];
        /**
         * 请求的参数
         * s 为路由
         */
        $_SERVER['QUERY_STRING'];
        /**
         * 判断路由是否存在
         */
        $rtrim = str_replace(__ROUTE__['expanded'],'',$s);

        if(isset($routeData[$rtrim])){
            /**
             * 判断请求类型
             */

            if($routeData[$rtrim][2] != 'all'){
                if($routeData[$rtrim][2] != $_SERVER['REQUEST_METHOD']) {
                    echo  '不存在的请求';
                    exit;
                }
            }
            /**
             * 获取类命名空间
             */

            $atpath = __APP__.'\\'.$routeData[$rtrim][0];
            /**
             * 实例化控制器
             */
            $new = new $atpath;
            $this->atpath = &$atpath;

            $method = &$routeData[$rtrim][1];
            $this->method = &$method;
            /**
             * 控制器方法
             */
            $new->$method();

        }else{
            echo '不存在';
            exit;

        }
    }

    /**
     * 启动请求转移
     */
    protected function begin()
    {



    }

    /**
     * 获取路由配置
     */
    protected function getRouteConfig()
    {
        /**
         * 获取路由配置
         * ['expanded']
         */

        /**
         * 打开route目录
         */


        /**
         * 打开
         */

    }



    /**
     * 初始化
     */
    public static  function init()
    {
        /**
         * 判断是否已经有这个对象
         */
        if(static::$object != null){
            return static::$object;
        }
        $New = new static();
        return $New;
    }

}