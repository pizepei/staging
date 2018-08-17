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
     * 当前路径(控制器)
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
    protected $routeData = array();
    /**
     * 当前路由数据
     * @var array
     */
    protected $routeArr = array();

    /**
     *构造方法
     */
    protected function __construct()
    {
        /**
         * 获取路由
         */
        $this->getRouteConfig();

        $s = isset($_GET['s'])?$_GET['s']:'/'.__ROUTE__['expanded'];
        $this->atroute = $s;

        unset($_GET['s']);

        /**
         * 获取到
         */
        if(__ROUTE__['expanded'] != ''){
            $sstr = strrchr($s,'.');

            if($sstr != __ROUTE__['expanded'] ){
                /**
                 * 如果路由没有expanded
                 */
                 $s = '/'.__ROUTE__['expanded'];
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
        $this->atroute = str_replace(__ROUTE__['expanded'],'',$this->atroute);

        if(isset($this->routeData[$this->atroute])){
            /**
             * 获取控制器权限数据
             */
            $this->routeArr = &$this->routeData[$this->atroute];
            /**
             *
             * 判断请求类型
             */
            if(strtoupper($this->routeData[$this->atroute][2]) != 'ALL'){
                if(strtoupper($this->routeData[$this->atroute][2]) != $_SERVER['REQUEST_METHOD']) {
                    throw new \Exception('不存在的请求');
                }
            }
            /**
             * 获取类命名空间
             */
            $this->controller = &$this->routeData[$this->atroute][0];
            $this->atpath = '\\'.__APP__.'\\'.$this->controller;
            /**
             * 获取控制器方法
             */
            $this->method = &$this->routeData[$this->atroute][1];




        }else{
            throw new \Exception('路由不存在');


        }
    }

    /**
     * 获取property
     * @param $propertyName
     */
    public function __get($propertyName)
    {
        if(isset($this->$propertyName)){
            return $this->$propertyName;
        }
        return null;
    }

        /**
     * 启动请求转移（实例化控制器）
     */
    protected function begin()
    {
        /**
         * 实例化控制器
         */
        $atpath = &$this->atpath;
        $new = new $this->atpath;
        $method = &$this->method;
        /**
         * 控制器方法
         */
        return $new->$method();
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
        $file_dir = scandir( __ROUTE__['file_dir']);
        foreach ($file_dir as $k =>$v){

            if(!is_dir(__ROUTE__['file_dir'].__DS__.$v)){
                /**
                 * 合并【按文件字母升序排列】
                 */
                $this->routeData = array_merge($this->routeData,require(__ROUTE__['file_dir'].__DS__.$v));
            }else{
                //echo '目录'.$v;
            }
        }

    }

    /**
     * 初始化
     * @param bool $status
     * @return null|static
     */
    public static  function init($status = false)
    {
        /**
         * 判断是否已经有这个对象
         *             var_dump($this->atroute);
         */
        if(static::$object != null){
            return static::$object;
        }
        static::$object = new static();

        if($status == true){
            return static::$object ->begin();
        }

    }

}