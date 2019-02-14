<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 16:06
 * @title 路由
 */
namespace pizepei\staging;
use pizepei\config\Config;
use pizepei\model\cache\Cache;


class Route
{

    /**
     * 完善缓存,完善&使用
     * 从头开始整理代码逻辑优化代码
     * 进行测试
     */
    /**
     * 当前对象
     * @var null
     */
    private static $object = null;

    protected $Config = null;
    /**
     * 支持的请求类型
     */
    const RequestType =['GET','POST','PUT','PATCH','DELETE','COPY','HEAD','OPTIONS','LINK','UNLINK','PURGE','LOCK','UNLOCK','PROPFIND','VIEW','CLI'];
    /**
     * 控制器return 返回的数据类型()
     */
    const ReturnType = ['int','string','bool','float','array','null'];

    /**
     * 控制器return 返回的数据类型()
     */
    const ReturnDataType = ['html','xml','json','string'];

    /**
     * 返回数据类型
     * debug 调试模式    auth  权限
     */
    const ReturnFormat = ['debug','auth'];

    /**
     * 附加
     */
    const ReturnAddition = ['list','objectList','object','raw'];

    /**
     * 路由参数附加参数
     * name 【中文说明，表达式或者正则表达式、】
     * 表达式 empty或者函数  不能为空
     */
    protected $ReturnSubjoin = [
        'required'=>['必须的','empty',''],
        'number'=>['手机号码','/^[1][3-9][0-9]{9}$/',''],
        'identity'=>['身份证','/^[1-9]\d{5}(18|19|2([0-9]))\d{2}(0[0-9]|10|11|12)([0-2][1-9]|30|31)\d{3}[0-9X]$/',''],
        'phone'=>['电话号码','/^(0[0-9]{2,3}/-)?([2-9][0-9]{6,7})+(/-[0-9]{1,4})?$/',''],
        'password'=>['密码格式','/^[0-9A-Za-z_\@\*\,\.]{6,23}$/'],
    ];
    /**
     * 应用目录下所有文件路径
     * @var array
     */
    protected $filePathData = [];
    /**
     * 当前请求的控制器
     * @var string
     */
    protected $controller = '';
    /**
     * 当前请求的模块
     * @var string
     */
    protected $module = '';
    /**
     * 当前请求的方法
     * @var string
     */
    protected $method = '';
    /**
     * 当前路径(控制器)
     * @var string
     */
    protected $atPath = '';
    /**
     * 当前路由
     * @var string
     */
    protected $atRoute = '';
    /**
     * 路由数据
     * @var null
     */
    protected $fileRouteData = array();
    protected $noteRouter = array();
    /**
     * 当前路由的所有信息
     * @var array
     */
    protected $atRouteData = [];
    /**
     * 当前路由数据
     * @var array
     */
    protected $routeArr = array();
    /**
     * 控制器发返回类型
     * @var null
     */
    protected $ReturnType = null;


    protected $RouterAdded = [];

    /**
     * 以命名空间为key的控制器路由注解快
     * @var array
     */
    protected $noteBlock = array();

    /**
     * 匹配路由（都是从请求方法先过滤的）
     *      生成路由表时
     *          把常规路由与Restful路由分开
     *              Restful 路由 切割出来：变量名前的路由 字符串$routeStartStr（strpos()）
     *      进行匹配时
     *          1、通过in_array 匹配常规路由
     *          2、常规路由没有匹配到 使用Restful 路由表匹配
     *              1、循环Restful 路由表  使用strpos()函数查询$routeStartStr 在url（$s）中重新的位置（然后为0 代表匹配到 如果是false 或者>0 为没有匹配到）可能匹配多个使用数组$arr存储
     *              2、判断coumt($arr) >是否大于1 大于进行3步骤  == 0 路由不存在  ==1 路由存在进行4步骤
     *              3、循环使用正则表达式进行匹配$arr中路由（如何依然有超过1个匹配成功：记录日志 返回路由冲突）
     *              4、使用方法获取变量并且保存到routeParam(独立$_GET $_POST )
     *
     * 匹配到路由进行请求转发（给控制器）
     *      1、通过路由表进行所以参数的处理（吧路由表相关参数给请求类Request）
     *      2、根据路由表Param 吧 请求类Request实例 注入给 路由对应的 控制器方法
     *
     *
     * 一个请求处理完
     *      
     *
     *
     */

