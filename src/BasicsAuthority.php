<?php
/**
 * 基础权限类
 */
declare(strict_types=1);

namespace pizepei\staging;


use pizepei\basics\service\account\BasicsAccountService;
use pizepei\model\redis\Redis;

class BasicsAuthority
{
    /**
     * 状态
     * @var bool
     */
    protected $status = false;
    /**
     * 状态码
     * @var bool
     */
    protected $code = false;
    /**
     * 说明
     * @var string
     */
    protected $msg = '';

    /**
     * jwt模式
     * @var string
     */
    protected $pattern = '';

    /**
     * 当前权限控制信息（扩展）
     * @var array
     */
    protected $authExtend=[];
    /**
     * 解密的jwt Payload信息
     * @var array
     */
    protected $Payload = [];

    protected $parameter = '';
    /**
     * @var App|null
     */
    protected $app = null;
    /**
     * Authority constructor.
     * @param $pattern 模式  比如 jwt模式
     * @param App $app
     */
    public function __construct($pattern,App $app)
    {
        $this->app = $app;
        # jwt模式
        $this->pattern = $pattern;
    }
    /**
     *  获取 property
     * @param $propertyName
     * @return |null
     */
    public function __get($propertyName)
    {
        if(isset($this->$propertyName)){
            return $this->$propertyName;
        }
        return null;
    }
    /**
     * 统一返回
     * @param $parameter 方法
     * @return array
     */
    public function start($parameter)
    {
        if($parameter === 'public'){
            $this->status = true;
        }
        return $this->$parameter();
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/22 17:49
     * @return bool 是否登录
     * @title  路由标题
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function login():bool
    {


    }


    /**
     * 判断是否登录
     * @throws \Exception
     */
    public function WhetherTheLogin()
    {
        // *方法路由：注册到不同操作权限资源里面用authGroup【admin.bbx:user.bbx】中文名字、注册扩展扩展authExtend  控制器：方法（方法里面有返回数据、）
        $AccountService = new BasicsAccountService();
        $Redis = Redis::init();
//        var_dump($_SERVER);
        if (!isset($this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]) || $this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']] ==''){throw new \Exception('非法请求[TOKEN]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        $this->Payload =  $AccountService->decodeLogonJwt($this->pattern,$this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]??'',$Redis);
    }
    /**
     * 权限判断(使用数据缓存或者数据库的版本)
     * @param array $data 权限数据集合
     * @throws \Exception
     */
    public function jurisdictionTidy(array $data)
    {
        $Route = $this->app->Route();
        $Route->authTag;

        if(!isset($data[$Route->authTag])){
            throw new \Exception('无权限',\ErrorOrLog::JURISDICTION_CODE);
        }
        /**
         * 判断是否存在扩展信息
         */
        $this->authExtend = $data[$Route->authTag]['extend']??[];
        /**
         * 当前账号的权限集合
         * 当前路由的权限的tag
         * 获取自定义资源
         *
         * 1、通过判断路由唯一标识 是否在用户权限数据集合中判断是否有权限
         *
         * 2、有权限就通过  routeAuthExtend  使用对应的类的方法 设置对应的拓展属性
         */

    }

    /**
     * 权限判断（根据配置文件和jwt信息自己判断权限 服务端和客户端都不保存详细客户信息）
     * @param $data 权限数据集合
     * @param $tag 当前路由tag
     */
    public function jurisdiction($data,$tag)
    {
        /**
         * 当前账号的权限
         */
    }
}