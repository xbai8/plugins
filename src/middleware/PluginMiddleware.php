<?php
declare(strict_types=1);

namespace Xbai\Plugins\middleware;

use Closure;
use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;
use Xbai\Plugins\utils\PluginsUtil;

/**
 * 插件业务处理
 * @author 贵州猿创科技有限公司
 * @copyright 贵州猿创科技有限公司
 * @email 416716328@qq.com
 */
class PluginMiddleware
{

    /** @var App */
    protected $app;

    /**
     * 插件工具类
     * @var PluginsUtil
     * @author 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    protected $pluginUtil;

    /**
     * 中间件构造函数
     * @param \think\App $app
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 多应用插件配置
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        // 实例插件工具
        $pluginUtil       = new PluginsUtil($this->app);
        $this->pluginUtil = $pluginUtil;
        // 1.初始化应用插件基础参数
        $this->pluginUtil->initPlugin();
        // 2.解析路由
        $this->parseRoute();
        // 3.使用composer注册插件命名空间
        $this->registerNamespace();
        // 4.加载插件配置
        $this->pluginUtil->loadConfig();
        // 5.注册插件中间件
        $this->registerMiddlewares();

        // 调度转发
        return $this->app->middleware
            ->pipeline('plugin')
            ->send($request)
            ->then(function ($request) use ($next) {
                return $next($request);
            });
    }

    /**
     * 解析路由
     * @throws \Exception
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function parseRoute()
    {
        $pathinfo = $this->app->request->pathinfo();
        if (pathinfo($pathinfo, PATHINFO_EXTENSION)) {
            return;
        }
        $pathinfo = str_replace('app/', '', $pathinfo);
        $pathinfo = trim($pathinfo, '/');
        $pathArr = explode('/', $pathinfo);
        $pathCount = count($pathArr);
        $plugin = $pathArr[0] ?? '';
        if (empty($plugin)) {
            throw new \Exception("插件名称不能为空");
        }
        $this->app->request->plugin = $plugin;
        // 取控制器
        $control = config('route.default_controller','Index');
        // 取方法名
        $action = config('route.default_action','index');
        if ($pathCount > 1) {
            // 控制器
            $controlIndex = $pathCount - 2;
            $control = ucfirst($pathArr[$controlIndex]);
            // 方法
            $acionIndex = $pathCount - 1;
            $action     = $pathArr[$acionIndex];
        }
        $isControlSuffix    = config('route.controller_suffix',true);
        $controllerSuffix   = $isControlSuffix ? 'Controller' : '';
        $this->app->request->control = "{$control}{$controllerSuffix}";
        $this->app->request->action = $action;
        
        // 层级
        unset($pathArr[0]);
        unset($pathArr[$pathCount - 1]);
        unset($pathArr[$pathCount - 2]);
        $this->app->request->levelRoute = implode('/', $pathArr);
    }

    /**
     * 使用composer注册插件命名空间
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function registerNamespace()
    {
        // 扫描插件目录并排除.和..
        $data = array_diff(scandir($this->app->getRootPath() . 'plugin'), ['.', '..']);
        // 实例命名空间类
        $loader = require $this->app->getRootPath() . 'vendor/autoload.php';
        // 绑定服务
        $this->app->bind('rootLoader', $loader);
        // 注册命名空间
        foreach ($data as $pluginName) {
            if (is_dir($this->app->getRootPath() . 'plugin/' . $pluginName)) {
                $pluginPath = $this->app->getRootPath() . "plugin/{$pluginName}/";
                $loader->setPsr4("plugin\\{$pluginName}\\", $pluginPath);
            }
        }
    }

    /**
     * 注册插件中间件
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function registerMiddlewares()
    {
        $request = $this->app->request;
        // 获取框架全局中间件
        $middleware = config('plugins.middleware', []);
        // 获取插件全局中间件
        $pluginMiddleware = config("plugin.{$request->plugin}.middleware", []);
        // 合并中间件
        $middlewares = array_merge($middleware, $pluginMiddleware);
        // 注册应用级中间件
        $plugin = $this->app->request->route('plugin','');
        $module = $this->app->request->route('module',config('app.default_app','index'));
        $pluginMiddlewarePath = $this->app->getRootPath()."plugin/{$plugin}/app/{$module}/middleware";
        if (is_dir($pluginMiddlewarePath)) {
            // 扫描php文件
            $data = glob("{$pluginMiddlewarePath}/*.php");
            foreach ($data as $file) {
                $class = str_replace('.php', '', basename($file));
                $middlewares[] = "plugin\\{$plugin}\\app\\{$module}\\middleware\\{$class}";
            }
        }
        // 注册插件全局中间件
        $this->app->middleware->import($middlewares,'plugin');
    }
}