    /**
     *构造方法
     */
    protected function __construct()
    {
        /**
         * 合并ReturnSubjoin
         */
        $this->eturnSubjoin= array_merge($this->ReturnSubjoin,__ROUTE__['ReturnSubjoin']);
        $s = isset($_GET['s'])?$_GET['s']:'/'.__ROUTE__['index'];//默认路由

        $this->atRoute = $s;
        //var_dump($s);
        unset($_GET['s']);

        /**
         * 获取到
         */
        if(__ROUTE__['expanded'] != ''){
            $sstr = strrchr($s,'.');

            if($sstr != __ROUTE__['expanded'] ){
                /**
                 * 如果路由没有expanded
                 */
                 $s = '/'.__ROUTE__['expanded'];
            }
        }
        /**
         * 请求类型
         */
        $_SERVER['REQUEST_METHOD'];
        /**
         * 请求url
         */
        $_SERVER['REQUEST_URI'];
        /**
         * 请求的参数
         * s 为路由
         */
        $_SERVER['QUERY_STRING'];

        /**
         * 判断文件类型
         */

        if( __ROUTE__['type']== 'note'){
            $this->annotation();
            //$this->noteRoute();
        }else if( __ROUTE__['type']== 'file'){
            /**
             * 获取路由
             */
            $this->getRouteConfig();
            $this->fileRoute();
        }
    }
    /**
     * 文件路由(以单独文件定义的路由)
     */
    protected function fileRoute()
    {
        /**
         * 判断路由是否存在
         */
        $this->atRoute = str_replace(__ROUTE__['expanded'],'',$this->atRoute);
        if(isset($this->fileRouteData[$this->atRoute])){
            /**
             * 获取控制器权限数据
             */
            $this->routeArr = &$this->fileRouteData[$this->atRoute];
            /**
             *
             * 判断请求类型
             */
            if(strtoupper($this->fileRouteData[$this->atRoute][2]) != 'ALL'){
                if(strtoupper($this->fileRouteData[$this->atRoute][2]) != $_SERVER['REQUEST_METHOD']) {
                    throw new \Exception('不存在的请求');
                }
            }
            /**
             * 获取类命名空间
             */
            $this->controller = &$this->fileRouteData[$this->atRoute][0];
            $this->atPath = '\\'.__APP__.'\\'.$this->controller;
            /**
             * 获取控制器方法
             */
            $this->method = &$this->fileRouteData[$this->atRoute][1];

        }else{
            throw new \Exception('路由不存在',404);
        }
    }

