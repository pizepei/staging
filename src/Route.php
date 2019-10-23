<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/8/2
 * Time: 16:06
 * @title 路由
 */
declare(strict_types=1);

namespace pizepei\staging;

class Route
{
    /**
     * 当前对象
     * @var null
     */
    private static $object = null;

    protected $Config = null;
    /**
     * 支持的请求类型
     */
    const RequestType =['All','GET','POST','PUT','PATCH','DELETE','COPY','HEAD','OPTIONS','LINK','UNLINK','PURGE','LOCK','UNLOCK','PROPFIND','VIEW','CLI'];
    /**
     * 请求path参数数据类型
     * 用来限制路由生成
     */
    const RequestPathParamDataType = ['int','string','float'];
    /**
     * get post 等请求参数数据类型
     */
    const RequestParamDataType = ['int','string','bool','float','array','null'];

    /**
     * 控制器return 返回的数据类型
     */
    const ReturnDataType = ['html','xml','json','string'];

    /**
     * 返回数据类型
     */
    const ReturnFormat = ['list','objectList','object','raw'];
    /**
     * 路由附加配置
     * debug 调试模式    auth  权限
     */
    const ReturnAddition =  ['debug','auth'];
    /**
     * 路由资源类型  api 为默认传统类型    microservice为微服务类型（在进入控制器到控制器的权限判断时继续请求数据的单独处理 在文档中进行特殊显示）
     */
    const resourceType = ['api','microservice'];


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
        'uuid'=>['uuid','/^[0-9A-Za-z]{8}[-][0-9A-Za-z]{4}[-][0-9A-Za-z]{4}[-][0-9A-Za-z]{4}[-][0-9A-Za-z]{12}$/'],//0152C794-4674-3E16-9A3E-62005CACC127
        'email'=>['email','/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/','']
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
    protected $noteRouter = array();
    /**
     * 当前路由的所有信息
     * @var array
     */
    protected $atRouteData = [];
    /**
     * 控制器发返回类型
     * @var null|string
     */
    protected $ReturnType = null;
    /**
     * 当前路由的权限控制器
     * @var array
     */
    protected $baseAuth = [];
    /**
     * @var array
     */
    protected $RouterAdded = [];

    /**
     * 以命名空间为key的控制器路由注解快
     * @var array
     */
    protected $noteBlock = array();
    /**
     * 当前路由的tag
     * @var string
     */
    protected $authTag = '';

    /**
     * 当前路由return参数
     * @var array
     */
    protected $Return = [];
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
     * 一个请求处理完
     */

