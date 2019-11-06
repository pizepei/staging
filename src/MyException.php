<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/4/16
 * Time: 17:25
 * @title 异常处理类配合set_exception_handler
 */
declare(strict_types=1);

namespace pizepei\staging;


use pizepei\func\Func;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MyException
{
    /**
     * 异常对象
     * @var null
     */
    //private $exception = null;
    /**
     * 应用容器
     * @var App|null
     */
    protected $app = null;
    /**
     * json_encode 函数配置
     * @var int
     */
    protected $json_encode = 320;
    /**
     * MyException constructor.
     * @param string $path
     * @param string $exception
     * @param array  $info
     */
    public function __construct(string $path,$exception=null,array$info=[],App $app) {
        $this->app  =$app;
        $this->path = $path;
        $this->info = $info;
        #判断是否已经加载配置文件
        if (class_exists('\Config')){
            $this->json_encode = \Config::UNIVERSAL['init']['json_encode'];
        }

        $this->exception = $exception;
        if($exception){
            $this->PDO_exception_handler($exception);
        }
        @set_exception_handler(array($this, 'exception_handler'));
        @set_error_handler(array($this, 'error_handler'));
        //throw new Exception('DOH!!');error_get_last
    }

    /**
     * 错误处理
     * @param $errno 错误编号
     * @param $errstr 错误消息
     * @param $errfile 错误所在的文件
     * @param $errline 错误所在的行
     */
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        header("Content-Type:application/json;charset=UTF-8");
        # 错误代码方便查日志
        $str_rand = $this->app->Helper()->str()->str_rand(20);

        if(!$this->app->has('Route')){
            $route = [
                'controller'=>'system',
                'router'=>$_SERVER['PATH_INFO'],
            ];
        }
        # 系统错误统一 50000
        $result = [
            $this->app->__INIT__['ErrorReturnJsonCode']['name']??'code'=>50000,
        ];
        #判断是否是开发模式
        if($this->app->__EXPLOIT__){
            # 开发模式
            $result[$this->app->__INIT__['ErrorReturnJsonMsg']['name']??'code'] = $errstr.'['.$errno.']';
            $result[$this->app->__INIT__['ReturnJsonData']??'data'] = [
                'route'=>$route??[
                    'controller'=>$this->app->Route()->controller.'->'.$this->app->Route()->method,
                    'router'=>$this->app->Route()->atRoute,
                ],
                'File'=>str_replace(getcwd(), "", $errfile).'['.$errline.']',
            ];

        }else{
            /**
             * 生产模式
             */
            $result[ $this->app->__INIT__['ErrorReturnJsonMsg']['name']??'msg'] = '系统繁忙['.$str_rand.']';
        }
        $result['error'] = $str_rand;
        $result['statusCode'] = 100;
        $this->createLog($result);
        $this->app->Response()->output_ob_start(json_encode($result,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 异常类
     * @var null
     */
    private $exception = null;
    /**
     * 错误代码（用来排查异常处理，error和succeed方法为业务逻辑，不自动生成排查代码，一般在生产环境时异常都统一调试系统错误这个时候会有错误比较短的代码提供显示方便用户反馈）
     * @var string
     */
    private $errorCode = '';
    /**
     * 常规
     * @param $exception
     */
    public function exception_handler($exception) {
        $this->exception = $exception;
        if ($this->app->Response()->ResponseData !==''){
            $this->app->Response()->output_ob_start();
        }else{
            $this->errorCode = $this->app->Helper()->str()->str_rand(15);
            # 判断是否是开发模式
            if($this->app->__EXPLOIT__){
                # 开发模式
                $this->app->Response()->output_ob_start($this->exploit($exception));
            }else{
                # 生产模式
                $this->app->Response()->output_ob_start($this->production($exception));
            }
        }
    }
    /**
     * PDO
     * @param $exception
     */
    public function PDO_exception_handler($exception) {

        header("Content-Type:application/json;charset=UTF-8");
        $this->exception = $exception;
        //var_dump($exception);
        /**
         * 判断是否是开发模式
         */
        if($this->app->__EXPLOIT__){

            /**
             * 开发模式
             */
            $this->exploit($exception);
        }else{
            /**
             * 生产模式
             */
            $this->production($exception);
        }
    }

    /**
     * 生产环境下
     * @param $exception
     * @return false|string
     */
    private function production($exception)
    {
        return json_encode($this->setCodeCipher(),$this->json_encode);
    }

    /**
     * 开发调试环境下
     * @param $exception
     * @return false|string
     */
    private function exploit($exception)
    {
        return json_encode($this->resultData($this->exception->getMessage(),$this->exception->getCode(),$this->exploitData()));
    }

    /**
     * @param       $msg
     * @param       $code
     * @param array $data
     * @return array
     */
    private  function resultData($msg,$code,$data=[])
    {
        $result =  [
            $this->app->__INIT__['ErrorReturnJsonMsg']['name']??'msg'   =>$msg,
            $this->app->__INIT__['ErrorReturnJsonCode']['name']??'code' =>$code==0?$this->app->__INIT__['ErrorReturnJsonCode']['value']??100:$this->exception->getCode(),
            $this->app->__INIT__['ReturnJsonData']??'data'              =>$this->app->Response()->ResponseData,
            'statusCode'                => 100,# statusCode 成功 200  错误失败  100   主要用来统一请求需要状态 是框架固定的 代表是succeed 还是 error或者异常
            'errorCode'                 =>$this->errorCode,
            'debug'                     =>$data, # 异常调试
        ];
        return $result;
    }


    /**
     * 开发时的异常处理（data）
     * @return array
     */
    private function exploitData()
    {
        # 判断路由是否初始化
        if(!$this->app->has('Route')){
            $route = [
                'controller'=>'system',
                'router'=>$_SERVER['PATH_INFO'],
            ];
        }
        return [
            'route'=>isset($route)?$route:[
                'controller'=>($this->app->Route($this->app)->controller??'').'->'.($this->app->Route()->method??''),
                'router'=>$this->app->Route()->atRoute,
            ],
            'sql'=>isset($GLOBALS['DBTABASE']['sqlLog'])?$GLOBALS['DBTABASE']['sqlLog']:'',
            'File'=>str_replace(getcwd(), "", $this->exception->getFile()).'['.$this->exception->getLine().']',
            'Trace'=>$this->getTrace(30),
        ];
    }
    /**
     * @Author pizepei
     * @Created 2019/4/16 23:01
     * @param int $tier
     * @return array
     * @title  获取痕迹方法 默认6级
     * @explain
     */
    private function getTrace(int $tier=3)
    {
        $array =[];
        foreach($this->exception->getTrace() as $key=>$value)
        {
            if($key>($tier-1)){
                break;
            }
            if($key != 0){
                unset($value['args']);
            }
            $array[] = $value;
        }
        return $array;
    }
    /**
     * 创建错误代码
     * 用以生产
     * @param array $error_code
     * @return array
     */
    protected function setCodeCipher()
    {
        # 判断是否是权限问题和登录问题(前端需要统一判断)
        if(\ErrorOrLog::NOT_LOGGOD_IN_CODE == $this->exception->getCode()   || \ErrorOrLog::JURISDICTION_CODE == $this->exception->getCode() ){
            $result =  [
                $this->app->__INIT__['ErrorReturnJsonMsg']['name']  =>$this->exception->getMessage(),
                $this->app->__INIT__['ErrorReturnJsonCode']['name'] =>$this->exception->getCode(),
                'errorCode'                                         =>$this->errorCode,
                'statusCode'                                        => 100,
            ];
        }

        # 这里暂时没有发现是做什么的
        if($this->info){
            if(isset($this->info[$this->exception->getCode()])){
                $result =  [
                    $this->app->__INIT__['ErrorReturnJsonMsg']['name']  =>$this->info[$this->exception->getCode()][0].'['.$this->errorCode.']',
                    $this->app->__INIT__['ErrorReturnJsonCode']['name'] =>$this->exception->getCode()==0?$this->app->__INIT__['ErrorReturnJsonCode']['value']:$this->exception->getCode(),
                    'errorCode'                                         =>$this->errorCode,
                    'statusCode'                                        => 100,
                ];
            }else{
                $result =  [
                    $this->app->__INIT__['ErrorReturnJsonMsg']['name']      =>'系统繁忙['.$this->errorCode.']',
                    $this->app->__INIT__['ErrorReturnJsonCode']['name']     =>$this->exception->getCode()==0?$this->app->__INIT__['ErrorReturnJsonCode']['value']:$this->exception->getCode(),
                ];
            }
            $result['errorCode']    = $this->errorCode;
            $result['statusCode']   = 100;

            return $result;
        }
        # 确定错误代码：在生产模式下

        # 异常错误默认code为0
        if($this->exception->getCode() === 0)
        {
            $result =  [
                $this->app->__INIT__['ErrorReturnJsonMsg']['name']  =>'系统繁忙['.$this->errorCode.']',
                $this->app->__INIT__['ErrorReturnJsonCode']['name'] =>$this->app->__INIT__['ErrorReturnJsonCode']['value'],
                'errorCode'                                         => $this->errorCode,
                'statusCode'                                        => 100,
            ];
        }
        # 判断错误代码错误区间（在抛异常时定义了code时 检测是系统级别的区间还是开发者定义的错误代码区间）
        foreach(\ErrorOrLog::CODE_SECTION as $key=>$value)
        {
            # 判断范围
            if($value[0] < $this->exception->getCode() &&  $this->exception->getCode()<$value[1])
            {
                if($key === 'SYSTEM_CODE')
                {
                    if(isset(\ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()]))
                    {
                        $result =  [
                            $this->app->__INIT__['ErrorReturnJsonMsg']['name']=>
                                is_int(\ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()][0])?
                                    \ErrorOrLog::HINT_MSG[\ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()][0]]:
                                    \ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()][0].'['.$this->errorCode.']',
                            $this->app->__INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                            'errorCode'     =>$this->errorCode,
                            'statusCode'    => 100,
                        ];
                    }

                }else if($key === 'USE_CODE')
                {
                    if(isset(\ErrorOrLog::USE_CODE[$this->exception->getCode()]))
                    {
                        $result =  [
                            $this->app->__INIT__['ErrorReturnJsonMsg']['name']=>
                                is_int(\ErrorOrLog::USE_CODE[$this->exception->getCode()][0])?
                                    \ErrorOrLog::HINT_MSG[\ErrorOrLog::USE_CODE[$this->exception->getCode()][0]].'['.$this->errorCode.']':
                                    \ErrorOrLog::USE_CODE[$this->exception->getCode()][0].'['.$this->errorCode.']',
                            $this->app->__INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                            'errorCode'         =>$this->errorCode,
                            'statusCode'        => 100,
                        ];
                    }
                }
            }
        }
        # 写入错误日志
        $this->createLog($result);
        return $result;
    }

    /**
     * 创建日志
     * @param        $result
     * @param string $Logger
     * @throws \Exception
     */
    private function createLog($result,string $Logger='')
    {
        // 创建日志频道
        #  "monolog/monolog": "^1.23"
//        $log = new Logger('name');
//        $log->pushHandler(new StreamHandler($this->path.'/your.log', Logger::WARNING));
        // 添加日志记录
        //$log->addWarning('Foo',$this->exploitData());
        //$log->addError('Bar',$result);

    }

}