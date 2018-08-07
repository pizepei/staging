<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/3
 * Time: 9:37
 * @title 请求类
 */

namespace pizepei\staging;


class Request
{


    /**
     * 当前对象
     * @var null
     */
    private static $object = null;
    /**
     * 获取到所以的请求
     */
    protected function __construct()
    {
        $this->GET = $_GET;
//        $this->COOKIE = $_COOKIE;
        $this->POST = $_POST;

        /**
         * 释放内存
         */
        $_GET = null;
//        $_COOKIE = null;
        $_POST = null;
//        $this->FILES = $_FILES;
    }

    /**
     * 获取数据
     * @param string $name  ['get','key',''type]  或者字符串key
     * @param string $type  强制数据类型
     * @return bool
     */
    public function input($name = '',$type='')
    {
        /**
         * 默认获取get+post
         * 注意相同key get会被post覆盖
         */
        if($name == ''){
            return $_REQUEST;
        }

        if(is_array($name)){
            /**
             * 获取请求类型
             */
            $type = strtoupper($name[0]);
            if(isset($name[2])){
                settype($this->$type[$name[1]],$name[2]);
            }
            return $this->$type[$name[1]];
        }else{
            if($type != ''){
                settype($this->GET[$name],$type);
            }
            return  $this->GET[$name];

        }

    }
    /**
     * 初始化
     */
    public static  function init()
    {
        /**
         * 判断是否已经有这个对象
         */
//        if(isset($GLOBALS['Request'])){
        if(static::$object != null){
//            return $GLOBALS['Request'];
            return static::$object;
        }
//        $GLOBALS['Request'] = new static();
        static::$object =  new static();
        return static::$object;
    }

}