    /**
     * @var App|null
     */
    protected $app = null;
    /**
     *构造方法
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        # 合并ReturnSubjoin
        $this->ReturnSubjoin= array_merge($this->ReturnSubjoin,$this->app->__ROUTE__['ReturnSubjoin']);
        # 判断路由没事 获取当前路由 atRoute
        if ($_SERVER['PATH_INFO'] == ''){
            $atRoute = isset($_GET['s'])?$_GET['s']:'/'.$this->app->__ROUTE__['index'];//默认路由
        }else{
            $atRoute = $_SERVER['PATH_INFO'];
        }
        if ($this->app->__ROUTE__['postfix'] !==[]){
            foreach ($this->app->__ROUTE__['postfix'] as $value){
                $atRoute = str_replace($value,'',$atRoute);
            }
        }
        $this->atRoute = $atRoute;
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

        # 生成 使用 注解路由
        $this->annotation();
        //$this->noteRoute();
    }
    public function __isset($name)
    {
        if (isset($this->$name)){
            return true;
        }
        return null;
        // TODO: Implement __isset() method.
    }
    /**
     * @Author pizepei
     * @return mixed
     * @throws \Exception
     * @title  注释路由（控制器方法上注释方式设置的路由）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function noteRoute()
    {
        switch ($_SERVER['REQUEST_METHOD']){
            case 'GET':
                $RouteData = isset(\RouteInfo::GET['Rule'][$this->atRoute])?\RouteInfo::GET['Rule'][$this->atRoute]:\RouteInfo::GET['Path']??[];
                break;
            case 'POST':
                $Rule = \RouteInfo::POST;
                $RouteData = isset(\RouteInfo::POST['Rule'][$this->atRoute])?\RouteInfo::POST['Rule'][$this->atRoute]:\RouteInfo::POST['Path']??[];

                break;
            case 'PUT':
                $Rule = \RouteInfo::PUT;
                $RouteData = isset(\RouteInfo::PUT['Rule'][$this->atRoute])?\RouteInfo::PUT['Rule'][$this->atRoute]:\RouteInfo::PUT['Path']??[];
                break;
            case 'PATCH';
                $Rule = \RouteInfo::PATCH;
                $RouteData = isset(\RouteInfo::PATCH['Rule'][$this->atRoute])?\RouteInfo::PATCH['Rule'][$this->atRoute]:\RouteInfo::PATCH['Path']??[];
                break;
            case 'DELETE';
                $Rule = \RouteInfo::DELETE;
                $RouteData = isset(\RouteInfo::DELETE['Rule'][$this->atRoute])?\RouteInfo::DELETE['Rule'][$this->atRoute]:\RouteInfo::DELETE['Path']??[];

                break;
            case 'COPY';
                $Rule = \RouteInfo::COPY;
                $RouteData = isset(\RouteInfo::COPY['Rule'][$this->atRoute])?\RouteInfo::COPY['Rule'][$this->atRoute]:\RouteInfo::COPY['Path']??[];

                break;
            case 'HEAD';
                $Rule = \RouteInfo::HEAD;
                $RouteData = isset(\RouteInfo::HEAD['Rule'][$this->atRoute])?\RouteInfo::HEAD['Rule'][$this->atRoute]:\RouteInfo::HEAD['Path']??[];

                break;
            case 'OPTIONS';
                $Rule = \RouteInfo::OPTIONS;
                $RouteData = isset(\RouteInfo::OPTIONS['Rule'][$this->atRoute])?\RouteInfo::OPTIONS['Rule'][$this->atRoute]:\RouteInfo::OPTIONS['Path']??[];

                break;
            case 'LINK';
                $Rule = \RouteInfo::LINK;
                $RouteData = isset(\RouteInfo::LINK['Rule'][$this->atRoute])?\RouteInfo::LINK['Rule'][$this->atRoute]:\RouteInfo::LINK['Path']??[];

                break;
            case 'UNLINK';
                $Rule = \RouteInfo::UNLINK;
                $RouteData = isset(\RouteInfo::UNLINK['Rule'][$this->atRoute])?\RouteInfo::UNLINK['Rule'][$this->atRoute]:\RouteInfo::UNLINK['Path']??[];

                break;
            case 'PURGE';
                $Rule = \RouteInfo::PURGE;
                $RouteData = isset(\RouteInfo::PURGE['Rule'][$this->atRoute])?\RouteInfo::PURGE['Rule'][$this->atRoute]:\RouteInfo::PURGE['Path']??[];

                break;
            case 'LOCK';
                $Rule = \RouteInfo::LOCK;
                $RouteData = isset(\RouteInfo::LOCK['Rule'][$this->atRoute])?\RouteInfo::LOCK['Rule'][$this->atRoute]:\RouteInfo::LOCK['Path']??[];

                break;
            case 'UNLOCK';
                $Rule = \RouteInfo::UNLOCK;
                $RouteData = isset(\RouteInfo::UNLOCK['Rule'][$this->atRoute])?\RouteInfo::UNLOCK['Rule'][$this->atRoute]:\RouteInfo::UNLOCK['Path']??[];

                break;
            case 'PROPFIND';
                $Rule = \RouteInfo::PROPFIND;
                $RouteData = isset(\RouteInfo::PROPFIND['Rule'][$this->atRoute])?\RouteInfo::PROPFIND['Rule'][$this->atRoute]:\RouteInfo::PROPFIND['Path']??[];

                break;
            case 'VIEW';
                $Rule = \RouteInfo::VIEW;
                $RouteData = isset(\RouteInfo::VIEW['Rule'][$this->atRoute])?\RouteInfo::VIEW['Rule'][$this->atRoute]:\RouteInfo::VIEW['Path']??[];

                break;
            case 'CLI';
                $Rule = \RouteInfo::CLI;
                $RouteData = isset(\RouteInfo::CLI['Rule'][$this->atRoute])?\RouteInfo::CLI['Rule'][$this->atRoute]:\RouteInfo::CLI['Path']??[];

                break;
            case 'All';
                $Rule = \RouteInfo::All;
                $RouteData = isset(\RouteInfo::All['Rule'][$this->atRoute])?\RouteInfo::All['Rule'][$this->atRoute]:\RouteInfo::All['Path']??[];

                break;
            default:
                $RouteData = [];
        }
        if (isset($RouteData['router']) && is_string($RouteData['router'])){

            # 匹配到常规路由
        }else{
            # 使用路径路由匹配模式
            if(empty($RouteData)){ throw new \Exception('路由不存在'); }
            # 不在常规路由中 同时也没有模糊匹配（path中确定的部分简单匹配）
            # 使用快捷匹配路由匹配
            $length = 0;
            $PathNote = [];
//            foreach ($this->yieldForeach($RouteData) as $k=>$v){
//            }
            foreach ($RouteData as $k=>$v){
                # 通过长度进行匹配
                if(strpos($this->atRoute,$v['PathNote']) === 0){
                    # 使用最佳匹配长度结果（匹配长度最长的）
                    if(strlen($v['PathNote']) > $length){
                        $length = strlen($v['PathNote']); # 重新定义长度
                        $PathNote[$length][$k] = $v;
                    }else if(strlen($v['PathNote']) == $length){
                        $PathNote[$length][$k] = $v;
                    }
                }
            }
            if(empty($PathNote)){
                //header("Status: 404 Not Found");
                //header("HTTP/1.0 404 Not Found");
                throw new \Exception('路由不存在',404);
            }

            # 判断匹配到的路由数量、使用正则表达式匹配并且获取参数、$length为strlen()获取的匹配长度，使用匹配最才$length做路由匹配
            if(count($PathNote[$length])>1){
                /**
                 * 使用模糊匹配后仍然有多个路由
                 *      strlen()获取的匹配长度一样的情况下导致有多个路由
                 * 匹配到多个 使用正则表达式
                 */
                $PathNoteFor = $PathNote[$length];
                foreach ($PathNoteFor as $pnK=>$pnV){
                    preg_match($pnV['MatchStr'],$this->atRoute,$PathDataFor);
                    /**
                     * 判断正则表达式获取的参数数量是否和配置一致
                     * 路径参数在正则表达式中统一用(.*?)表示因此
                     *      如果路由前缀是相同的如/index/:id[uuid]   和 /index/:name[string] 会同时路由冲突
                     *      /index/:id[uuid]/:name[string] 和 /index/:name[string] 不会冲突并且进入到这里
                     *      因此这里只需要判断使用正则表达式匹配到的参数数量-1后 和$pnV['PathParam']一直就可以判断是正确路由了
                     */
                    if(count($pnV['PathParam']) == (count($PathDataFor)-1))
                    {
                        $PathData = $PathDataFor;
                        $RouteData = $pnV;
                    }
                }
            }else{
                # 只有一个
                $RouteData = current($PathNote[$length]);

                preg_match($RouteData['MatchStr'],$this->atRoute,$PathData);# 使用正则表达式匹配
            }
            array_shift($PathData);
            if( !isset($RouteData['PathParam']) || (count($RouteData['PathParam']) != count($PathData))){
                # 严格匹配参数（如果对应的:name位置没有使用参数 或者为字符串空  认为是路由不存在 或者提示参数不存在）
                throw new \Exception(($RouteData['router']??'').':路由不存在,请检查路由参数是否使用了特殊字符串（-_@）');
            }
            # 对参数进行强制过滤（根据路由上的规则：name[int]）
            $i=0;
            foreach ($RouteData['PathParam'] as $k=>$v){
                # 判断排除 空参数
                if(empty($PathData[$i]) && $PathData[$i] !=='0'){
                    throw new \Exception($k.'缺少参数');
                }
                # 参数约束  array_key_exists($v,$this->ReturnSubjoin)
                if(isset($this->ReturnSubjoin[$v])){
                    # 开始 匹配路径参数
                    preg_match($this->ReturnSubjoin[$v][1],$PathData[$i],$result);
                    if(!isset($result[0]) && empty($result[0])){throw new \Exception($k.'非法的:'.$this->ReturnSubjoin[$v][0]);}
                }else if(in_array($v,self::RequestPathParamDataType)){
                    if(!settype($PathData[$i],$v)){throw new \Exception($k.'参数约束失败:'.$v);}

                }else{
                    throw new \Exception($k.'非法的参数约束:'.$v);
                }
                $PathArray[$k] = $PathData[$i];
                ++$i;
            }
        }
        /**
         * 判断是否有对应参数（确定是先检查参数准确性、还是在控制器中获取参数时检查（可能出现参数不正确但是不提示错误））
         */
        $function = $RouteData['function']['name'];
        $this->controller = &$RouteData['Namespace'];
        $this->method = &$RouteData['function']['name'];
        $this->atRoute = &$RouteData['router'];             #路由
        $this->ReturnType = &$RouteData['ReturnType'];      #路由请求类型

        $this->RouterAdded = &$RouteData['RouterAdded'];    #附加配置
        $this->atRouteData = &$RouteData;                   #路由
        $this->baseAuth = &$RouteData['baseAuth']??[];      #权限控制器
        $this->authTag = &$RouteData['tag']??'';            #路由标识
        $this->Return = &$RouteData['Return']??[];
        if(!empty($RouteData['routeBaseAuth'][0])){
            $this->baseAuth = &$RouteData['routeBaseAuth']??[];//权限控制器
        }

        # 避免在控制器中有输出导致Cannot modify header information - headers already sent by错误=>因此在控制器实例化前设置头部
        $this->app->Request()->setHeader($this->app->Request()::Header[$this->ReturnType]);
        $this->app->Request()->PATH = $PathArray??[]; # 设置匹配到的路径参数
        # 实例化控制器
        $controller = new $RouteData['Namespace']($this->app);
        if(empty($RouteData['function']['Param']) && empty($RouteData['ParamObject'])){
            return $controller->$function();
        }else{
            return $controller->$function($this->app->Request());
        }
    }
    public function yieldForeach($data)
    {
        foreach ($data as $key=>$value){
            if(strpos($this->atRoute,$value['PathNote']) === 0){
                # 使用最佳匹配长度结果（匹配长度最长的）
                if(strlen($v['PathNote']) > $length){
                    $length = strlen($v['PathNote']); # 重新定义长度
                    (yield $key=>$value);
                }else if(strlen($v['PathNote']) == $length){
                    (yield $key=>$value);
                }
            }
        }
        if(count($PathNote[$length])>1) {
            (yield 0=>null);
        }
    }
    /**
     * 禁止外部获取的类属性
     */
    const forbidPram = [
        'noteRouter',//所有的路由信息
    ];

    /**
     *  获取 property
     * @param $propertyName
     */
    public function __get($propertyName)
    {
        # 设置禁止获取的信息

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
        # 判断应用模式  如果是开发模式  或者其中一个配置文件不存在 都会重新生成权限与路由配置文件
        if($this->app->__EXPLOIT__ == 1 || !file_exists($this->app->__DEPLOY_CONFIG_PATH__.'RouteInfo.php') || !file_exists($this->app->__DEPLOY_CONFIG_PATH__.'PermissionsInfo.php')){
            # 获取应用目录下所有文件路径
            $this->getFilePathData(dirname(getcwd()).DIRECTORY_SEPARATOR.$this->app->__APP__,$fileData);
            $this->filePathData = $fileData;
            # 分离获取所有注解块
            $this->noteBlock();
            # 设置Route   Permissions  类
            $this->app->InitializeConfig()->set_config('RouteInfo',$this->noteRouter,$this->app->__DEPLOY_CONFIG_PATH__);
            $this->app->InitializeConfig()->set_config('PermissionsInfo',$this->Permissions,$this->app->__DEPLOY_CONFIG_PATH__);
        }
        # 包含配置
        require ($this->app->__DEPLOY_CONFIG_PATH__.'RouteInfo.php');
        require ($this->app->__DEPLOY_CONFIG_PATH__.'PermissionsInfo.php');

    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/19 14:38
     * @title  获取注解块
     * @throws \Exception
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
        /**
         * 过滤非控制器类文件
         */
        if(!isset($result[1])){return ;}
        preg_match('/@title[\s]{1,6}(.*?)[\r\n]/s',$result[1],$title);
        preg_match('/User:[\s]{1,6}(.*?)[\r\n]/s',$result[1],$User);
        preg_match('/@basePath[\s]{1,6}(.*?)[\s]{1,4}/s',$result[1],$basePath);
        preg_match('/@baseControl[\s]{1,6}(.*?)[\s]{1,4}/s',$result[1],$baseControl);
        preg_match('/@authGroup[\s]{1,6}(.*?)[\r\n]/s',$result[1],$authGroup);
        preg_match('/@baseAuth[\s]{1,6}(.*?)[\r\n]/s',$result[1],$baseAuth);


        # 处理权限
        if(isset($baseAuth[1]))
        {
            $baseAuth = explode(':',$baseAuth[1]);
        }else{
            $baseAuth = [];
        }
        $basePath[1] = $basePath[1]??'';

        # 如果有就删除 /
        $basePath[1] = rtrim($basePath[1],'/');
        $title[1] = $title[1]??'未定义';//标题
        $User[1] = $User[1]??'未定义';//创建人
        preg_match('/namespace (.*?);/s',$data,$namespace);
        # 过滤非控制器类文件
        if(!isset($namespace[1])){return ;}
        # 获取类名称
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
//        。
//        throw new \Exception('CS');
        if (isset($baseControl[1])){
            $baseControl = $baseControl[1];
            if (DIRECTORY_SEPARATOR ==='\\'){
                $data =  file_get_contents(str_replace('/',DIRECTORY_SEPARATOR,$this->app->DOCUMENT_ROOT.'vendor'.DIRECTORY_SEPARATOR.trim(trim($baseControl,'/'),'\\').'.php'));
            }else{
                $data =  file_get_contents(str_replace('\\',DIRECTORY_SEPARATOR,$this->app->DOCUMENT_ROOT.'vendor'.DIRECTORY_SEPARATOR.trim(trim($baseControl,'/'),'\\').'.php'));
            }
            preg_match_all('/\/\*\*[\s](.*?){/s',$data,$basicsNoteBlock);//获取方法以及注解块
            if (isset($basicsNoteBlock[1])){
                $noteBlock[1] = array_merge($basicsNoteBlock[1],$noteBlock[1]);
            }
        }

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
                preg_match_all('/[^ ]+[A-Z-a-z_0-9.:\/\]\[]+/s',$routerData[1],$routerData);

                if(!isset($routerData[0][1])){continue;}# 跳过不规范的路由

                $routerData = $routerData[0];
                $routerData[0] = strtoupper($routerData[0]);
                # 判断请求类型
                if(!in_array($routerData[0],self::RequestType)){throw new \Exception('不规范的请求类型'.$baseNamespace.'->'.$routerData[0]);}
                # 判断是否是独立路由
                if(strpos($routerData[1],'/') === 0){
                    # 独立路由
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

                        $routerAddedExp = explode(':',$routerAddedValue);

                        if(!in_array($routerAddedExp[0],self::ReturnAddition)){
                            throw new \Exception('不规范的路由附加配置'.$baseNamespace.'->'.$routerStr.'->'.$routerAddedValue);
                        }
                        $routerAddedExplode[$routerAddedExp[0]] = $routerAddedExp[1];
                    }
                    $routerAdded = $routerAddedExplode;
                }
                /**
                 * 把常规路由与Restful路由分开
                 */
                preg_match('/\[/s',$routerData[1],$routerType);

                if(empty($routerType)){
                    $routerType = 'Rule';
                }else{
                    $routerType = 'Path';
                    # 获取简单路径路由用来快速匹配
                    preg_match('/(.*?):/s',$routerStr,$PathNote);
                    if (!isset($PathNote[1])){
                        throw new \Exception('路由是否忘记写：了？->'.$routerStr);
                    }
                    $PathNote = $PathNote[1];

                    # 准备正则表达式
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
                    preg_match('/@explain[\s]+(.*?)[*][\s]+@/s',$v,$routeExplain);//路由解释说明（备注）
                    preg_match('/@title[\s]+(.*?)@/s',$v,$routeTitle);//获取路由名称
                    preg_match('/@param[\s]+(.*?)@/s',$v,$routeParam);//请求参数
                    preg_match('/@return[\s]+(.*?)@/s',$v,$routeReturn);//获取返回参数


                    preg_match('/@Author[\s]+(.*?)[\s\n]{1,8}[*]{1}[\s\n]{1,8}@{0,1}/s',$v,$Author);//方法创建人
                    preg_match('/@Created[\s]+(.*?)[\s\n]{1,8}[*]{1}[\s\n]{1,8}@{0,1}/s',$v,$Created);//方法创建时间

                    preg_match('/@resourceType[\s]+(.*?)[\r\n]/s',$v,$resourceType);# 路由注意类型 如果默认是API资源  microservice为微服务应用API资源
                    if (isset($resourceType[1]) && !in_array($resourceType[1],self::resourceType)){ throw new \Exception('resourceType 类型错误['.$baseErrorNamespace.']');}

                    preg_match('/@authGroup[\s]+(.*?)[\r\n]/s',$v,$routeAuthGroup);//路由的权限分组
                    preg_match('/@authExtend[\s]+(.*?)[\r\n]/s',$v,$routeAuthExtend);//权限扩展信息
                    preg_match('/@baseAuth[\s]+(.*?)[\r\n]/s',$v,$routeBaseAuth);//路由上定义的权限控制器

                    $tag = md5($namespace[1].'\\'.$class[1].$function['name']);//路由标识（控制器方法级别）

                    //$tag = md5($namespace[1].'\\'.$class[1].$function['name'].$routerStr.$matchStr);//路由标识（路由级别）
                    /**
                     * 切割处理
                     */
                    $this->authDispose($routeAuthGroup,$routeAuthExtend,$tag);
                    /**
                     * 分组判断
                     */
                    $detectionAuthGroup = $this->detectionAuthGroup($routeAuthGroup);
                    if(!$detectionAuthGroup[0]){
                        throw new \Exception($detectionAuthGroup[1].' '.'  ['.$baseErrorNamespace.']');
                    }
                    if(!$this->detectionAuthExtend($routeAuthExtend)){
                        throw new \Exception('AuthExtend illegality  ['.$baseErrorNamespace.']');
                    }
                    # 处理权限控制器
                    if(isset($routeBaseAuth[1]))
                    {
                        $routeBaseAuth = explode(':',$routeBaseAuth[1]);
                    }else{
                        $routeBaseAuth = [];
                    }
                    $routeBaseAuth[1] = $routeBaseAuth[1]??'';
                    /*** ***********切割请求参数[url 参数  post等参数 不包括路由参数] return***************/
                    $routeParam = $routeParam[1]??[];

                    if(isset($routeTitle[1])){
                        preg_match('/(.*?)[\n\r]/s',$routeTitle[1],$routeTitle);//获取路由名称
                    }
                    /**
                     * 获取依赖注入的 对象 容器下主要是为了适配IDE
                     * 目前只支持Request对象（严格区分大小写）
                     */
                    if($routeParam != []){
                        preg_match('/(.*?)[ ]{0,10}[\r\n]/s',$routeParam,$routeParamObject);//请求参数
                        $routeParamObject = $routeParamObject[1]??'';
                        if(empty($routeParamObject)){ throw new \Exception('设置了@param但是没有传入对象信息['.$baseErrorNamespace.']');}
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
                            if(isset($routeParamData[1]) && !empty($routeParamData[1]) && $routeParamData[1][0] !==''){
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
                            //var_dump($routeReturnType);
                            $routeReturnExplain = $routeReturnType;
                            //preg_match('/\[json\](.*?)[ ]{0,1}/s',$routeReturnType,$routeReturnExplain);//获取返回参数的主题说明备注
                            //var_dump($routeReturnExplain);

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

                        if(isset($routeReturnData[1]) && isset($routeReturnData[1][0]) && $routeReturnData[1][0] !==''){ $routeReturnData = $this->setReturn($routeReturnData[1]);}else{$routeReturnData = [];}

                    }

                    /**
                     * 准备路由数据
                     */
                    $noteRouter = [
                        'resourceType'=>$resourceType[1]??'api',
                        'router'=>$routerStr,//路由
                        'tag'=>$tag,//tag路由标识
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
                        'ReturnType' => $routeReturnType??$this->app->__INIT__['return'],//返回类型
                        'function'=>$function,//控制器方法
                        'routeAuthGroup'=>$routeAuthGroup,//路由的权限分组
                        'routeAuthExtend'=>$routeAuthExtend,//权限扩展信息
                        'baseAuth'=>$baseAuth??[],//权限控制器
                        'routeBaseAuth'=>$routeBaseAuth??[],//路由上定义的权限控制器

                    ];
                    if($routerType == 'Rule'){
                        $this->noteRouter[$routerData[0]][$routerType][$routerStr] = $noteRouter;# 传统路由
                    }else{
                        $this->noteRouter[$routerData[0]][$routerType][$matchStr] = $noteRouter;#路径路由
                    }


                    /**
                     * 准备文档数据
                     * 【请求方法#路由路径】=【请求参数，请求返回数据，控制器方法】
                     */
                    $routerDocumentData[$routerData[0].'#'.$routerStr] =[
                        'resourceType'=>$resourceType[1]??'api',
                        'requestType'=>$routerData[0],//请求类型  get  post等等
                        'routerType'=>$routerType,//路由类型
                        'matchStr'=>$matchStr??'',//请求参数
                        'routerStr'=>$routerStr,//路由
                        'routeReturnExplain'=>$routeReturnExplain??[],//返回说明
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
                        'ReturnType' => $routeReturnType??$this->app->__INIT__['return'],//返回类型
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
            'baseAuth'=>($baseAuth[0]??'').':'.($baseAuth[1]??''),
            'route'=>$routerDocumentData??[],
        ];
    }

    /**
     * @Author pizepei
     * @Created 2019/4/21 16:09
     *
     * @title  权限相关处理
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    protected function authDispose(&$routeAuthGroup,&$routeAuthExtend,$tag)
    {
        //* @authGroup [admin.del:user.del]删除账号操作
        //* @authExtend UserExtend:list 删除账号操作
        /**
         * 切割权限
         */
        if(isset($routeAuthGroup[1])){
            $routeAuthGroup = $routeAuthGroup[1];
            $this->processingBatch($routeAuthGroup,'.',':');
            /**
             * 检测权限分组规范
             */
            //authority
        }
        if(isset($routeAuthExtend[1])){
            $routeAuthExtend= $routeAuthExtend[1];
            $this->processingBatch($routeAuthExtend,'.',':');
        }
        /**
         * 合并
         *      [模块资源]=>[del=>[路由=>''，唯一路由标识md5（命名空间+类名称+方法名称）]]
         *
         * @authGroup admin.del:删除账号操作,user.del:删除账号操作,user.add:添加账号操作
         * @authExtend UserExtend.list:删除账号操作
         */
        foreach($routeAuthGroup as $value)
        {
             //模块资源  del  $tag
            $this->Permissions[$value[0]][$value[1]][$value[2][0]][] = [
                'tag'=>$tag,
                'explain'=>$value[2][1],
                'extend'=>$routeAuthExtend
            ];
        }
    }

    /**
     * 权限模型数据
     * @var array
     */
    protected $Permissions  = [];
    /**
     * @Author pizepei
     * @Created 2019/4/21 17:19
     * @param $routeAuthGroup
     * @return array
     * @title  检测权限分组
     */
    protected function detectionAuthGroup($routeAuthGroup)
    {
        if(empty($routeAuthGroup)){return [true];}

        $Resource = 'authority\\'.$this->app->__APP__.'\\Resource';

        $reflect = new \ReflectionClass($Resource);
        $ConstData = $reflect->getConstants();
        foreach($routeAuthGroup as $value)
        {
            //if(count($value) !=3) {return [false,'formal error ：count unequal 3 ',''];}
            /**
             * 一级
             */
            if(!isset($ConstData['mainResource'][$value[0]])){
                return [false,'main illegality',''];
            }
            /**
             * 二级
             */
            if(!isset($ConstData[$value[0]][$value[1]])){
                return [false,'second illegality',''];
            }
            //list

            /**
             * 判断三级
             */
            if(!isset($ConstData[$value[0]][$value[1]]['list'][$value[2][0]])){
                return [false,'lesser inexistence',''];
            }

            return [true];
        }

    }

    /**
     * @Author pizepei
     * @Created 2019/4/21 17:19
     * @param $routeAuthExtend
     * @title  权限格式判断
     * @return array
     */
    protected function detectionAuthExtend($routeAuthExtend)
    {
        if(empty($routeAuthExtend)){
            return true;
        }
        foreach($routeAuthExtend as $value)
        {
            if(count($value) !=2){
                return false;
            }
            if(count($value[1]) !=2){
                return false;
            }
            return [true];
        }
    }


    /**
     * @Author pizepei
     * @Created 2019/4/21 16:12
     *
     * @param $data
     * @param $main
     * @param $lesser
     * @title  批处理固定格式数据
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     *
     */
    public function processingBatch( &$data, $main, $lesser)
    {
        $data = explode(',',$data);
        foreach($data as &$value){
            $value = explode($main,$value);
            foreach($value as $key=> &$valueLesser){
                $valueLesser = (count(explode($lesser,$valueLesser)) == 1)?$valueLesser:explode($lesser,$valueLesser);
            }
        }
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
     * @param $dir
     * @param $fileData
     */
    public function getFilePathData($dir,&$fileData)
    {
         # 打开应用目录获取所有文件路径
        if (is_dir($dir)){
            if ($dh = opendir($dir)){
                while (($file = readdir($dh)) !== false){
                    if($file != '.' && $file != '..'){
                        # 判断是否是目录
                        if(is_dir($dir.DIRECTORY_SEPARATOR.$file)){
                            $this->getFilePathData($dir.DIRECTORY_SEPARATOR.$file,$fileData);
                        }else{
                             # 判断是否是php文件
                            if(strrchr($file,'.php') == '.php'){
                                $fileData[] = $dir.DIRECTORY_SEPARATOR.$file;
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }
    }
    /**
     * 启动请求转移（实例化控制器）
     * @return mixed
     * @throws \Exception
     */
    public function begin()
    {
        # 处理路由 ->路由匹配路由->实例化控制器
        return $this->noteRoute();
    }


}