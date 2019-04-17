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

class MyException
{
    public function __construct() {
        @set_exception_handler(array($this, 'exception_handler'));
        //throw new Exception('DOH!!');
    }

    //protected string $message ;        //异常消息内容
    //
    //protected int $code ;            //异常代码
    //
    //protected string $file ;        //抛出异常的文件名
    //
    //protected int $line ;            //抛出异常在该文件中的行号
    //
    //    /* 方法 */
    //
    //public construct ([ string $message = "" [, int $code = 0 [, Exception $previous = NULL ]]] )    //异常
    //
    //构造函数
    //
    //final public string getMessage ( void )            //获取异常消息内容
    //
    //final public Exception getPrevious ( void )        //返回异常链中的前一个异常
    //
    //final public int getCode ( void )                //获取异常代码
    //
    //final public string getFile ( void )            //获取发生异常的程序文件名称
    //
    //final public int getLine ( void )                //获取发生异常的代码在文件中的行号
    //
    //final public array getTrace ( void )            //获取异常追踪信息
    //
    //final public string getTraceAsString ( void )    //获取字符串类型的异常追踪信息
    //
    //public string toString ( void )                //将异常对象转换为字符串
    //
    //final private void clone ( void )                //异常克隆
    /**
     * 异常类
     * @var null
     */
    private $exception = null;
    public function exception_handler($exception) {
        header("Content-Type:application/json;charset=UTF-8");

        $this->exception = $exception;
        /**
         * 判断是否是开发模式
         */
        if(!__EXPLOIT__){
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
    }
    private function exploit($exception)
    {
        echo json_encode($this->resultData($this->exception->getMessage(),$this->exception->getCode(),$this->exploitData()),JSON_UNESCAPED_UNICODE);
    }

    private  function resultData($msg,$code,$data=[])
    {
        $result =  [
            __INIT__['ErrorReturnJsonMsg']['name']=>$msg,
            __INIT__['ErrorReturnJsonCode']['name']=>$code,
            __INIT__['ReturnJsonData']=>$data,
        ];
        return $result;
    }

    private function getErrorCode(){
        /**
         * 构造错误代码和提示（生产时使用）
         */
    }
    private function exploitData()
    {
        $Route = Route::init();
        return [
            'route'=>[
                'controller'=>$Route->controller.'->'.$Route->method,
                //'d'=>$Route->atRouteData,
                'router'=>$Route->atRoute,
            ],
            'File'=>$this->exception->getFile().'['.$this->exception->getLine().']',
            'Trace'=>$this->getTrace(),
            'ErrorOrLog'=>\ErrorOrLog::CODE_SECTION,
            'setCodeCipher'=>$this->setCodeCipher(),

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
            unset($value['args']);
            $array[] = $value;
        }
        return $array;
    }

    /**
     * 创建错误代码
     * 用以生产
     */
    protected function setCodeCipher()
    {
        //确定错误代码
        /**
         *  0  默认
         */
        if($this->exception->getCode() === 0)
        {

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
                        $str_rand = Func::M('str')::str_rand(10);
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
                        $str_rand = Func::M('str')::str_rand(15);
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
        return $result;

    }

}