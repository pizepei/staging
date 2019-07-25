<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/07/20
 * Time: 16:30
 * @title 框架容器类
 */
namespace pizepei\staging;


use pizepei\config\InitializeConfig;
use pizepei\container\Container;
use pizepei\deploy\LocalDeployServic;
use pizepei\helper\Helper;
use pizepei\model\db\TableAlterLogModel;
use pizepei\terminalInfo\TerminalInfo;

/**
 * Class App
 * @package pizepei\staging
 * @method  Authority Authority(string $pattern,App $app  = App) 权限基础类
 * @method  Request Request(App $app  = App) 请求类
 * @method  Controller Controller(App $app  = App) 控制器类
 * @method  MyException MyException(string $path,$exception=null,array$info=[],App $app) 权限基础类
 * @method  Route Route(App $app = App) 路由类
 * @method  InitializeConfig InitializeConfig(App $app) 初始化配置类
 */
class App extends Container
{
    /**
     * 是否开启开发调试模式
     * @var bool
     */
    private $__EXPLOIT__ = false;
    /**
     * 运行模式  SAAS    ORIGINAL
     * @var string
     */
    private $__RUN_PATTERN__ = 'ORIGINAL';
    /**
     * 运行模式 cil  web
     * @var string
     */
    private $__PATTERN__ = 'WEB';
    /**
     * 是否记录slq日志
     * @var bool
     */
    private $__CLI__SQL_LOG__ = false;

    /**
     * 请求ID
     * @var null
     */
    private $__REQUEST_ID__ = null;

