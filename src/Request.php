<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/3
 * Time: 9:37
 * @title 请求类
 */
declare(strict_types=1);

namespace pizepei\staging;


use pizepei\func\Func;
use pizepei\helper\Helper;

class Request
{
    /**
     * 数据类型转换（在获取参数是进行过滤数据类型转换）
     *  根据路由数据
     *      处理返回参数
     *          区分php://input  post  get
     *      请求来的参数
     * 根据要求返回不同的http请求
     * 根据要求返回不同的数据类型
     */

    /**
     * 当前对象
     * @var null
     */
    private static $object = null;
    /**
     * 请求id  （uuid）
     * @var null
     */
    protected  $RequestId = null;
    /**
     * 应用容器
     * @var App|null
     */
    protected $app = null;

    /**
     * Request constructor.
     * @param App $app
     * @throws \Exception
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        if (Helper::init()->is_empty($_SERVER['PATH_INFO'])){
            $this->GET = $_GET;
            unset($this->GET['s']);
        }

//        $this->COOKIE = $_COOKIE;
        $this->POST = $_POST;
        $this->PATH = [];
        $this->RAW = [];

        /**
         * 生成请求id
         */
        $this->RequestId = Helper::init()->getUuid(true,45,$app->__INIT__['uuid_identifier']);
        /**
         * 释放内存
         */
        /**
         * 判断模式exploit调试模式不释放$_POST、$_GET内存
         */
        if($app->__INIT__['pattern'] != 'exploit'){
            $_POST = null;
            //$_GET = null;
            //$_COOKIE = null;
            //$this->FILES = $_FILES;
        }
    }


    /**
     * 路由对象
     * @var null
     */
    protected $Route = null;

    /**
     * 初始化路由对象
     */
    protected function initRoute(){
        if($this->Route == null){
            $this->Route = $this->app->Route();
        }
    }

    /**
     * @param $vname
     * @return
     */
    public function __get($vname){
        return $this->$vname;
    }
    /**
     * 获取 PATH 变量
     * @param $name
     * @return mixed|null
     */
    public function path($name ='')
    {
        if($name ===''){
            return $this->PATH;
        }
        return $this->PATH[$name]??null;
    }
    protected $inputType = ['post','get','raw'];
    protected $status = [
        'GET'=>false,
        'POST'=>false,
        'PATH'=>false,
        'RAW'=>false,
    ];

    /**
     * 获取post
     * @param string $name
     * @return null
     * @throws \Exception
     */
    public function post($name='')
    {
        return $this->input($name,'post');
    }

    /**
     * 获取raw
     * @param string $name
     * @return null
     * @throws \Exception
     */
    public function raw($name='')
    {
        return $this->input($name,'raw');
    }
    /**
     * 获取非路径参数外的参数数据
     * @param string $name  ['get','key']  或者字符串key
     * @param string $type  获取的请求数据类型
     * @return null
     * @throws \Exception
     */
    public function input($name = '',$type='get')
    {
        $this->initRoute();
        /**
         * 判断参数
         */
        if(is_array($name) && count($name)==2){
            $type = $name[0];
            $name = $name[1];
        }
        if(!in_array($type,$this->inputType)){
            throw new \Exception('错误的类型：'.$type);
        }
        $TypeS = strtoupper($type);
        /**
         * 判断是否已经进行了数据处理
         */
        if(!$this->status[$TypeS]){
            /**
             * 没有进行处理
             */
            if($TypeS == 'RAW'){
                $this->getRaw();
            }

            if(isset($this->Route->atRouteData['Param']['raw']['fieldRestrain'][1])){
                if(isset($this->Route->atRouteData['Param']['raw']['fieldRestrain'][1]) == 'raw'){
                    /**
                     * 对数据不做处理
                     */
                }
            }else{
                $this->paramFiltration($this->$TypeS,$type);
            }


            /**
             * 处理完成修改状态
             */
            $this->status[$TypeS] = true;
        }
        /**
         * 判断是否是获取全部
         */
        if($name == ''){
            return $this->$TypeS;
        }
        return $this->$TypeS[$name]??null;
    }
    /**
     * 获取php://input数据
     */
    protected function getRaw()
    {
        /**
         * 判断是否定义数据类型
         */
        if(isset($this->Route->atRouteData['Param']['raw']['fieldRestrain'][0]) && $_SERVER['HTTP_CONTENT_TYPE'] !== 'application/xml'){

            if($this->Route->atRouteData['Param']['raw']['fieldRestrain'][0] == 'xml'){
                $this->RAW = $this->xmlToArray(file_get_contents("php://input",'r'));

            }else if($this->Route->atRouteData['Param']['raw']['fieldRestrain'][0] == 'json'){
                $this->RAW = json_decode(file_get_contents("php://input",'r'),true);
            }else if($this->Route->atRouteData['Param']['raw']['fieldRestrain'][0] == 'url'){
                /**
                 * application/x-www-form-urlencoded方式是Jquery的Ajax请求默认方式
                 * 在请求发送过程中会对数据进行序列化处理，以键值对形式？key1=value1&key2=value2的方式发送到服务器
                 */
                parse_str(file_get_contents("php://input"),$this->RAW);
            }else{
                $this->RAW = json_decode(file_get_contents("php://input",'r'),true);
            }
        }else{

            if(!isset($_SERVER['HTTP_CONTENT_TYPE'])){
                $this->RAW = json_decode(file_get_contents("php://input",'r'),true);
                if(!$this->RAW){
                    parse_str(file_get_contents("php://input"),$this->RAW);
                }
            }
            /**
             * 没有定义
             */
            if(file_get_contents("php://input") == ''){
                $this->RAW = null;
            }
            if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
                $this->RAW = json_decode(file_get_contents("php://input",'r'),true);
            }
            /**
             * xml
             */
            if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/xml' || $_SERVER['HTTP_CONTENT_TYPE'] == 'text/xml'){
                $this->RAW = $this->xmlToArray(file_get_contents("php://input",'r'));
            }
            /**
             * application/x-www-form-urlencoded方式是Jquery的Ajax请求默认方式
             * 在请求发送过程中会对数据进行序列化处理，以键值对形式？key1=value1&key2=value2的方式发送到服务器
             */
            if($_SERVER['HTTP_CONTENT_TYPE'] == 'text/plain'|| !isset($_SERVER['HTTP_CONTENT_TYPE']) || $_SERVER['HTTP_CONTENT_TYPE'] == 'application/x-www-form-urlencoded'){
                $this->RAW = json_decode(file_get_contents("php://input",'r'),true);
                if(!$this->RAW){
                    parse_str(file_get_contents("php://input"),$this->RAW);
                }
            }
        }

    }
    /**
     * 请求参数过滤
     */
    protected function paramFiltration(&$data,$type)
    {
        $this->initRoute();
        if(!isset($this->Route->atRouteData['Param'])){
            $data = null;
            return false;
        }
        if(!isset($this->Route->atRouteData['Param'][$type])){
            return null;
        }
        $Param = $this->Route->atRouteData['Param'][$type];
        /**
         * 获取数据格式
         */
        $format = $Param['fieldRestrain'][0];
        $noteData = &$Param['substratum'];
        $this->paramFiltrationRecursive($data,$noteData,$format);
    }

    /**
     * @param        $data
     * @param string $type
     * @return null
     * @throws \Exception
     * @Author 皮泽培
     * @Created 2019/6/12 17:42
     * @title  过滤return参数
     * @explain 过滤控制器return参数
     */
    public function returnParamFiltration(&$data,$type='')
    {
        $this->initRoute();
        $this->Route->Return;
        if (!isset($this->Route->Return['data'])){
            return null;
        }
        if ($type ==''  && $this->Route->Return['data']['fieldRestrain'][0] =='raw')
        {
            return $data;
        }
        //开始
        $this->paramFiltrationRecursive($data,$this->Route->Return['data']['substratum'],$type==''?$this->Route->Return['data']['fieldRestrain'][0]:$type);
        return $data;
    }

    /**
     * 递归函数处理数据类型转换
     *
     * @param        $data
     * @param        $noteData
     * @param string $type
     * @throws \Exception
     */
    protected function paramFiltrationRecursive(&$data,$noteData,$type='object')
    {
        static $iii = 0;
        $iii++;
        /**
         * 安全策略
         */
        if($iii > __INIT__['requestParamTier']){
            throw new \Exception('请求数据超过限制:参数层级超过限制');
        }
        /**
         * 对请求参数进行过滤（删除不在注解中的参数key）
         */
        //var_dump($noteData);
        if($this->app->__INIT__['requestParam']){$this->unsetParam($data,$noteData,$type);}
        foreach($noteData as $k=>$v){
            /**
             * 判断类型
             */
            if(in_array($v['fieldRestrain'][0],($this->app->Route())::RequestParamDataType)  ){
                /**
                 * 参数过滤（约束）
                 */
                $this->eturnSubjoin($data,$k,$v,$type);
            }else if((in_array($v['fieldRestrain'][0],$this->app->Route()::ReturnFormat) && $type =='objectList') ){
                if($v['fieldRestrain'][0] == 'object'){
                    /**
                     * 参数过滤（约束）
                     */
                    $this->eturnSubjoin($data,$k,$v,'object');
                }else if($v['fieldRestrain'][0] == 'objectList'){
                    /**
                     * 参数过滤（约束）
                     */
                    $this->eturnSubjoin($data,$k,$v,'objectList');
                }else if($v['fieldRestrain'][0] == 'raw'){
                    //没有限制
                }

            }else if(in_array($v['fieldRestrain'][0],$this->app->Route()::ReturnFormat)){
                /**
                 * 数据格式 一般情况是数组
                 */
                if(isset($v['substratum'])){
                    if($v['fieldRestrain'][0] == 'object'){
                        $data[$k] = $data[$k]??'';

                        if (!is_array($data[$k]))
                        {
                            $data[$k] = json_decode($data[$k],true);
                        }
                        $this->paramFiltrationRecursive($data[$k],$noteData[$k]['substratum']);
                    }else if($v['fieldRestrain'][0] == 'objectList'){
                        $data[$k] = $data[$k]??'';
                        if(!is_array($data[$k])){
                            $data[$k] = json_decode($data[$k],true);
                            /**
                             * 判断json_decode 后依然不是array 就不是规范的参数
                             */
                            if(!is_array($data[$k])){$data[$k]=[[]];}
                        }
                        $this->paramFiltrationRecursive($data[$k],$noteData[$k]['substratum'],'objectList');
                    }
                }
            }
        }
    }

    /**
     * @Author: pizepei
     * @Created: 2018/10/13 23:23
     * @param $data
     * @param $key
     * @param $noteData
     * @param $type
     * @throws \Exception
     * @title  检测是否有参数约束
     */
    protected function eturnSubjoin(&$data,$key,$noteData,$type)
    {
        /**
         * 数据类型转换
         */
        if($type =='objectList'){//索引array(递归函数里面)
            /**
             * 判断是否存在多的
             */
            if(count($noteData['fieldRestrain']) <= 1){
                foreach($data as $kk=>&$vv){
                    /**
                     * 上级别是objectList
                     * 本级别依然是objectList
                     */
                    if($noteData['fieldRestrain'][0] == 'objectList'){

                        foreach($noteData['substratum'] as $kkk=>&$vvv){
                                if(in_array($vvv['fieldRestrain'][0],$this->app->Route()::ReturnFormat)){
                                    /**
                                     *
                                     */
                                    $return = $this->paramFiltrationRecursive($vv[$key],$noteData['substratum'],'objectList');
                                    //if($return){
                                        //$vv[$key][] = '';
                                    //}
                                }else{
                                    if(!isset($vv[$key])){$vv[$key] =[[]];}
                                    foreach($vv[$key] as $kkkk=>&$vvvv){
                                        if(isset($vvvv[$kkk])) {settype($vvvv[$kkk],$vvv['fieldRestrain'][0]);}
                                    }
                                }
                        }
                    }else{
                        if(isset($vv[$key])) {settype($vv[$key],$noteData['fieldRestrain'][0]);}
                    }
                }
            }
            if(count($noteData['fieldRestrain']) > 1){
                $fieldRestrain = $noteData['fieldRestrain'][0];
                unset($noteData['fieldRestrain'][0]);
                foreach($data as $kk=>&$vv){
                    /**
                     * 如果存在就类型转换
                     */
                    isset($vv[$key]);
                    if($fieldRestrain == 'objectList' || empty($vv[$key])) {throw new \Exception($key.'['.$key.']是必须的');}
                    
                    if(isset($vv[$key])) {settype($vv[$key],$fieldRestrain);}
                    foreach($noteData['fieldRestrain'] as $k=>$v){
                        if(isset($this->Route->ReturnSubjoin[$v])){
                            if(!isset($vv[$key]) || $vv[$key] ==''){
                                throw new \Exception($noteData['fieldExplain'].'['.$key.']是必须的');
                            }
                            if($this->Route->ReturnSubjoin[$v][1] != 'empty'){
                                preg_match($this->Route->ReturnSubjoin[$v][1],$vv[$key],$result);
                                if(empty($result) || $result ==null){
                                    throw new \Exception($noteData['fieldExplain'].'['.$key.']:'.'格式错误');
                                }
                            }
                        }
                    }
                }
            }
        }else if($type =='object'){//非索引array
            /**
             * 判断是否存在多的
             */
            //if(count($noteData['fieldRestrain']) <= 1){
            /**
             * 进行数据类型转换 $data = null;isset($data);返回false
             */
            if(isset($data[$key]) && $noteData['fieldRestrain'][0] !=='object' && $noteData['fieldRestrain'][0] !=='objectList' && $data[$key] === null)
            {
                if(is_array($data[$key]))
                {
                    $data[$key] = json_encode($data[$key],LIBXML_NOCDATA);
                }
                settype($data[$key],$noteData['fieldRestrain'][0]);
            }
            //}
            unset($noteData['fieldRestrain'][0]);
            foreach($noteData['fieldRestrain'] as $k=>$v){
                if(isset($this->Route->ReturnSubjoin[$v])){
                    if(!isset($data[$key]) || $data[$key] ==''){
                        throw new \Exception($noteData['fieldExplain'].'['.$key.']是必须的');
                    }
                    if($this->Route->ReturnSubjoin[$v][1] != 'empty'){
                        preg_match($this->Route->ReturnSubjoin[$v][1],$data[$key],$result);
                        if(empty($result) || $result ==null){
                            throw new \Exception($noteData['fieldExplain'].'['.$key.']:'.'格式错误');
                        }
                    }
                }
            }

        }


    }

    /**
     * @Author: pizepei
     * @Created: 2018/10/12 23:08
     * @param        $data
     * @param        $noteData
     * @param string $type
     * @throws \Exception
     * @title  对请求参数进行过滤（删除不在注解中的参数key） 测是否有参数约束
     */
    protected  function unsetParam(&$data,$noteData,$type='object')
    {
        /**
         * 对请求参数进行过滤（删除不在注解中的参数key）
         */
        if(isset($data) && is_array($data)){
            foreach($data as $pk=>&$pv){
                if($type == 'object'){
                    //if(!isset($noteData[$pk])){
                    //    if(!array_key_exists($pk,$noteData)){ unset($data[$pk]);}
                    //}
                    if(isset($noteData[$pk])){
                        if($noteData[$pk]['fieldRestrain'][0] != 'raw'){
                            if(!array_key_exists($pk,$noteData)){
                                unset($data[$pk]);
                            }else{
                                if (!isset($noteData[$pk]['substratum']) && ($noteData[$pk]['fieldRestrain'][0] =='object' || $noteData[$pk]['fieldRestrain'][0] =='objectList'))
                                {
                                    throw new \Exception('参数: '.$pk.' ['.$noteData[$pk]['fieldRestrain'][0].']不能没有下级或可使用[raw]忽略参数限制');
                                }
                            }
                        }
                    }else{
                        /**
                         * 删除不在注解中的参数key
                         */
                        unset($data[$pk]);
                    }

                }else if($type == 'objectList'){

                    if(!is_array($pv)){
                        throw new \Exception('非法的数据结构:'.$pk.'上级应该是['.$type.']');
                    }
                    if (!is_int($pk)){unset($data[$pk]);}//删除分索引数组的非法数据
                    foreach($pv as $kk =>&$vv){
                        if(is_array($vv)){
                            $type = 'objectList';
                            if (!isset($noteData[$kk])){unset($pv[$kk]);continue;}

                            if($noteData[$kk]['fieldRestrain'][0] == 'object'){
                                $type = 'object';
                                $this->unsetParam($vv,$noteData[$kk]['substratum'],$type);
                            }else if($noteData[$kk]['fieldRestrain'][0] != 'raw'){
                                $this->unsetParam($vv,$noteData[$kk]['substratum'],$type);
                            }

                        }else{
                            if(!array_key_exists($kk,$noteData)){ unset($data[$pk][$kk]);}
                        }
                    }
                }
            }
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
        if(!self::$object) {self::$object = new static();}
        
        return self::$object;

    }
    /**
     * 重定向请求
     * @param $url
     */
    public function Redirect($url)
    {
        header("Location: {$url}",true,301);
    }
    /**
     * 将xml转为array
     * @param  string 	$xml xml字符串或者xml文件名
     * @param  bool 	$isfile 传入的是否是xml文件名
     * @return array    转换得到的数组
     */
     public function xmlToArray($xml,$isfile=false){
         //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        if($isfile){
            if(!file_exists($xml)) return false;
            $xmlstr = file_get_contents($xml);
        }else{
            $xmlstr = $xml;
        }
        $result= json_decode(json_encode(simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     * 数组转xml字符
     *
     * @param        $data
     * @param string $name
     * @return bool|string
     * @throws \Exception
     */
    function arrayToXml($data,$name='xml'){

        if(!is_array($data) || count($data) <= 0){
            return false;
        }
        static $i =1;
        $i++;
        if($name == 'xml'){
            $xml = '<?xml version="1.0" encoding="UTF-8" ?><'.$name.'>';
        }else{
            $xml = '<'.$name.'>';
        }
        foreach ($data as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                if(is_array($val)){
                    foreach($val as $k=>$v){
                        /**
                         * 安全策略
                         */
                        if($i > $this->app->__INIT__['requestParamTier']){
                            throw new \Exception('请求数据超过限制:xml层级超过限制');
                        }
                        /**
                         * 策略数据
                         */
                        if(is_array($v)){
                            $xml .=$this->arrayToXml($v,$key);
                        }else{
                            $xml .=$this->arrayToXml($val,$key);
                        }
                    }
                }else{
                    $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                }
            }
        }
        $xml.= "</".$name.">";

        return $xml;
    }

    /**
     * 设置产生url
     *
     * @param $route    路由地址
     * @param $data     需要传递的参数
     * @return string
     */
    public function setUrl($route,$data =[])
    {
        /**
         * 判断是否有参数
         */
        $para = '';
        if(!empty($data)){
            /**
             * 拼接
             */
            foreach ($data as $k=>$v){
                $para .= $k.'='.$v.'&';
            }
            $para = rtrim($para, "&");
            $route = $route.'?'.$para;
        }
        if(isset($_SERVER['HTTPS'])){
            $http = $_SERVER['HTTPS'] == 'on'?'https://':'http://';
        }else{
            $http = 'http://';
        }
        return $http.$_SERVER['HTTP_HOST'].$route;
    }
    /**
     * 自定义响应header
     */
    public function setHeader($header)
    {
        if($this->app->__PATTERN__ === 'WEB'){
            foreach ($header as $k=>$v){
                @header("{$k}: {$v}");
            }
        }
    }

    /**
     * Content-Type
     * http://tool.oschina.net/commons/
     */
    const Header = [
        'txt'=>['Content-Type'=>'text/plain'],
        'png'=>['Content-Type'=>'image/png'],
        'html'=>['Content-Type'=>'text/html; charset=UTF-8'],
        'json'=>['Content-Type'=>'application/json;charset=UTF-8'],
        'gif'=>['Content-Type'=>'image/gif'],
        'xml'=>['Content-Type'=>'text/xml'],

    ];

}