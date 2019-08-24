<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 16:24
 * @title 权限控制基础类 
 */
declare(strict_types=1);

namespace pizepei\staging;

use pizepei\container\Container;

class  Authority extends Container
{
    /**
     * 容器标识
     */
    const CONTAINER_NAME = 'Authority';
    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [

    ];
    public function __construct(string $son = '')
    {
        if ($son !==''){
            # 判断是否存在
            if($son::bind !== [])
            {
                #合并
                $this->bind = array_merge($son::bind ,$this->bind);
            }
        }
        self::$containerInstance[static::CONTAINER_NAME] = $this;
    }
//    /**
//     * Authority constructor.
//     * @param $pattern
//     * @param App $app
//     */
//    public function __construct($pattern,App $app);
//    /**
//     * @Author 皮泽培
//     * @Created 2019/8/22 14:21
//     * @param $name
//     * @title  路由标题
//     * @return mixed
//     * @throws \Exception
//     */
//    public function __get($name);
//    /**
//     * 判断是否登录
//     * @throws \Exception
//     */
//    public function isLogin();
//    /**
//     * 判断是有权限
//     * @throws \Exception
//     */
//    public function grantCheck();

}