    /**
     * 注释路由（控制器方法上注释方式设置的路由）
     */
    protected function noteRoute()
    {
        
        /**
         * 获取路由信息
         */
        $Rule = &$this->noteRouter[$_SERVER['REQUEST_METHOD']]['Rule'];//常规
        $Path = &$this->noteRouter[$_SERVER['REQUEST_METHOD']]['Path'];//路径
        //var_dump($Path);
        /**
         * 开始使用常规路由快速匹配
         */
        if(isset($Rule[$this->atRoute])){
        /**
         * 在常规路由中
         */
            //echo '在常规路由中';
            $RouteData = &$Rule[$this->atRoute];
        }else{
            if(empty($Path)){ throw new \Exception('路由不存在'); }
            //echo '不在常规路由中';
            /**
             * 使用快捷匹配路由匹配
             */
            $length = 0;
            foreach ($Path as $k=>$v){
                if(strpos($this->atRoute,$v['PathNote']) === 0){
                    /**
                     * 使用最佳匹配长度结果（匹配长度最长的）
                     */
                    if(strlen($v['PathNote']) > $length){
                        $length = strlen($v['PathNote']);
                        $PathNote[$length][$k] = $Path[$k];
                    }else if(strlen($v['PathNote']) == $length){
                        $PathNote[$length][$k] = $Path[$k];
                    }
                }
            }
            if(!isset($PathNote)){
                //header("Status: 404 Not Found");
                //header("HTTP/1.0 404 Not Found");
                throw new \Exception('路由不存在',404);
            }
            /**
             * 判断匹配到的路由数量
             *
             * 使用正则表达式匹配并且获取参数
             */
            if(count($PathNote[$length])>1){
                /**
                 * 匹配到多个 使用正则表达式
                 */
                foreach ($PathNote as $pnK=>$pnV){
                    preg_match($pnV['MatchStr'],$this->atRoute,$PathData);
                }
            }else{
                /**
                 * 只有一个
                 */
                $RouteData =$PathNote[$length];
                $RouteData = current($RouteData);
                preg_match($RouteData['MatchStr'],$this->atRoute,$PathData);
            }
            /**
             * 去除第一个数据
             */
            array_shift($PathData);
            if(count($RouteData['PathParam']) != count($PathData)){
                /**
                 * 严格匹配参数（如果对应的:name位置没有使用参数 或者为字符串空  认为是路由不存在 或者提示参数不存在）
                 */
                throw new \Exception($RouteData['router'].':路由不存在');
            }
            /**
             * 对参数进行强制过滤（根据路由上的规则：name[int]）
             */
            $i=0;
            foreach ($RouteData['PathParam'] as $k=>$v){
                /**
                 *判断空参数
                 */
                if(empty($PathData[$i])){
                    throw new \Exception($k.'缺少参数');
                }
                if(!settype($PathData[$i],$v)){
                    throw new \Exception($k.'参数约束失败:'.$v);
                }
                $PathArray[$k] = $PathData[$i];
                $i++;
            }
        }
        //var_dump($RouteData);
        $function = $RouteData['function']['name'];
        //$this->module = $function;
        $this->controller = &$RouteData['Namespace'];
        $this->method = &$RouteData['function']['name'];
        $this->atRoute = &$RouteData['router'];//路由
        $this->ReturnType = &$RouteData['ReturnType'];//路由请求类型

        $this->RouterAdded = &$RouteData['RouterAdded'];//附叫配置

        $this->atRouteData = $RouteData;
        $controller = new $RouteData['Namespace'];

        if(empty($RouteData['function']['Param']) && empty($RouteData['ParamObject'])){
            return $controller->$function();
        }else{
            $Request = Request::init();
            $Request->PATH = $PathArray??[];
            return $controller->$function($Request);
        }

    }


    /**
     *  获取 property
     * @param $propertyName
     */
    public function __get($propertyName)
    {
        if(isset($this->$propertyName)){
            return $this->$propertyName;
        }
        return null;
    }

