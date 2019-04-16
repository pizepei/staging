<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/4/16
 * Time: 17:25
 * @title 异常处理类配合set_exception_handler
 */

namespace pizepei\staging;


class MyException
{
    public function __construct() {
        @set_exception_handler(array($this, 'exception_handler'));
        //throw new Exception('DOH!!');
    }

    public function exception_handler($exception) {
        print "Exception Caught: ". $exception->getMessage() ."\n";
    }

    //public function production($errno, $errstr, $errfile, $errline)
    //{
    //    echo 'ssss';
    //}
    //public static function exploit($errno, $errstr, $errfile, $errline)
    //{
    //    echo 'exploit';
    //}
}