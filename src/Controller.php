<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 17:48
 * @title 控制器基类
 */
declare(strict_types=1);

namespace pizepei\staging;
class Controller
{
    /**
     * 应用容器
     * @var App|null
     */
    protected $app = null;
    /**
     * Controller constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $Route = $this->app->Route();
        # 判断是否有设置权限控制器
        if(isset($Route->baseAuth[0]) && isset($Route->baseAuth[1]) && $Route->baseAuth[1] !='public')
        {
            $className = $Route->baseAuth[0];
            $Authority = $this->app->Authority()->$className('common',$this->app);
            $authResult = $Authority->start($Route->baseAuth[1]);
            $this->authExtend = $Authority->authExtend;
            $this->Payload = $Authority->Payload;
        }
    }
    /**
     * 获取Extend
     * @param string $key
     * @return mixed
     */
    public function getAuthExtend($key ='')
    {
        return $this->jurisdictionExtend[$key]??null;
    }

    /**
     * 视图
     * @param string $name
     * @param array  $data
     * @return string|array
     */
    public function view(string $name = '',array$data = [],string $path='',string $type='html'):string
    {

        if ($path ==''){
            $path = $this->app->__TEMPLATE__;
        }
        /**
         * 默认路径
         */
        if($name == '')
        {
            /**
             * 自动拼接地址
             * __TEMPLATE__命名空间
             */
        }else{

        }
        $file = file_get_contents($path.$name.'.'.$type);
        if(!empty($data))
        {
            foreach($data as $key=>$vuleu)
                if(!is_array($vuleu)){
                    $file = str_replace("'{{{$key}}}'",$vuleu,$file);
                    $file = str_replace("{{{$key}}}",$vuleu,$file);
                }
        }
        //require();
        return $file;
    }

    /**
     * @Author pizepei
     * @Created 2019/2/15 23:14
     *
     * @Author pizepei
     * @Created 2019/2/15 23:02
     *
     * @param     $data
     * @param     $msg 状态说明
     * @param     $code 状态码
     * @param int $count sss
     *
     * @return array
     * @title  控制器成功返回
     */
    public function succeed($data,$msg='',$code='',$count=0)
    {
        $result =  [
            $this->app->__INIT__['SuccessReturnJsonMsg']['name']=>$msg==''?$this->app->__INIT__['SuccessReturnJsonMsg']['value']:$msg,
            $this->app->__INIT__['SuccessReturnJsonCode']['name']=>$code==''?$this->app->__INIT__['SuccessReturnJsonCode']['value']:$code,
            $this->app->__INIT__['ReturnJsonData']=>$data,
        ];
        if($count>0){
            $result[$this->app->__INIT__['ReturnJsonCount']] = $count;
        }else{
            $result[$this->app->__INIT__['ReturnJsonCount']] = is_array($data)?count($data):0;
        }
        return $result;
    }

    /**
     * @Author pizepei
     * @Created 2019/2/15 23:09
     *
     * @param $data 错误详细信息
     * @param $msg  错误说明
     * @param $code  错误代码
     * @return array
     * @title  控制器错误返回
     */
    public function error($data,$msg='',$code='')
    {
        $result =  [
            $this->app->__INIT__['ErrorReturnJsonMsg']['name']=>$msg==''?$this->app->__INIT__['ErrorReturnJsonMsg']['value']:$msg,
            $this->app->__INIT__['ErrorReturnJsonCode']['name']=>$code==''?$this->app->__INIT__['ErrorReturnJsonCode']['value']:$code,
            $this->app->__INIT__['ReturnJsonData']=>$data,
        ];
        return $result;
    }




}