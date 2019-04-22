<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 16:24
 * @title 权限控制基础类 
 */

namespace pizepei\staging;


use pizepei\model\redis\Redis;
use service\basics\account\AccountService;

class Authority
{
    /**
     * 状态
     * @var bool
     */
    private $status = false;
    /**
     * 状态码
     * @var bool
     */
    private $code = false;
    /**
     * 说明
     * @var string
     */
    private $msg = '';

    /**
     * jwt模式
     * @var string
     */
    private $pattern = '';

    /**
     * Authority constructor.
     *
     * @param $parameter
     * @param $pattern
     */
    public function __construct($parameter,$pattern)
    {
        if($parameter === 'punlic'){
            $this->status = true;
        }
        
        $this->pattern = $pattern;
        /**
         * 权限控制方法
         */
        $this->$parameter();
    }

    /**
     * 统一返回
     * @return array
     */
    public function init()
    {
        return ['status'=>$this->status,'cdoe'=>$this->code];
    }

    /**
     * 判断是否登录
     */
    public function WhetherTheLogin()
    {
        // *方法路由：注册到不同操作权限资源里面用authGroup【admin.bbx:user.bbx】中文名字、注册扩展扩展authExtend  控制器：方法（方法里面有返回数据、）
        $AccountService = new AccountService();
        $Redis = Redis::init();
        var_dump($_SERVER);
        return $AccountService->decodeLogonJwt($this->pattern,$_SERVER['HTTP_ACCESS_TOLEN'],$Redis);

    }
}