    /**
     * 系统初始化内存
     * @var int
     */
    private $__INIT_MEMORY_GET_USAGE__ = 0;
    /**
     * 系统初始化时间
     * @var float
     */
    private $__INIT_MICROTIME__ = 0;
    /**
     * 获取一个不能访问或者不存在的属性时
     * @param $name
     */
    public function __get($name)
    {

        return $this->$name??null;
    }
    /**
     * 容器绑定标识
     * @var array
     */
    protected $baseBind = [
        'Authority'             =>Authority::class,
        'Controller'            =>Controller::class,
        'MyException'           =>MyException::class,
        'Request'               =>Request::class,
        'Route'                 =>Route::class,
        'InitializeConfig'      =>InitializeConfig::class
    ];
    /**
     * Container constructor.
     * @param string $deployPath
     */
    public function __construct(bool $exploit = false,$app_path='app',$pattern = 'ORIGINAL',$path='',$deployPath='')
    {
        $this->__INIT_MEMORY_GET_USAGE__ = memory_get_usage()/1024; #系统初始化内存
        $this->__INIT_MICROTIME__ = microtime(true);   #系统初始化时间

        $this->__APP__ =  $app_path;    #应用路径
        $this->__EXPLOIT__ = $exploit;  #是否开发调试模式

        #关于配置：先读取deploy配置确定当前项目配置是从配置中心获取还是使用本地配置
        #普通配置
        if (empty($path)){$path='..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$app_path.DIRECTORY_SEPARATOR;}
        #项目级别配置
        if (empty($deployPath)){$deployPath='..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;}
        if (Helper::init(false,$this)->is_empty($app_path)){
            throw new \Exception('应用路径不能为空'.PHP_VERSION);
        }
        #获取配置、判断环境
        if(PHP_VERSION <= 7){
            throw new \Exception('PHP版本必须<=7,当前版本'.PHP_VERSION);
        }
        /**
         * 服务器版本php_uname('s').php_uname('r');
         */
        # 设置初始化配置
        $path = $this->setDefine($pattern,$path,$deployPath);
        # 判断是否为开发调试模式
        if($this->__EXPLOIT__){
            $this->MyException($path,null,[],$this);
        }else{
            /**
             * 设置错误级别？
             */
            $this->MyException($path,null,[],$this);
            // 关闭所有PHP错误报告
            //error_reporting(0);
            //set_exception_handler(['MyException','production']);
        }
        static::$instance = $this;
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:58
     * @param $path
     * @param $namespace
     * @param $deployPath
     * @throws \ReflectionException
     * @title  方法标题（一般是方法的简称）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function getInitDefine($path,$namespace,$deployPath)
    {
        $this->InitializeConfig($this);

        /**
         * 部署配置
         * 判断本地目录是否有配置，没有初始化
         *      有根据配置确定获取基础配置的途径
         */
        if(!file_exists($path.'Deploy.php')){

            $Deploy = $this->InitializeConfig()->get_deploy_const();
            if(!file_exists($deployPath.'SetDeploy.php')){
                $this->InitializeConfig()->set_config('SetDeploy',$Deploy,$deployPath,'config\\SetDeploy');
            }
            /**
             * 写入
             */
            $Deploy = array_merge($Deploy,$this->InitializeConfig()->get_const('config\\SetDeploy'));
            $this->InitializeConfig()->set_config('Deploy',$Deploy,$deployPath);
        }
        /**
         * 经过考虑，这个项目在saas模式下任何一个租户都使用一个配置文件，部署配置文件由deploay流程自动化生成。
         * 读取配置文件的路径暂时确定为项目标识项目标识定义在index入口文件
         */
        require($deployPath.'Deploy.php');
        $this->__EXPLOIT__ = \Deploy::__EXPLOIT__;//设置模式
        /**
         * 判断获取配置方式
         */
        if(\Deploy::toLoadConfig == 'ConfigCenter')
        {

            if($this->__EXPLOIT__){
                /**
                 * 远程配置中心获取
                 */
                $LocalDeployServic = new LocalDeployServic();
                $data=[
                    'appid'=>\Deploy::INITIALIZE['appid'],//项目标识
                    'domain'=>$_SERVER['HTTP_HOST'],//当前域名
                    'time'=>time(),//
                ];
                $data['ProcurementType'] = 'Config';//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
                $Config = $LocalDeployServic->getConfigCenter($data);
                $data['ProcurementType'] = 'Dbtabase';//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
                $dbtabase = $LocalDeployServic->getConfigCenter($data);
                $data['ProcurementType'] = 'ErrorOrLogConfig';//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
                $get_error_log = $LocalDeployServic->getConfigCenter($data);
                /**
                 * 写入
                 */
                $this->InitializeConfig()->set_config('Config',$Config['config'],$path,'','基础配置文件',$Config['date'],$Config['time'],$Config['appid']);
                $this->InitializeConfig()->set_config('Dbtabase',$dbtabase['config'],$path,'','数据库配置文件',$dbtabase['date'],$dbtabase['time'],$dbtabase['appid']);
                $this->InitializeConfig()->set_config('ErrorOrLog',$get_error_log['config'],$path,'','错误日志配置文件',$get_error_log['date'],$get_error_log['time'],$get_error_log['appid']);
            }else{
                /**
                 * 判断是否存在配置
                 */
                $LocalDeployServic = new LocalDeployServic();
                $data=[
                    'appid'=>\Deploy::INITIALIZE['appid'],//项目标识
                    'domain'=>$_SERVER['HTTP_HOST'],//当前域名
                    'time'=>time(),//
                ];
                if(!file_exists($path.'Config.php')){
                    $data['ProcurementType'] = 'Config';//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
                    $Config = $LocalDeployServic->getConfigCenter($data);
                    $this->InitializeConfig()->set_config('Config',$Config['config'],$path,'','基础配置文件',$Config['date'],$Config['time'],$Config['appid']);
                }
                if(!file_exists($path.'Dbtabase.php')){
                    $data['ProcurementType'] = 'Dbtabase';//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
                    $dbtabase = $LocalDeployServic->getConfigCenter($data);
                    $this->InitializeConfig()->set_config('Dbtabase',$dbtabase['config'],$path,'','数据库配置文件',$dbtabase['date'],$dbtabase['time'],$dbtabase['appid']);
                }
                if(!file_exists($path.'ErrorOrLog.php')){
                    $data['ProcurementType'] = 'ErrorOrLogConfig';//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
                    $get_error_log = $LocalDeployServic->getConfigCenter($data);
                    $this->InitializeConfig()->set_config('ErrorOrLog',$get_error_log['config'],$path,'','错误日志配置文件',$get_error_log['date'],$get_error_log['time'],$get_error_log['appid']);
                }
            }

        }else if(\Deploy::toLoadConfig == 'Local'){
            /**
             * 本地获取
             */
            /**
             * 判断是否是开发调试模式
             * 调试模式
             */
            if($this->__EXPLOIT__){
                /**
                 * 开发模式始终获取最新基础配置
                 */

                $Config = $this->InitializeConfig()->get_config_const($path);
                $dbtabase = $this->InitializeConfig()->get_dbtabase_const($path);
                $get_error_log = $this->InitializeConfig()->get_error_log_const($path);
                /**
                 * 判断是否存在配置
                 */
                if(!file_exists($path.'SetConfig.php')){
                    $this->InitializeConfig()->set_config('SetConfig',$Config,$path,$namespace);
                }
                if(!file_exists($path.'SetDbtabase.php')){
                    $this->InitializeConfig()->set_config('SetDbtabase',$dbtabase,$path,$namespace);
                }
                if(!file_exists($path.'SetErrorOrLog.php')){
                    $this->InitializeConfig()->set_config('SetErrorOrLog',$get_error_log,$path,$namespace);
                }
                /**
                 * 合并(只能合并一层)
                 */
                $Config = array_merge($Config,$this->InitializeConfig()->get_const($namespace.'\\SetConfig'));
                $dbtabase = array_merge($dbtabase,$this->InitializeConfig()->get_const($namespace.'\\SetDbtabase'));
                $get_error_log = array_merge($get_error_log,$this->InitializeConfig()->get_const($namespace.'\\SetErrorOrLog'));
                /**
                 * 写入
                 */
                $this->InitializeConfig()->set_config('Config',$Config,$path);
                $this->InitializeConfig()->set_config('Dbtabase',$dbtabase,$path);
                $this->InitializeConfig()->set_config('ErrorOrLog',$get_error_log,$path);

            }else{
                /**
                 * 判断是否存在配置
                 */
                if(!file_exists($path.'Config.php')){

                    $Config = $this->InitializeConfig()->get_config_const();
                    $Config = array_merge($Config,$this->InitializeConfig()->get_const($namespace.'\\SetConfig'));
                    /**
                     * 合并
                     */
                    $this->InitializeConfig()->set_config('Config',$Config,$path);
                }
                if(!file_exists($path.'Dbtabase.php')){

                    $dbtabase = $this->InitializeConfig()->get_dbtabase_const();
                    $dbtabase = array_merge($dbtabase,$this->InitializeConfig()->get_const($namespace.'\\SetDbtabase'));
                    /**
                     * 合并
                     */
                    $this->InitializeConfig()->set_config('Dbtabase',$dbtabase,$path);
                }
                if(!file_exists($path.'ErrorOrLog.php')){

                    $dbtabase = $this->InitializeConfig()->get_error_log_const();

                    $dbtabase = array_merge($dbtabase,$this->InitializeConfig()->get_const($namespace.'\\SetErrorOrLog'));
                    /**
                     * 合并
                     */
                    $this->InitializeConfig()->set_config('ErrorOrLog',$dbtabase,$path);
                }
            }
        }
    }

    /**
     * 设置define
     * @param string $pattern 默认 传统模式  namespace
     * @param string $path 默认 ../config/__APP__/    传统模式
     * @param string $deployPath 部署配置路径
     * @return string
     * @throws \Exception
     */
    protected function setDefine($pattern = 'ORIGINAL',$path='',$deployPath='')
    {
        $this->__RUN_PATTERN__ = $pattern;//运行模式  SAAS    ORIGINAL
        /**
         * 传统模式
         */
        if($this->__RUN_PATTERN__ == 'ORIGINAL'){
            $path='..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$this->__APP__.DIRECTORY_SEPARATOR;
            $namespace = 'config\\'.$this->__APP__;
            $this->getInitDefine($path,$namespace,$deployPath);
        }else if($this->__RUN_PATTERN__ == 'SAAS'){
            if(empty($path)){
                throw new \Exception('SAAS配置路径必须',10003);
            }
            /**
             * 自定义路径
             */
            $path .= DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.$this->__APP__.DIRECTORY_SEPARATOR;
            $namespace = 'config\\'.$this->__APP__;
            $this->getInitDefine($path,$namespace,$deployPath);
        }
        /**
         * 包含配置
         */
        include_once ($path.'Config.php');
        include_once($path.'Dbtabase.php');
        include_once($path.'ErrorOrLog.php');
        include_once ('..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'pizepei'.DIRECTORY_SEPARATOR.'helper'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR. 'function.php');
        /**
         * 获取配置到define;
         */
        $this->__INIT__ = \Config::UNIVERSAL['init'];//初始化配置
        $this->__ROUTE__ = \Config::UNIVERSAL['route'];//路由配置
        $this->__DS__ = DIRECTORY_SEPARATOR;//系统路径符
        $this->__APP__FILE__ = DIRECTORY_SEPARATOR;//应用的绝对目录（不知道干什么用的）
        $this->__TEMPLATE__ = '..'.DIRECTORY_SEPARATOR.$this->__APP__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR;//模板路径

//        define('__INIT__',\Config::UNIVERSAL['init']);//初始化配置
//        define('__ROUTE__',\Config::UNIVERSAL['route']);//路由配置
//        define('__DS__',DIRECTORY_SEPARATOR);//路由配置
//        define('__APP__FILE__',DIRECTORY_SEPARATOR);//应用的绝对目录
//        define('__TEMPLATE__','..'.DIRECTORY_SEPARATOR.__APP__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR);//模板路径
        return $path;
    }

    /**
     * 模板路径
     * @var string
     */
    private $__TEMPLATE__ = '';

    /**
     * 系统路径符
     * @var string
     */
    private $__DS__ = DIRECTORY_SEPARATOR;
    /**
     * 路由配置
     * @var array
     */
    private $__ROUTE__ = null;

    /**
     * 初始化配置
     * @var array
     */
    private $__INIT__ = [];
    /**
     * CLI 参数
     */
    const  GETOPT =[
        'route:',//路由
        'sqllog:',//是否启用dbslq日志
        'domain:',//域名
    ];

    /**
     * @Author pizepei
     * @Created 2019/6/12 21:57
     * @param string $pattern  CLI
     * @title  开始web模式驱动
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function start($pattern = 'WEB')
    {
        if($this->__PATTERN__ === 'CLI'){
            # 命令行模式

            $getopt = getopt('',self::GETOPT);
            $this->__CLI__SQL_LOG__ = $getopt['sqllog']??'false';
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
            $this->__CLI__SQL_LOG__ = $getopt['sqllog']??'false';
        }
        $this->Route($this);    #路由类
        $this->Request($this);  #请求类
        $this->__REQUEST_ID__ = $this->Request()->RequestId;    #获取请求类初始化设置的请求id

        # 全局响应配置 ：设置 Header
        $this->Request()->setHeader($this->__INIT__['header']);
        #控制器return  ：实例化控制器
        $this->output($this->Route()->begin());
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
        /**
         * 获取调试debug数据
         */
        $debug = $this->Route()->atRouteData['RouterAdded']['debug']??false;
        //$pattern = isset($Route->routeArr[4])?$Route->routeArr[4]:false;
        //http://tool.oschina.net/commons/
        switch ($this->Route()->ReturnType) {
            case 'json':
                $result = $this->returnJson($data,$debug);
                break;
            case 'xml':

                break;
            case 'html':
                $result = $this->returnHtml($data,$debug);
                break;
            case 'gif':

                break;
            default:
                echo $result = $this->returnJson($data,$debug);
        }
        if (isset($result)){
            echo $result??'';
        }
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
                if( $this->__EXPLOIT__ || $debug==='true' ){$data['SYSTEMSTATUS'] = $this->getSystemStatus();}

                if(isset($data[$this->__INIT__['ReturnJsonData']]) && isset($data[$this->__INIT__['SuccessReturnJsonCode']['name']]) && isset($this->__INIT__['SuccessReturnJsonMsg']['name']))
                {
                    $data[$this->__INIT__['ReturnJsonData']] = $this->Request()->returnParamFiltration($data[$this->__INIT__['ReturnJsonData']]);
                }else{
                    $this->Request()->returnParamFiltration($data);
                }
                echo json_encode($data,JSON_UNESCAPED_UNICODE );
            }else{
                /**
                 * 控制器returnd 的是字符串
                 */
                if( $this->__EXPLOIT__ || $debug==='true'){
                    echo json_encode(['data'=>$data,'SYSTEMSTATUS'=>$this->getSystemStatus()],JSON_UNESCAPED_UNICODE );
                }else{
                    echo json_encode(['data'=>$data],JSON_UNESCAPED_UNICODE );
                }
            }
        }else{
            /**
             * 控制器没有return;
             */
            if( $this->__EXPLOIT__ || $debug==='true'){
                echo json_encode(['SYSTEMSTATUS'=>$this->getSystemStatus()],JSON_UNESCAPED_UNICODE );
            }
        }
    }


    /**
     * 获取系统状态
     */
    protected function getSystemStatus():array
    {
        /**
         * 路由类
         */
        return $data =[
            'requestId'=>$this->__REQUEST_ID__,
            /**
             * 路由控制器
             */
            'controller' => $this->Route()->controller,
            /**
             * 控制器方法
             */
            'function_method' => $this->Route()->method,
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
            'route' =>$this->Route()->atRoute,
            /**
             * 历史slq
             */
            'sql' =>isset($GLOBALS['DBTABASE']['sqlLog'])?$GLOBALS['DBTABASE']['sqlLog']:'',
            /**
             * ip信息
             */
            'clientInfo'=>$this->__INIT__['clientInfo']?terminalInfo::getArowserPro():terminalInfo::get_ip(),
            /**
             * 系统状态
             */
            '系统开始时的内存(K)'=>$this->__INIT_MEMORY_GET_USAGE__,
            '系统结束时的内存(KB)'=>round(memory_get_usage()/1024/1024,5),
            '系统内存峰值(KB)' =>round(memory_get_peak_usage()/1024/1024,5),
            '执行耗时(S)' =>round(microtime(true)-($this->__INIT_MICROTIME__),4),

        ];


    }
    /**
     * 基本初始化
     */
    public static function init():self
    {
        return static::$instance;
    }
}