    /**
     * 生成注解路由
     *      判断运行模式
     *      判断是否存在缓存
     *      获取到属性
     */
    public function annotation()
    {
        $fileData =array();
        /**
         * 判断应用模式
         */
        if(__INIT__['pattern'] == 'exploit'){
            /**
             * 开发模式
             * 获取文件路径
             */
            $this->getFilePathData(dirname(getcwd()).DIRECTORY_SEPARATOR.__APP__,$fileData);
            $this->filePathData = $fileData;
            /**
             * 分离获取所有注解块
             */
            $this->noteBlock();
        }else{
            /**
             * 路由缓存
             */
            $CacheData = Cache::get(['public','arr'],'route');
            if($CacheData){
                $this->noteRouter = $CacheData;
            }else{
                /**
                 * 没有缓存
                 * 生生产模式
                 * 获取文件路径
                 */
                $this->getFilePathData(dirname(getcwd()).DIRECTORY_SEPARATOR.__APP__,$fileData);
                $this->filePathData = $fileData;
                /**
                 * 分离获取所有注解块
                 */
                $this->noteBlock();
                Cache::set(['public','arr'],$this->noteRouter,0,'route');
            }
        }
        //var_dump($this->noteRouter);
    }
    /**
     * 获取所有注解块
     */
    protected function noteBlock()
    {
        foreach ($this->filePathData as $k=>$v){
            $this->getNoteBlock($v);
        }
    }
    /**
     * 获取一个控制器中所有的注解块(使用正则表达式)
     */
    protected function getNoteBlock($filePath)
    {
        $data =  file_get_contents($filePath);
        preg_match('/\/[\*]{2}\s(.*?)\*\//s',$data,$result);
//        preg_match('/\/\*\*\r(.*?)\*\//s',$data,$result);
        /**
         * 过滤非控制器类文件
         */
        if(!isset($result[1])){return ;}
        preg_match('/@title[\s]{1,6}(.*?)[\r\n]/s',$result[1],$title);
        preg_match('/User:[\s]{1,6}(.*?)[\r\n]/s',$result[1],$User);
        preg_match('/@basePath[\s]{1,6}(.*?)[\s]{1,4}/s',$result[1],$basePath);
        preg_match('/@authGroup[\s]{1,6}(.*?)[\r\n]/s',$result[1],$authGroup);
        preg_match('/@baseAuth[\s]{1,6}(.*?)[\r\n]/s',$result[1],$baseAuth);


        //var_dump($result[1]);
        //var_dump($baseAuth);

        $basePath[1] = $basePath[1]??'';
        /**
         * 如果有就删除 /
         */
        $basePath[1] = rtrim($basePath[1],'/');
        $title[1] = $title[1]??'未定义';//标题
        $User[1] = $User[1]??'未定义';//创建人
        preg_match('/namespace (.*?);/s',$data,$namespace);
        /**
         * 过滤非控制器类文件
         */
        if(!isset($namespace[1])){return ;}
        /**
         * 获取类名称
         */
        preg_match('/class[\s]{1,6}(.*?)[\s]{1,6}/s',$data,$class);
        /**
         * 定义完整命名空间
         */
        $baseNamespace = $namespace[1].'\\'.$class[1];

        /**
         * 分析提取注解块
         */
        preg_match_all('/\/\*\*[\s](.*?){/s',$data,$noteBlock);//获取方法以及注解块
        /**
         * 判断是否存在注解块
         */
        if(!isset($noteBlock[1])){return ;}
        /**
         * 循环处理控制器中每一个注解块（如果其中没有@router关键字就抛弃注解块）
         */
        foreach ($noteBlock[1] as $k=>$v)
        {
            /**
             * 删除上一个注解块留下来的数据
             */
            unset($PathNote);//简单路径路由用来快速匹配
            unset($routerStr);//路由
            unset($matchStr);//匹配使用的 正则表达式
            //unset($namespace);//路由请求的控制器
            //unset($class);//路由请求的控制器
            unset($ParamObject);
            unset($PathParam);//路径参数（路由参数如user/:id  id就是路由参数）
            unset($routeParamData);//路由参数（url上的或者post等）
            unset($routeReturnData);//返回参数
            unset($function);//控制器方法


            unset($Author);//方法创建人

            unset($Created);//方法创建时间
            unset($routeParam);//切割请求参数



            preg_match('/@router(.*?)[\n\r]/s',$v,$routerData);
            preg_match('/public[\s]+function[\s]+(.*?)[\s]+$/s',$v,$functionData);
            /**
             * 判断注解块是否为控制器方法（可访问的方法：是否设置路由r@outer）
             */
            if(empty($routerData)){continue;}

            if(!empty($routerData) && isset($functionData[1])){
                /********************获取详路由细方法************/

                /**
                 * 控制器方法名称
                 */
                preg_match('/(.*?)[ ]{0,4}\(/s',$functionData[1],$functionName);
                preg_match_all('/[\$][A-Za-z_]+[^, =]/s',$functionData[1],$functionParam);
                if(isset($functionParam[0][0])){
                    $functionParam = $functionParam[0];
                }else{
                    $functionParam = [];
                }
                foreach ($functionParam as $funk=>$funv){
                    $functionParam[$funk] =ltrim($funv,'$');
                }
                $function['name'] = $functionName[1];
                $function['Param'] = $functionParam;
                preg_match_all('/[^ ]{1,10}[A-Z-a-z_.:\/\]\[]+/s',$routerData[1],$routerData);
                if(!isset($routerData[0][1])){continue;}//不规范的路由
                $routerData = $routerData[0];
                $routerData[0] = strtoupper($routerData[0]);

                /**
                 * 判断请求类型
                 */
                //var_dump($routerData);
                if(!in_array($routerData[0],self::RequestType)){throw new \Exception('不规范的请求类型'.$baseNamespace.'->'.$routerData[0]);}

                /**
                 * 判断是否是独立路由
                 */
                if(strpos($routerData[1],'/') === 0){
                    $routerStr = '/'.ltrim($routerData[1],'/');
                }else{

                    $basePath[1] = rtrim($basePath[1],'/').'/';
                    $routerStr = '/'.ltrim($basePath[1].$routerData[1],'/');

                }
                /**
                 * 设置附件路由配置
                 */
                if(isset($routerData[2])){
                    $routerAdded = $routerData;
                    unset($routerAdded[0]);
                    unset($routerAdded[1]);
                    foreach($routerAdded as $routerAddedValue ){
                        //var_dump(explode(':',$routerAddedValue));
                        if(!in_array(explode(':',$routerAddedValue)[0],self::ReturnFormat)){
                            throw new \Exception('不规范的路由附加配置'.$baseNamespace.'->'.$routerStr.'->'.$routerAddedValue);
                        }
                    }
                }
                /**
                 * 把常规路由与Restful路由分开
                 */
                preg_match('/\[/s',$routerData[1],$routerType);

                if(empty($routerType)){
                    $routerType = 'Rule';
                }else{
                    $routerType = 'Path';
                    /**
                     * 获取简单路径路由用来快速匹配
                     */
                    preg_match('/(.*?):/s',$routerStr,$PathNote);
                    $PathNote = $PathNote[1]??'';
                    /**
                     * 准备正则表达式
                     */
                    $routerStrReplace = preg_replace('/\:[A-Za-z\[]+\]/','(.*?)',$routerStr);
                    $matchStr = '/^'.preg_replace('/\//','\/',$routerStrReplace).'[\/]{0,1}$/';
                    /**
                     * 获取：参数
                     */
                    preg_match_all('/:[a-zA-Z_\[\]]+/s',$routerData[1],$PathParamData);
                    unset($PathParam);
                    if(isset($PathParamData[0][0])){
                        $PathParamData = $PathParamData[0];
                        foreach($PathParamData as $Pk=>$Pv){
                            /**
                             * 获取name
                             */
                            preg_match('/:(.*?)\[/s',$Pv,$PvName);
                            if(!isset($PvName[1])){ throw new \Exception('不规范的请求类型参数：'.$routerData[0].'->'.$routerData[1]);}
                            preg_match('/\[(.*?)\]/s',$Pv,$PvRes);
                            if(!isset($PvRes[1])){ throw new \Exception('不规范的请求类型参数：'.$routerData[0].'->'.$routerData[1]);}
                            $PathParam[$PvName[1]] = $PvRes[1];
                            /**
                             * 获取约束
                             */
                        }
                    }

                }

                /**********判断拼接路由********/
                /**
                 * 定义完整的错误提示路径
                 * $baseNamespace 完整的命名空间
                 * $routerData[0] 请求方法
                 * $routerStr 完整的请求路由
                 */
                $baseErrorNamespace = $baseNamespace.'-'.$routerData[0].'-'.$routerStr;
                /**
                 * noteRouter
                 */
                if($routerType == 'Rule'){
                    if(isset($this->noteRouter[$routerData[0]][$routerType][$routerStr] )){throw new \Exception("路由冲突:[".$baseErrorNamespace.']<=>['.$this->noteRouter[$routerData[0]][$routerType][$routerStr]['Namespace'].'-'.$routerType.'-'.$routerStr.']');}
                }else{
                    if(isset($this->noteRouter[$routerData[0]][$routerType][$matchStr] )){throw new \Exception("路由冲突:[".$baseErrorNamespace.']<=>['.$this->noteRouter[$routerData[0]][$routerType][$matchStr]['Namespace'].'-'.$routerType.'-'.$this->noteRouter[$routerData[0]][$routerType][$matchStr]['router'].']');}
                }
                /**
                 * 检测路由
                 *
                 * 1 请求方法
                 * 2 路由路径
                 * 3 路由参数
                 */
                if(count($routerData)>=2){
                    /**
                     * 切割详细信息
                     */
                    preg_match('/@explain[\s]{1,4}(.*?)[*][\s]{1,4}@/s',$v,$routeExplain);//路由解释说明（备注）
                    preg_match('/@title[\s]{1,4}(.*?)@/s',$v,$routeTitle);//获取路由名称
                    preg_match('/@param[\s]{1,4}(.*?)@/s',$v,$routeParam);//请求参数
                    preg_match('/@return[\s]{1,4}(.*?)@/s',$v,$routeReturn);//获取返回参数


                    preg_match('/@Author[\s]{1,4}(.*?)[\s\n]{1,8}[*]{1}[\s\n]{1,8}@{0,1}/s',$v,$Author);//方法创建人
                    preg_match('/@Created[\s]{1,4}(.*?)[\s\n]{1,8}[*]{1}[\s\n]{1,8}@{0,1}/s',$v,$Created);//方法创建时间



                    /*** ***********切割请求参数[url 参数  post等参数 不包括路由参数] return***************/
                    $routeParam = $routeParam[1]??[];

                    if(isset($routeTitle[1])){
                        preg_match('/(.*?)[\n\r]/s',$routeTitle[1],$routeTitle);//获取路由名称
                    }


                    /**
                     * 获取依赖注入的 对象
                     * 目前只支持Request对象（严格区分大小写）
                     */
                    if($routeParam != []){
                        //var_dump($routeParam);

                        preg_match('/(.*?)[ ]{0,10}[\r\n]/s',$routeParam,$routeParamObject);//请求参数
                        $routeParamObject = $routeParamObject[1]??'';
                        if(empty($routeParamObject)){ throw new \Exception('设置了@param但是没有传入对象信息');}
                        /**
                         * 判断对象信息
                         * 默认常规array
                         * $Request [xml] 使用[] 可直接定义xml或者json
                         */
                        preg_match('/\[(.*?)]/s',$routeParamObject,$routeParamObjectType);
                        /**
                         * 判断是否有命名空间
                         */
                        if($routeParamObject != '$Request'){
                            /**
                             * 有命名空间
                             */
                            preg_match('/(.*?)[ ]+[\$][A-Za-z]{1,30}[ ]{0,1}/s',$routeParamObject,$routeParamObjectPath);//请求对象命名空间
                            preg_match('/[\$][A-Za-z]{1,30}/s',$routeParamObject,$routeParamObject);
                            /**
                             * 请求对象
                             */
                            $routeParamObject = $routeParamObject[0]??'';
                            if($routeParamObject != '$Request'){ throw new \Exception('目前只支持Request对象（严格区分大小写）:'.$routeParamObject);}

                        }
                        /**
                         * 开始切割获取请求参数
                         */
                        if(isset($routeParam[1])){
                            /**
                             * 以*            \r  为标准每行切割
                             */
                            preg_match_all('/\*(.*?)[\r\n]/s',$routeParam,$routeParamData);//获取返回参数
                            if(isset($routeParamData[1]) || !empty($routeParamData[1])){
                                /***
                                 * 获取详细参数
                                 */
                                $routeParamData = $this->setReturn($routeParamData[1]);
                                //throw new \Exception('$Request 必须规定参数'.$baseErrorNamespace);
                            }

                        }
                    }

                    /*** ***********切割返回信息 return***************/
                    if(isset($routeReturn[1])){
                        /**
                         * array [objectList]
                         * 获取return array [object] 数据  （返回数据类型array为数组：json html：直接输出html页面）
                         */
                        preg_match('/[\s]{0,5}(.*?)[ ]{0,4}[\r\n]/s',$routeReturn[1],$routeReturnType);//获取返回参数
                        if(isset($routeReturnType[1])){
                            $routeReturnType = $routeReturnType[1];

                            preg_match('/\[(.*?)\]/s',$routeReturnType,$routeReturnType);//获取返回参数
                            if(!isset($routeReturnType[1]))
                            {
                                throw new \Exception('返回类型[必须填写]:'.$routeParamObject);
                            }
                            $routeReturnType = $routeReturnType[1];
                            /**
                             * 判断返回数据类型有html xml 默认json（array）
                             */
                            //if($routeReturnType == 'html'){
                            //
                            //}else if($routeReturnType == 'xml'){
                            //
                            //}else{
                            //
                            //}

                        }
                        /**
                         * 以*      \r  为标准每行切割
                         */
                        preg_match_all('/\*(.*?)[\r\n]/s',$routeReturn[1],$routeReturnData);//获取返回参数

                        if(isset($routeReturnData[1])){ $routeReturnData = $this->setReturn($routeReturnData[1]);}else{$routeReturnData = [];}
                        //var_dump($routeReturnData);
                    }

                    /**
                     * 准备路由数据
                     */
                    $noteRouter = [
                        'router'=>$routerStr,//路由
                        'PathNote'=>$PathNote??'',//简单路径路由用来快速匹配
                        'MatchStr'=>$matchStr??'',//匹配使用的 正则表达式
                        'Namespace'=>$namespace[1].'\\'.$class[1],//路由请求的控制器
                        'Router'=>$routerStr,//路由
                        'RouterAdded'=>$routerAdded??[],//路由附加参数
                        'ParamObject'=>$routeParamObject??'',//请求对象
                        'routeParamObjectPath'=>$routeParamObjectPath[1]??'',//请求对象命名空间路径
                        'routeParamObjectType'=>$routeParamObjectType[1]??'',//请求类型json  array xml
                        'PathParam' =>$PathParam??[],//路径参数（路由参数如user/:id  id就是路由参数）
                        'Param'=>$routeParamData??'',//路由参数（url上的或者post等）
                        'Return'=>$routeReturnData??[],//返回参数
                        'ReturnType' => $routeReturnType??__INIT__['return'],//返回类型
                        'function'=>$function,//控制器方法
                    ];
                    if($routerType == 'Rule'){
                        $this->noteRouter[$routerData[0]][$routerType][$routerStr] = $noteRouter;
                    }else{
                        $this->noteRouter[$routerData[0]][$routerType][$matchStr] = $noteRouter;
                    }


                    /**
                     * 准备文档数据
                     * 【请求方法#路由路径】=【请求参数，请求返回数据，控制器方法】
                     */
                    $routerDocumentData[$routerData[0].'#'.$routerStr] =[
                        'requestType'=>$routerData[0],//请求类型  get  post等等
                        'routerType'=>$routerType,//路由类型
                        'matchStr'=>$matchStr??'',//请求参数
                        'routerStr'=>$routerStr,//路由

                        'Author'=>$Author[1]??'',//方法创建人
                        'Created'=>$Created[1]??'',//方法创建时间

                        'param'=>$routeParam[1]??'',//请求参数
                        'return'=>$routeReturnData??[],//返回参数
                        'function'=>$function,//控制器方法
                        'explain'=>$routeExplain[1]??'',//路由解释说明（备注）
                        'title'=>$routeTitle[1]??'',//获取路由名称
                        'RouterAdded'=>$routerAdded??[],//路由附加参数
                        'ParamObject'=>$routeParamObject??'',//请求对象
                        'routeParamObjectPath'=>$routeParamObjectPath[1]??'',//请求对象命名空间路径
                        'routeParamObjectType'=>$routeParamObjectType[1]??'',//请求类型json  array xml
                        'PathParam' =>$PathParam??[],//路径参数（路由参数如user/:id  id就是路由参数）
                        'Param'=>$routeParamData??'',//路由参数（url上的或者post等）
                        'Return'=>$routeReturnData??[],//返回参数
                        'ReturnType' => $routeReturnType??__INIT__['return'],//返回类型
                        'function'=>$function,//控制器方法
                    ];
                }
            }else{
                continue;
            }
        }

        /**
         * 拼接数据（文档数据）
         */
        $this->noteBlock[$namespace[1].'\\'.$class[1]] = [
            'title'=>$title[1],
            'class'=>$class[1],
            'User'=>$User[1],
            'basePath'=>$basePath[1]??'',
            'authGroup'=>$authGroup[1]??[],
            'baseAuth'=>$baseAuth[1]??[],
            'route'=>$routerDocumentData??[],
        ];
    }



