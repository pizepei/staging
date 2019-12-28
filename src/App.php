<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/07/20
 * Time: 16:30
 * @title 框架容器类
 */
declare(strict_types=1);

namespace pizepei\staging;


use app\HelperClass;
use pizepei\config\InitializeConfig;
use pizepei\container\Container;
use pizepei\deploy\LocalDeployServic;
use pizepei\helper\Helper;
use pizepei\model\db\Db;
use pizepei\model\db\TableAlterLogModel;
use pizepei\model\redis\Redis;
use pizepei\terminalInfo\TerminalInfo;

/**
 * Class App
 * @package pizepei\staging
 * @property BasicsAuthority              $Authority  权限基础类属性
 * @method  Authority               Authority(string $pattern,App $app  = App) 权限基础类
 * @method  Request                 Request(App $app  = App) 请求类
 * @method  Controller              Controller(App $app  = App) 控制器类
 * @method  MyException             MyException(string $path,$exception=null,array$info=[],App $app) 权限基础类
 * @method  Route                   Route(App $app = App) 路由类
 * @method  InitializeConfig        InitializeConfig(App $app) 初始化配置类
 * @method  Helper                  Helper(string $son = '') 助手类
 * @method  Response                Response(App $app = App) 框架响应类
 */
class App extends Container
{
    /**
     * 容器标识
     */
    const CONTAINER_NAME = 'App';
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
     * 容器绑定标识
     * @var array
     */
    protected $baseBind = [
        'Authority'             =>Authority::class,
        'Controller'            =>Controller::class,
        'MyException'           =>MyException::class,
        'Request'               =>Request::class,
        'Route'                 =>Route::class,
        'InitializeConfig'      =>InitializeConfig::class,
        'Helper'                =>Helper::class,
        'Response'              =>Response::class,
    ];
    /**
     * 项目根目录  默认是上级目录层
     * @var int|string
     */
    protected $DOCUMENT_ROOT = 2;
    /**
     * 应用配置
     * @var string
     */
    protected $__CONFIG_PATH__ = '';
    /**
     * 项目级别的部署配置
     * @var string
     */
    protected $__DEPLOY_CONFIG_PATH__ = '';
    /**
     * 应用目录名称
     * @var string
     */
    protected $__APP__ = 'app';
    /**
     * @var string
     */
    protected $__USE_PATTERN__ = '';
    /**
     * @var string  widnows  or   linux
     */
    protected $__OS__ = 'linux';
    /**
     * 客户端IP
     * @var array|false|mixed|string
     */
    protected $__CLIENT_IP__ = '';
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
        'data:',//需要传输的数据 parse_str格式    处理后保存到GET中
    ];

    /**
     * Container constructor.
     * @param string $deployPath
     */
    public function __construct(bool $exploit = true,$app_path='app',$pattern = 'ORIGINAL',$path='',$deployPath='',$renPattern='WEB')
    {
        # 运行模式
        $this->__PATTERN__ = $renPattern;
        if ($this->__PATTERN__ =='CLI'){

            # 命令行模式
            # 比如>php index_cli.php --route gdhsg  --domain oauth.heil.top
            $getopt = getopt('',self::GETOPT);
            if (!isset($getopt['route'])){ throw new Exception('route domain 是必须的！');}
            parse_str($getopt['data']??'',$_GET);
            $this->__CLI__SQL_LOG__         = $getopt['sqllog']??'false';
            $_SERVER['HTTP_HOST']           = $getopt['domain']??'localhost';
            $_SERVER['REMOTE_ADDR']         = '127.0.0.1';
            $_SERVER['REQUEST_METHOD']      = 'CLI';
            $_SERVER['SERVER_PORT']         = '--';
            $_SERVER['REQUEST_URI']         =   $getopt['route'];
            $_SERVER['SCRIPT_NAME']         =   $getopt['route'];
            $_SERVER['PATH_INFO']           =   $getopt['route'];
            $_SERVER['HTTP_COOKIE']         =   '';
            $_SERVER['QUERY_STRING']        =   '';
            $_SERVER['HTTP_USER_AGENT']     =   '';

            $this->DOCUMENT_ROOT = dirname(getcwd()).DIRECTORY_SEPARATOR;#定义项目根目录
        }else{
            $this->DOCUMENT_ROOT = dirname($_SERVER['SCRIPT_FILENAME'],$this->DOCUMENT_ROOT).DIRECTORY_SEPARATOR;#定义项目根目录
        }
        $this->__CLIENT_IP__ = terminalInfo::get_ip();  # 客户端 IP

        if($this->__PATTERN__ === 'CLI'){

        }else{
            $this->__CLI__SQL_LOG__ = $getopt['sqllog']??'false';
        }

        $this->__APP__ =  $app_path;            #应用路径
        $this->__EXPLOIT__ = $exploit;          #是否开发调试模式  (使用应用级别的因为在项目级别可能会地址SAAS模式下所有的租户都开启了调试模式)
        $this->__USE_PATTERN__ = $pattern;      #应用模式 ORIGINAL       SAAS
        if (DIRECTORY_SEPARATOR ==='\\'){
            $this->__OS__ = 'widnows';
        }
        #应用级别配置
        $pathFof = '';
        if ($this->__USE_PATTERN__ == 'SAAS'){
            if (empty($path)){$pathFof = DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'];}
        }
        # 应用配置路径
        $this->__CONFIG_PATH__ = empty($path)?$this->DOCUMENT_ROOT.'config'.$pathFof.DIRECTORY_SEPARATOR.$this->__APP__.DIRECTORY_SEPARATOR:$path;

        #项目级别配置
        if (empty($deployPath)){
            $this->__DEPLOY_CONFIG_PATH__ = $this->DOCUMENT_ROOT.'config'.DIRECTORY_SEPARATOR;
        }else{
            $this->__DEPLOY_CONFIG_PATH__ = $this->DOCUMENT_ROOT.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$deployPath.DIRECTORY_SEPARATOR;
        }
        # 启动Helper容器
//        $this->Helper();

        $this->Helper('\\container\\'.$this->__APP__.'\HelperContainer');
        if ($this->Helper()->is_empty($app_path)){
            throw new \Exception('应用路径不能为空'.PHP_VERSION);
        }
        #获取配置、判断环境
        if(PHP_VERSION <= 7){
            throw new \Exception('PHP版本必须<=7,当前版本'.PHP_VERSION);
        }

        # 判断是否为开发调试模式
        if($this->__EXPLOIT__){
            $this->MyException($path,null,[],$this);
        }else{
            # 设置错误级别
            $this->MyException($path,null,[],$this);
            // 关闭所有PHP错误报告
            //error_reporting(0);
            //set_exception_handler(['MyException','production']);
        }
        # 设置初始化配置   服务器版本php_uname('s').php_uname('r');
        $path = $this->setDefine($pattern,$path,$deployPath); #关于配置：先读取deploy配置确定当前项目配置是从配置中心获取还是使用本地配置

        self::$containerInstance[static::CONTAINER_NAME] = $this;
    }

    /**
     *
     */
    public function __destruct()
    {

    }

    /**
     * 获取一个不能访问或者不存在的属性时
     * @param $name
     */
    public function __get($name)
    {
        return $this->$name??null;
    }
    /**
     * @Author pizepei
     * @Created 2019/6/12 21:58
     * @param $path
     * @param $namespace
     * @param $deployPath
     * @throws \ReflectionException
     * @title  获取项目配置
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function getInitDefine($path,$namespace,$deployPath)
    {
        $this->InitializeConfig($this); # 初始化配置类

        /**
         * 部署配置
         * 判断本地目录是否有配置，没有初始化
         *      有根据配置确定获取基础配置的途径
         */

        if(!file_exists($deployPath.'Deploy.php')){

            $Deploy = $this->InitializeConfig()->get_deploy_const();
            if(!file_exists($deployPath.'SetDeploy.php')){
                $this->InitializeConfig()->set_config('SetDeploy',$Deploy,$deployPath,'config');
            }
            # 合并写入
            $Deploy = array_merge($Deploy,$this->InitializeConfig()->get_const('config\\SetDeploy'));
            $this->InitializeConfig()->set_config('Deploy',$Deploy,$deployPath);
        }
        /**
         * 经过考虑，这个项目在saas模式下任何一个租户都使用一个配置文件，部署配置文件由deploay流程自动化生成。
         * 读取配置文件的路径暂时确定为项目标识项目标识定义在index入口文件
         */
        require($deployPath.'Deploy.php');
        $this->__EXPLOIT__ = \Deploy::__EXPLOIT__;//设置模式

        # 判断获取配置方式
        if(\Deploy::toLoadConfig == 'ConfigCenter')
        {
            if($this->__EXPLOIT__){

                # 远程配置中心获取
                $LocalDeployServic = new LocalDeployServic();
                $data=[
                    'appid'             =>  \Deploy::INITIALIZE['appid'],//项目标识
                    'domain'            =>  $_SERVER['HTTP_HOST'],//当前域名
                    'MODULE_PREFIX'     =>  \Deploy::PROJECT_ID,//项目标识
                    'time'              =>  time(),//
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
                    'appid'             =>\Deploy::INITIALIZE['appid'],//项目标识
                    'domain'            =>$_SERVER['HTTP_HOST'],//当前域名
                    'MODULE_PREFIX'     =>\Deploy::PROJECT_ID,//项目标识
                    'time'              =>time(),//
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
            # 本地获取
            
            # 判断是否是开发调试模式
            if($this->__EXPLOIT__){
                # 开发模式始终获取最新基础配置
                $Config = $this->InitializeConfig()->get_config_const($path);
                $dbtabase = $this->InitializeConfig()->get_dbtabase_const($path);
                $get_error_log = $this->InitializeConfig()->get_error_log_const($path);
                # 判断是否存在配置
                if(!file_exists($path.'SetConfig.php')){
                    $this->InitializeConfig()->set_config('SetConfig',$Config,$path,$namespace);
                }
                if(!file_exists($path.'SetDbtabase.php')){
                    $this->InitializeConfig()->set_config('SetDbtabase',$dbtabase,$path,$namespace);
                }
                if(!file_exists($path.'SetErrorOrLog.php')){
                    $this->InitializeConfig()->set_config('SetErrorOrLog',$get_error_log,$path,$namespace);
                }
                # 合并(只能合并一层)
                $Config = array_merge($Config,$this->InitializeConfig()->get_const($namespace.'\\SetConfig'));
                $dbtabase = array_merge($dbtabase,$this->InitializeConfig()->get_const($namespace.'\\SetDbtabase'));
                $get_error_log = array_merge($get_error_log,$this->InitializeConfig()->get_const($namespace.'\\SetErrorOrLog'));
                # 写入
                $this->InitializeConfig()->set_config('Config',$Config,$path);
                $this->InitializeConfig()->set_config('Dbtabase',$dbtabase,$path);
                $this->InitializeConfig()->set_config('ErrorOrLog',$get_error_log,$path);

            }else{
                # 判断是否存在配置
                if(!file_exists($path.'Config.php')){
                    $Config = $this->InitializeConfig()->get_config_const();
                    # 合并
                    $this->InitializeConfig()->set_config('Config',$Config,$path);
                }
                if(!file_exists($path.'Dbtabase.php')){

                    $dbtabase = $this->InitializeConfig()->get_dbtabase_const();
                    $dbtabase = array_merge($dbtabase,$this->InitializeConfig()->get_const($namespace.'\\SetDbtabase'));
                    # 合并
                    $this->InitializeConfig()->set_config('Dbtabase',$dbtabase,$path);
                }
                if(!file_exists($path.'ErrorOrLog.php')){
                    $dbtabase = $this->InitializeConfig()->get_error_log_const();
                    $dbtabase = array_merge($dbtabase,$this->InitializeConfig()->get_const($namespace.'\\SetErrorOrLog'));
                    # 合并
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

        $namespace = 'config\\'.$this->__APP__; # 命名空间

        if($this->__RUN_PATTERN__ == 'ORIGINAL'){ # 传统模式
            $this->getInitDefine($this->__CONFIG_PATH__,$namespace,$this->__DEPLOY_CONFIG_PATH__);
        }else if($this->__RUN_PATTERN__ == 'SAAS'){
            if(empty($path)){
                throw new \Exception('SAAS配置路径必须',10003);
            }
            # 自定义路径
            $path .= DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.$this->__APP__.DIRECTORY_SEPARATOR;
            $namespace = 'config\\'.$this->__APP__;
            $this->getInitDefine($path,$namespace,$deployPath);
        }

        # 包含配置
        require ($this->__CONFIG_PATH__.'Config.php');
        require($this->__CONFIG_PATH__.'Dbtabase.php');
        require($this->__CONFIG_PATH__.'ErrorOrLog.php');
        require ('..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'pizepei'.DIRECTORY_SEPARATOR.'helper'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR. 'function.php');
        # 获取配置到define
        $this->__INIT__ = \Config::UNIVERSAL['init'];//初始化配置
        $this->__ROUTE__ = \Config::UNIVERSAL['route'];//路由配置
        $this->__DS__ = DIRECTORY_SEPARATOR;//系统路径符
        $this->__TEMPLATE__ = $this->DOCUMENT_ROOT.$this->__APP__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR;//模板路径

        # 规划为应用控制器全部由LocalDeployServic::cliInitDeploy方法创建不在记录在git中，因此在开发模式下在进入路由前执行此方法动态生成控制器
        #  同时开发模式下可能响应时间会更长
        if ($this->__EXPLOIT__ || !file_exists($deployPath.DIRECTORY_SEPARATOR.$this->__APP__.'BaseAuthGroup.php') || !file_exists($deployPath.DIRECTORY_SEPARATOR.$this->__APP__.'BaseMenu.php') ||\Deploy::ENVIRONMENT =='develop'){
            LocalDeployServic::cliInitDeploy($this,['force'=>true]);    #动态生成控制器和其他文件
            LocalDeployServic::getMenuTemplate($this,['force'=>true]);  #动态根据依赖包生成菜单文件
        }
        # 包含引入 权限、菜单类
        require ($this->__CONFIG_PATH__.'BaseAuthGroup.php');
        require ($this->__CONFIG_PATH__.'BaseMenu.php');

        return $path;
    }


    /**
     * @Author pizepei
     * @Created 2019/6/12 21:57
     * @param string $pattern  CLI
     * @title  开始web模式驱动
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function start()
    {


        $this->Response($this);  #响应控制类
        $this->Route($this);    #路由类
        $this->Authority('\\container\\'.$this->__APP__.'\AuthorityContainer');
        $this->Request($this);  #请求类
        $this->__REQUEST_ID__ = $this->Request()->RequestId;    #获取请求类初始化设置的请求id
        # 全局响应配置 ：设置 Header
        $this->Response()->setHeader($this->__INIT__['header']);
        #控制器return  ：实例化控制器
        $this->Response()->output($this->Route()->begin());
    }

}