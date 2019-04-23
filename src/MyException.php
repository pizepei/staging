<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/4/16
 * Time: 17:25
 * @title 异常处理类配合set_exception_handler
 */

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
     * MyException constructor.
     * @param string $path
     * @param string $exception
     * @param array  $info
     */
    public function __construct(string $path,$exception=null,array$info=[]) {
        $this->path = $path;
        $this->info = $info;

        $this->exception = $exception;
        if($exception){
            $this->PDO_exception_handler($exception);
        }
        @set_exception_handler(array($this, 'exception_handler'));
        @set_error_handler(array($this, 'error_handler'));
        //throw new Exception('DOH!!');
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
        $str_rand = Func::M('str')::str_rand(20);
        /**
         * 判断是否是开发模式
         */
        $result = [
            __INIT__['ErrorReturnJsonCode']['name']=>50000,
        ];
        if(__EXPLOIT__){
            $Route = Route::init();
            /**
             * 开发模式
             */
            $result[__INIT__['ErrorReturnJsonMsg']['name']] = $errstr.'['.$errno.']';
            $result[__INIT__['ReturnJsonData']] = [
                'route'=>[
                    'controller'=>$Route->controller.'->'.$Route->method,
                    'router'=>$Route->atRoute,
                ],
                'File'=>str_replace(getcwd(), "", $errfile).'['.$errline.']',
            ];

        }else{
            /**
             * 生产模式
             */
            $result[ __INIT__['ErrorReturnJsonMsg']['name']] = '系统繁忙['.$str_rand.']';
        }
        $result['error'] = $str_rand;
        $this->createLog($result);
        exit(json_encode($result,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 异常类
     * @var null
     */
    private $exception = null;

    /**
     * 常规
     * @param $exception
     */
    public function exception_handler($exception) {
        header("Content-Type:application/json;charset=UTF-8");
        $this->exception = $exception;
        /**
         * 判断是否是开发模式
         */
        if(__EXPLOIT__){
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
        if(__EXPLOIT__){
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

    private function production($exception)
    {
        echo json_encode($this->setCodeCipher(),JSON_UNESCAPED_UNICODE);

        //exit(json_encode($this->setCodeCipher(),JSON_UNESCAPED_UNICODE));
    }
    private function exploit($exception)
    {
        exit(json_encode($this->resultData($this->exception->getMessage(),$this->exception->getCode(),$this->exploitData()),JSON_UNESCAPED_UNICODE));
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
            __INIT__['ErrorReturnJsonMsg']['name']=>$msg,
            __INIT__['ErrorReturnJsonCode']['name']=>$code,
            __INIT__['ReturnJsonData']=>$data,
        ];
        return $result;
    }


    /**
     * 开发时的异常处理（data）
     * @return array
     */
    private function exploitData()
    {

        $Route = Route::init();
        return [
            'route'=>[
                'controller'=>$Route->controller.'->'.$Route->method,
                'router'=>$Route->atRoute,
            ],
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
    private function getTrace(int$tier=3)
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
        $str_rand = Func::M('str')::str_rand(20);
        /**
         * 判断是否是权限和登录
         */
        if(\ErrorOrLog::NOT_LOGGOD_IN_CODE == $this->exception->getCode()   || \ErrorOrLog::JURISDICTION_CODE == $this->exception->getCode() ){
            $result =  [
                __INIT__['ErrorReturnJsonMsg']['name']=>$this->exception->getMessage(),
                __INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                'error'=>$str_rand,
            ];
        }

        if($this->info){
            if(isset($this->info[$this->exception->getCode()])){
                $result =  [
                    __INIT__['ErrorReturnJsonMsg']['name']=>$this->info[$this->exception->getCode()][0].'['.$str_rand.']',
                    __INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                    'error'=>$str_rand,
                ];
            }else{
                $result =  [
                    __INIT__['ErrorReturnJsonMsg']['name']=>'系统繁忙['.$str_rand.']',
                    __INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                ];
            }
            $result['error'] = $str_rand;
            return $result;
        }
        //确定错误代码
        /**
         *  0  默认
         */
        if($this->exception->getCode() === 0)
        {
            $result =  [
                __INIT__['ErrorReturnJsonMsg']['name']=>'系统繁忙['.$str_rand.']',
                __INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                'error'=>$str_rand,
            ];
        }
        /**
         * 判断区间
         */
        foreach(\ErrorOrLog::CODE_SECTION as $key=>$value)
        {

            /**
             * 判断范围
             */
            if($value[0] < $this->exception->getCode() &&  $this->exception->getCode()<$value[1])
            {
                if($key === 'SYSTEM_CODE')
                {
                    if(isset(\ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()]))
                    {
                        $result =  [
                            __INIT__['ErrorReturnJsonMsg']['name']=>
                                is_int(\ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()][0])?
                                    \ErrorOrLog::HINT_MSG[\ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()][0]]:
                                    \ErrorOrLog::SYSTEM_CODE[$this->exception->getCode()][0].'['.$str_rand.']',
                            __INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                            'error'=>$str_rand,
                        ];
                    }

                }else if($key === 'USE_CODE')
                {
                    if(isset(\ErrorOrLog::USE_CODE[$this->exception->getCode()]))
                    {
                        $result =  [
                            __INIT__['ErrorReturnJsonMsg']['name']=>
                                is_int(\ErrorOrLog::USE_CODE[$this->exception->getCode()][0])?
                                    \ErrorOrLog::HINT_MSG[\ErrorOrLog::USE_CODE[$this->exception->getCode()][0]].'['.$str_rand.']':
                                    \ErrorOrLog::USE_CODE[$this->exception->getCode()][0].'['.$str_rand.']',
                            __INIT__['ErrorReturnJsonCode']['name']=>$this->exception->getCode(),
                            'error'=>$str_rand,
                        ];
                    }
                }
            }
        }
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
        $log = new Logger('name');
        $log->pushHandler(new StreamHandler($this->path.'/your.log', Logger::WARNING));
        // 添加日志记录
        //$log->addWarning('Foo',$this->exploitData());
        //$log->addError('Bar',$result);

    }

}