    /**
     * 切割组织返回参数
     */
    protected function setReturn($data)
    {
        if(!isset($data[0])){
            return null;
        }
        /**
         * 获取第一个并且以第一个为参考
         */
        preg_match('/^[ ]+/s',$data[0],$blank);//获取路由名称
        $baseBlank = $blank[0]??'';
        return $this->setReturnRecursive(strlen($baseBlank),0,$data);
    }

    /**
     * 获取同级别参数详情的递归函数
     * @param $length
     * @param $i
     * @param $data
     * @throws \Exception
     */
    protected function setReturnRecursive($length,$i,$data)
    {
        /**
         * 第一个  key  + 空格长度
         *      进入递归
         *      1如果是下级别【上级别key】=【下级别key】
         */
        /**
         * 总循环数
         */
        $count = count($data);
        for ($x =$i;$x<=$count-1;$x++ ){
            preg_match('/^[ ]{'.$length.'}[A-Za-z_]/s',$data[$x],$blankJudge);//获取路由名称

            if(empty($blankJudge)){

                //echo '进入下一层的x'.$x.'$length'.$length.'<br>';
                /**
                 * 判断 是上一层或者下一层  下下层
                 */
                preg_match('/[ ]+/s',$data[$x],$blankLength);//获取空格长度
                $baseBlank = $blankLength[0]??'';
                $baseBlank = strlen($baseBlank);


                if($baseBlank<$length){
                    return $arr;
                }
                /**
                 * 当前空格长度 $baseBlank   参考空格长度 $length   $tagBaseBlank = 上一次循环的$baseBlank长度
                 */
                /**
                 * 判断第一次下层 进入
                 */
                if(isset($tagBaseBlank)){
                    /**
                     * 非第一次下层 进入
                     *
                     * 上一次循环的$baseBlank长度  <  当前空格长度
                     */
                    if($tagBaseBlank < $baseBlank){
                        //下下层
                        //$arr[$field]['substratum'] = $this->setReturnRecursive($baseBlank,$x,$data);

                    }else if($tagBaseBlank == $baseBlank){
                        /**
                         * 同级别  判断下一个 $x 是否是下级别
                         */
                    }else if($tagBaseBlank > $baseBlank){
                        /**
                         * 上一层
                         */
                        unset($tagBaseBlank);
                    }
                }else{
                    /**
                     * 第一次下层 进入
                     */
                    $tagBaseBlank = $baseBlank;
                    $arr[$field]['substratum'] = $this->setReturnRecursive($baseBlank,$x,$data);
                }
            }else{
                unset($tagBaseBlank);
                /**
                 * 同一层
                 */
                preg_match('/[ ]+(.*?)[ ]{1,5}[\[]{1}/s',$data[$x],$field);//字段
                /**
                 * 这里可以考虑加入敏感关键字过滤
                 */
                if(!isset($field[1])){throw new \Exception('@return 字段名称不正确:'.$data[$x]);}
                $field = $field[1];
                preg_match('/\[(.*?)\]{1}/s',$data[$x],$fieldRestrain);//约束
                //var_dump($fieldRestrain);
                if(!isset($fieldRestrain[1])){throw new \Exception('@return 字段约束不正确:'.$data[$x]);}
                preg_match('/[\]][ ]+(.*?)$/s',$data[$x],$fieldExplain);//explain说明
                $arr[$field] = [
                    'fieldRestrain'=>explode(' ',$fieldRestrain[1]),
                    'fieldExplain'=>$fieldExplain[1]??''
                ];
            }

        }
        return $arr;
    }


