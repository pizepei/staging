<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/4/16
 * Time: 17:17
 * @title 异常处理继承Exception
 */

namespace pizepei\staging;


class Exception extends \Exception
{
    ///* 属性 */
    //
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

    protected $ErrorInfo;

    //构造函里处理一些逻辑，然后将一些信息传递给基类

    public function construct($message=null,$code=0)
    {
        $this->ErrorInfo = '自定义错误类的错误信息';
        parent::construct($message,$code);
    }

    //提供获取自定义类信息的方法

    public function GetErrorInfo()

    {
        return $this->ErrorInfo;

    }

    /**
     *
     *这里还可以添加异常日志,只需在上面的构造函数里调用就可以了
     *
     */
    public function log($file)

    {
        file_put_contents($fiel,$this->toString(),FILE_APPEND);

    }




}