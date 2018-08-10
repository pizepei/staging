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
use Whoops\Run;
class Start
{
    /**
     * 加载配置
     *      判断是否有有效缓存
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


    /**
     *
     */
    protected $RternOutput = null;

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
         * 设置初始化配置
         */
        $this->setDefine();

        /**
         * 判断模式
         */
        if(__INIT__['pattern'] == 'exploit'){

            $whoops = new Run;
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
         * 控制器return
         */
        $this->output(Route::init(true));
    }
    /**
     * 基本初始化
     */
    protected function init()
    {


    }

    /**
     * 设置define
     */
    protected function setDefine()
    {
        /**
         * 获取配置到define;
         */
        define('__INIT__',Config::UNIVERSAL['init']);//初始化配置
        define('__ROUTE__',Config::UNIVERSAL['route']);//路由配置
        define('__DS__',DIRECTORY_SEPARATOR);//路由配置
        /**
         * 自定义设置配置
         */
        if(__INIT__['define'] != ''){
            require(__INIT__['define']);
        }
    }

    /**
     * 开始
     */
    public function start()
    {

    }
    /**
     * 控制器输出
     * @param $data
     */
    protected function output($data)
    {
        /**
         * 判断输出类型
         */
        if(__INIT__['return'] == 'json'){
            if($data != null){
                if(is_array($data)){
                    /**
                     * 控制器return的是array ['code'=>001,'msg'=>'比如这样']
                     */
                    $data['SYSTEMSTATUS'] = $this->getSystemStatus();
                    echo json_encode($data,JSON_UNESCAPED_UNICODE );
                }else{
                    /**
                     * 控制器returnd 的是字符串
                     */
                    echo json_encode(['data'=>$data,'SYSTEMSTATUS'=>$this->getSystemStatus()],JSON_UNESCAPED_UNICODE );
                }
            }else{
                /**
                 * 控制器没有return;
                 */
                echo json_encode(['SYSTEMSTATUS'=>$this->getSystemStatus()],JSON_UNESCAPED_UNICODE );
            }

        }

    }

    /**
     * 获取系统状态
     */
    protected function getSystemStatus()
    {
        /**
         * 路由类
         */
        $Route = Route::init();

        return $data =[
            /**
             * 路由控制器
             */
            'controller' => $Route->controller,
            /**
             * 控制器方法
             */
            'method' => $Route->method,
            /**
             * 完整路由
             */
            'REQUEST_URI'=> $_SERVER['REQUEST_URI'],
            'route' => $Route->atroute,

            /**
             * 历史slq
             */
            'sql' =>isset($GLOBALS['DBTABASE']['slqLog'])?$GLOBALS['DBTABASE']['slqLog']:'',
            /**
             * 历史变量
             */
            'sqlvariable' => isset($GLOBALS['DBTABASE']['variableLog'])?$GLOBALS['DBTABASE']['variableLog']:'',


            '系统开始时的内存(K)'=>__INIT_MEMORY_GET_USAGE__,
            '系统结束时的内存(K)'=>memory_get_usage()/1024,
            '系统内存峰值(K)' =>memory_get_peak_usage()/1024,
            '执行耗时(S)' =>round(microtime(true)-__INIT_MICROTIME__,4),
        ];


    }



}