    /**
     * 获取所有文件目录地址
     */
    public function getFilePathData($dir,&$fileData)
    {
        /**
         * 打开应用目录
         * 获取所有文件路径
         */
        if (is_dir($dir)){
            if ($dh = opendir($dir)){
                while (($file = readdir($dh)) !== false){
                    if($file != '.' && $file != '..'){
                        /**
                         * 判断是否是目录
                         */
                        if(is_dir($dir.DIRECTORY_SEPARATOR.$file)){
                            $this->getFilePathData($dir.DIRECTORY_SEPARATOR.$file,$fileData);
//                            echo "目录:" . $file . "<br>";
                        }else{
                            /**
                             * 判断是否是php文件
                             */
//                            var_dump(strrchr($file,'.'));
                            if(strrchr($file,'.php') == '.php'){
                                $fileData[] = $dir.DIRECTORY_SEPARATOR.$file;
                            }
//                            echo "文件:" . $file . "<br>";
                        }
                    }
                }
                closedir($dh);
            }
        }
    }
    /**
     * 处理一个注解块
     */
    /**
     * 启动请求转移（实例化控制器）
     */
    protected function begin()
    {

        /**
         * 判断文件类型
         */
        if( __ROUTE__['type']== 'note'){
            return $this->noteRoute();



        }else if( __ROUTE__['type']== 'file'){
            /**
             * 实例化控制器
             */
            $atPath = &$this->atPath;
            $new = new $this->atPath;
            $method = &$this->method;
            /**
             * 控制器方法
             */
            return $new->$method();
        }

    }

    /**
     * 获取路由配置
     */
    protected function getRouteConfig()
    {
        /**
         * 打开route目录
         */
        $file_dir = scandir( __ROUTE__['file_dir']);
        foreach ($file_dir as $k =>$v){

            if(!is_dir(__ROUTE__['file_dir'].__DS__.$v)){
                /**
                 * 合并【按文件字母升序排列】
                 */
                $this->fileRouteData = array_merge($this->fileRouteData,require(__ROUTE__['file_dir'].__DS__.$v));
            }else{
                //echo '目录'.$v;
            }
        }

    }

    /**
     * 初始化
     * @param bool $status
     * @return null|static
     */
    public static  function init($status = false)
    {
        /**
         * 判断是否已经有这个对象
         *  var_dump($this->atRoute);
         */
        if(self::$object != null){
            return self::$object;
        }else{
            self::$object = new static();
        }
        //var_dump(static::$object);
        if($status == true){
            return self::$object ->begin();
        }

    }

}