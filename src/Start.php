<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 15:47
 * @title 脚手架启动文件
 */
namespace pizepei\staging;
use pizepei\config\InitializeConfig;
use pizepei\staging\Route;
use pizepei\staging\Request;
use pizepei\config\Config;
use pizepei\terminalInfo\ToLocation;
use Whoops\Run;
use pizepei\terminalInfo\TerminalInfo;
use pizepei\staging\MyException;



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

    /**
     * Start constructor.
     *
     * @param string $pattern
     * @param string $path
     * @throws \Exception
     */
    public function __construct($pattern = 'ORIGINAL',$path='..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.__APP__.DIRECTORY_SEPARATOR)
    {
        /**
         * 获取配置
         * 判断环境
         */
        if(PHP_VERSION <= 7){
            exit('PHP版本必须<=7,当前版本'.PHP_VERSION);
        }

        /**
         * 服务器版本php_uname('s').php_uname('r');
         */
        /**
         * 设置初始化配置
         */
        $path = $this->setDefine($pattern,$path);
        /**
         * 判断模式
         */
        //array("App","customError")
        //MyException::class
        if(__INIT__['pattern'] == 'exploit'){
            new MyException($path);
            //set_error_handler
            //set_exception_handler(array('pizepei\staging\MyException','exploit($errno, $errstr, $errfile, $errline)'));

            //set_exception_handler(function($exception){
            //        echo "<b>Exception:</b> ".__REQUEST_ID__ , $exception->getMessage();
            //});
            //$whoops = new Run;
            //$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            //$whoops->register();

        }else{
            // 关闭所有PHP错误报告
            //error_reporting(0);
            //set_exception_handler(['MyException','production']);

        }


    }
    /**
     * 基本初始化
     */
    protected function init()
    {


    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:58
     * @param $path
     * @param $namespace
     * @throws \ReflectionException
     * @title  方法标题（一般是方法的简称）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function getInitDefine($path,$namespace)
    {
        /**
         * 获取配置 合并
         */
        if(__EXPLOIT__){
            /**
             * 获取基础配置
             */

            $InitializeConfig = new InitializeConfig();
            $Config = $InitializeConfig->get_config_const($path);
            $dbtabase = $InitializeConfig->get_dbtabase_const($path);

            $get_error_log = $InitializeConfig->get_error_log_const($path);
            /**
             * 判断是否存在配置
             */
            if(!file_exists($path.'SetConfig.php')){
                $InitializeConfig->set_config('SetConfig',$Config,$path,$namespace);
            }
            if(!file_exists($path.'SetDbtabase.php')){
                $InitializeConfig->set_config('SetDbtabase',$dbtabase,$path,$namespace);
            }
            if(!file_exists($path.'SetErrorOrLog.php')){

                $InitializeConfig->set_config('SetErrorOrLog',$get_error_log,$path,$namespace);
            }
            /**
             * 合并(只能合并一层)
             */
            $Config = array_merge($Config,$InitializeConfig->get_const($namespace.'\\SetConfig'));
            $dbtabase = array_merge($dbtabase,$InitializeConfig->get_const($namespace.'\\SetDbtabase'));

            $get_error_log = array_merge($get_error_log,$InitializeConfig->get_const($namespace.'\\SetErrorOrLog'));

            /**
             * 写入
             */
            $InitializeConfig->set_config('Config',$Config,$path);
            $InitializeConfig->set_config('Dbtabase',$dbtabase,$path);
            $InitializeConfig->set_config('ErrorOrLog',$get_error_log,$path);

        }else{
            /**
             * 判断是否存在配置
             */
            if(!file_exists($path.'Config.php')){
                $InitializeConfig = new InitializeConfig();
                $Config = $InitializeConfig->get_config_const();
                $Config = array_merge($Config,$InitializeConfig->get_const($namespace.'\\SetConfig'));
                /**
                 * 合并
                 */
                $InitializeConfig->set_config('Config',$Config,$path);
            }
            if(!file_exists($path.'Dbtabase.php')){
                $InitializeConfig = new InitializeConfig();
                $dbtabase = $InitializeConfig->get_dbtabase_const();
                $dbtabase = array_merge($dbtabase,$InitializeConfig->get_const($namespace.'\\SetDbtabase'));
                /**
                 * 合并
                 */
                $InitializeConfig->set_config('Dbtabase',$dbtabase,$path);
            }
            if(!file_exists($path.'ErrorOrLog.php')){

                $InitializeConfig = new InitializeConfig();
                $dbtabase = $InitializeConfig->get_dbtabase_const();

                $dbtabase = array_merge($dbtabase,$InitializeConfig->get_const($namespace.'\\SetErrorOrLog'));
                /**
                 * 合并
                 */
                $InitializeConfig->set_config('ErrorOrLog',$dbtabase,$path);
            }

        }
    }

    /**
     * 设置define
     * @param string $pattern 默认 传统模式  namespace
     * @param string $path 默认 ../config/__APP__/    传统模式
     * @return string
     * @throws \Exception
     */
    protected function setDefine($pattern = 'ORIGINAL',$path='')
    {
        define('__RUN_PATTERN__',$pattern);//运行模式  SAAS    ORIGINAL
        /**
         * 传统模式
         */
        if($pattern == 'ORIGINAL'){
            $path='..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.__APP__.DIRECTORY_SEPARATOR;
            $namespace = 'config\\'.__APP__;
            $this->getInitDefine($path,$namespace);
        }else if($pattern == 'SAAS'){
            if(empty($path)){
                throw new \Exception('SAAS配置路径必须',500);
            }
            /**
             * 自定义路径
             */
            $path .= DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.__APP__.DIRECTORY_SEPARATOR;
            $namespace = 'config\\'.__APP__;
            $this->getInitDefine($path,$namespace);
            ///**
            // * 判断是否存在配置
            // */
            //if(!file_exists($path.'Config.php')){
            //    /**
            //     *
            //     * 通过内网加密获取（限制ip aes加密）
            //     * SAAS配置在数据库中（一个统一的基础配置   和个自的配置）
            //     * 合并
            //     * 写入对应路径     自定义目录/项目名称/__APP__/xxxx.php
            //     */
            //}
            //if(!file_exists($path.'Dbtabase.php')){
            //    /**
            //     *
            //     * 通过内网加密获取（限制ip aes加密）
            //     * SAAS配置在数据库中（一个统一的基础配置   和个自的配置）
            //     * 合并
            //     * 写入对应路径     自定义目录/项目名称/__APP__/xxxx.php
            //     */
            //}
        }
        /**
         * 包含配置
         */
        require ($path.'Config.php');
        require($path.'Dbtabase.php');
        require($path.'ErrorOrLog.php');

        /**
         * 获取配置到define;
         */
        define('__INIT__',\Config::UNIVERSAL['init']);//初始化配置
        define('__ROUTE__',\Config::UNIVERSAL['route']);//路由配置
        define('__DS__',DIRECTORY_SEPARATOR);//路由配置
        define('__APP__FILE__',DIRECTORY_SEPARATOR);//应用的绝对目录

        define('__TEMPLATE__','..'.DIRECTORY_SEPARATOR.__APP__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR);//模板路径
        return $path;
        ///**
        // * 自定义设置配置
        // */
        //if(__INIT__['define'] != ''){
        //    require(__INIT__['define']);
        //}
    }

    /**CLI 参数
     *
     */
    const  GETOPT =[
        'route:',//路由
        'sqllog:',//是否启用dbslq日志
        'domain:',//域名
    ];

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:57
     * @param string $pattern
     * @throws \Exception
     * @title  开始web模式驱动
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     * @router 方法路由一般控制器只适应(get /user/:user_id[int] ))
     */
    public function start($pattern = 'WEB')
    {

        define('__PATTERN__',$pattern);
        if(__PATTERN__ === 'CLI'){
            $getopt = getopt('',self::GETOPT);
            define('__CLI__SQL_LOG__',$getopt['sqllog']??'false');
            /**
             * 命令行模式
             */
            $_SERVER['HTTP_HOST']       = 'localhost';
            $_SERVER['REMOTE_ADDR']     = '127.0.0.1';
            $_SERVER['REQUEST_METHOD']  = 'CLI';
            $_SERVER['SERVER_PORT']     = '--';
            $_SERVER['REQUEST_URI']     = $getopt['route'];
            $_SERVER['SCRIPT_NAME']     =  $getopt['route'];
            $_GET['s'] =  $getopt['route'];
            $_SERVER['HTTP_COOKIE']     = '';
            $_SERVER['QUERY_STRING']    = '';
            $_SERVER['HTTP_USER_AGENT']    = '';
        }else{
            define('__CLI__SQL_LOG__','false');
        }
        /**
         * 请求类
         */
        $Request = Request::init();

        define('__REQUEST_ID__',$Request->RequestId);//初始化配置

        /**
         * 全局响应配置
         */
        //$Request->setHeader(__INIT__['header']);
        /**
         * 控制器return
         */
        $this->output(Route::init(true));
    }
    /**
     *
     * @Author pizepei
     * @Created 2019/6/12 21:56
     * @param $data
     * @throws \Exception
     * @title  控制器输出
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function output($data)
    {
        $Route = Route::init();
        /**
         * 根据不同的路由进行不同的
         */

        /**
         * 路由单独配置的调试4
         * 路由权限分组 3
         */
        //var_dump($Route->atRouteData);


        $debug = $Route->atRouteData['RouterAdded']['debug']??false;

        $pattern = isset($Route->routeArr[4])?$Route->routeArr[4]:false;
        //http://tool.oschina.net/commons/
        switch ($Route->ReturnType) {
            case 'json':
                $result = $this->returnJson($data,$debug);
                break;
            case 'xml':

                echo "xml";
                break;
            case 'html':
                $result = $this->returnHtml($data,$debug);
                break;

            default:
                $result = $this->returnJson($data,$debug);
        }

        echo $result??'';

    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:55
     * @param $data
     * @return mixed
     * @title  返回字符串
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function returnString($data)
    {
        return $data;
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:55
     * @param $data
     * @return mixed
     * @title  返回html
     */
    protected function returnHtml($data)
    {
        return $data;
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:54
     * @param $data
     * @param $debug
     * @throws \Exception
     * @title  返回json
     */
    protected function returnJson($data,$debug)
    {

        if($data != null){
            if(is_array($data)){
                /**
                 * 判断是否是开发模式
                 * 不是
                 * 判断是否路由单独开启 调试模式
                 */
                if( __INIT__['pattern']!='exploit' || $debug==='true' ){$data['SYSTEMSTATUS'] = $this->getSystemStatus();}

                if(isset($data[__INIT__['ReturnJsonData']]) && isset($data[__INIT__['SuccessReturnJsonCode']['name']]) && isset(__INIT__['SuccessReturnJsonMsg']['name']))
                {
                    $data[__INIT__['ReturnJsonData']] = Request::init()->returnParamFiltration($data[__INIT__['ReturnJsonData']]);
                }else{
                    Request::init()->returnParamFiltration($data);
                }
                echo json_encode($data,JSON_UNESCAPED_UNICODE );
            }else{
                /**
                 * 控制器returnd 的是字符串
                 */
                if( __INIT__['pattern']=='exploit' || $debug==='true'){
                    echo json_encode(['data'=>$data,'SYSTEMSTATUS'=>$this->getSystemStatus()],JSON_UNESCAPED_UNICODE );
                }else{
                    echo json_encode(['data'=>$data],JSON_UNESCAPED_UNICODE );
                }
            }
        }else{
            /**
             * 控制器没有return;
             */
            if( __INIT__['pattern']=='exploit' || $debug==='true'){
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
            'requestId'=>__REQUEST_ID__,
            /**
             * 路由控制器
             */
            'controller' => $Route->controller,
            /**
             * 控制器方法
             */
            'function_method' => $Route->method,
            /**
             * 请求方法 get post
             */
            'request_method' =>$_SERVER['REQUEST_METHOD'],
            /**
             * 完整路由（去除域名的url地址）
             */
            'request_url'=> $_SERVER['REQUEST_URI'],
            /**
             * 解释路由
             */
            'route' => $Route->atRoute,
            /**
             * 历史slq
             */
            'sql' =>isset($GLOBALS['DBTABASE']['sqlLog'])?$GLOBALS['DBTABASE']['sqlLog']:'',
            /**
             * ip信息
             */
            'clientInfo'=>__INIT__['clientInfo']?terminalInfo::getArowserPro():terminalInfo::get_ip(),
            /**
             * 系统状态
             */
            '系统开始时的内存(K)'=>__INIT_MEMORY_GET_USAGE__,
            '系统结束时的内存(KB)'=>round(memory_get_usage()/1024/1024,5),
            '系统内存峰值(KB)' =>round(memory_get_peak_usage()/1024/1024,5),
            '执行耗时(S)' =>round(microtime(true)-__INIT_MICROTIME__,4),

        ];



    }



}