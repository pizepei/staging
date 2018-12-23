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
     * 获取到所以的请求
     */
    protected function __construct()
    {
        $this->GET = $_GET;
        unset($this->GET['s']);
//        $this->COOKIE = $_COOKIE;
        $this->POST = $_POST;
        $this->PATH = [];
        $this->RAW = [];
        /**
         * 释放内存
         */
        /**
         * 判断模式exploit调试模式不释放$_POST、$_GET内存
         */
        if(__INIT__['pattern'] != 'exploit'){
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
            $this->Route = Route::init();
        }
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
     * 获取数据
     * @param string $name  ['get','key']  或者字符串key
     * @param string $type  获取的请求数据类型
     * @return bool
     */
    public function input($name = '',$type='get')
    {
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

            $this->paramFiltration($this->$TypeS,$type);
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
        if(file_get_contents("php://input") == ''){
            $this->RAW = null;
        }
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
            $this->RAW = json_decode(file_get_contents("php://input",'r'),true);
        }
        /**
         * xml
         */
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/xml'){
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
    /**
     * 请求参数过滤
     */
    protected function paramFiltration(&$data,$type)
    {
        $this->initRoute();
        $Param = $this->Route->atRouteData['Param'][$type];
        /**
         * 获取数据格式
         */
        $format = $Param['fieldRestrain'][0];
        $noteData = &$Param['substratum'];
        $this->paramFiltrationRecursive($data,$noteData);

    }
    /**
     * 递归函数处理数据类型转换
     * @param $data
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
        if(__INIT__['requestParam']){$this->unsetParam($data,$noteData,$type);}
        foreach($noteData as $k=>$v){
            /**
             * 判断类型
             */
            if(in_array($v['fieldRestrain'][0],$this->Route::ReturnType)  ){
                $this->eturnSubjoin($data,$k,$v,$type);
            }else if((in_array($v['fieldRestrain'][0],$this->Route::ReturnFormat) && $type =='objectList') ){
                if($v['fieldRestrain'][0] == 'object'){
                    $this->eturnSubjoin($data,$k,$v,'object');
                }else{

                    $this->eturnSubjoin($data,$k,$v,'objectList');
                }

            }else if(in_array($v['fieldRestrain'][0],$this->Route::ReturnFormat)){
                /**
                 * 数据格式 一般情况是数组
                 */
                if(isset($v['substratum'])){
                    if($v['fieldRestrain'][0] == 'object'){
                        $data[$k] = $data[$k]??'';
                        $data[$k] = json_decode($data[$k],true);
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
     * @title  检测是否有参数约束
     */
    protected function eturnSubjoin(&$data,$key,$noteData,$type)
    {
        //var_dump($key);
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
                                if(in_array($vvv['fieldRestrain'][0],$this->Route::ReturnFormat)){
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
                    //var_dump($vv[$key]);
                    //var_dump($fieldRestrain);
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

            //var_dump($key);
            //var_dump(count($noteData['fieldRestrain']));
            /**
             * 判断是否存在多的
             */
            //if(count($noteData['fieldRestrain']) <= 1){
                if(isset($data[$key])) {settype($data[$key],$noteData['fieldRestrain'][0]);}
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
     * @param $data
     * @param $noteData
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
                        if(!array_key_exists($pk,$noteData)){ unset($data[$pk]);}
                }else if($type == 'objectList'){

                    if(!is_array($pv)){throw new \Exception('非法的数据结构');}
                    foreach($pv as $kk =>&$vv){
                        if(is_array($vv)){
                            $type = 'objectList';
                            if($noteData[$kk]['fieldRestrain'][0] == 'object'){
                                $type = 'object';
                            }
                            $this->unsetParam($vv,$noteData[$kk]['substratum'],$type);
                        }else{
                            if(!array_key_exists($kk,$noteData)){ unset($data[$pk][$kk]);var_dump($kk);}
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
        if(!self::$object) self::$object = new static();
        
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
     * @param  string 	$xml xml字符串
     **/
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
                        if($i > __INIT__['requestParamTier']){
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
        foreach ($header as $k=>$v){
            header("{$k}: {$v}");
        }
    }

}