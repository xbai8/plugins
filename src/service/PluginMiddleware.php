<?php
declare(strict_types=1);

namespace Xbai\Plugins\service;

use Closure;
use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;

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
        // composer注册插件命名空间
        $this->registerNamespace();
        // 设置插件基础参数
        $this->setPluginApp();
        // 设置插件命名空间及路由
        $this->setPluginRoute();
        // 注册插件全局中间件
        $this->registerGlobalMiddleware();

        // 调度转发
        return $this->app->middleware
            ->pipeline('plugin')
            ->send($request)
            ->then(function ($request) use ($next) {
                return $next($request);
            });
    }

    /**
     * 设置插件命名空间及路由
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function setPluginRoute()
    {
        $request = $this->app->request;
        $plugin  = getPluginName();
        if (!is_dir($this->app->getRootPath() . "plugin/{$plugin}")) {
            throw new HttpException(500,"插件不存在：{$plugin}");
        }
        $request->plugin = $plugin;
        // 解析插件路由
        $route = (new Route($this->app))->parseRoute();
        // 设置路由及命名空间
        $this->app->setNamespace($route['namespace']);
        $request->setController($route['control']);
        $request->setAction($route['action']);
    }

    
    /**
     * 注册插件全局中间件
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function registerGlobalMiddleware()
    {
        $request = $this->app->request;
        // 获取框架中间件
        $middleware = config('plugins.middleware', []);
        // 获取插件配置中间件
        $pluginMiddleware = config("plugin.{$request->plugin}.middleware.", []);
        // 注册配置中间件
        $middlewares = array_merge($middleware, $pluginMiddleware);
        // 注册插件全局中间件
        $this->app->middleware->import($middlewares,'plugins');
    }

    /**
     * 插件基础设置
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function setPluginApp()
    {
        // 实例插件工具
        $pluginUtil = new \Xbai\Plugins\utils\PluginsUtil($this->app);
        // 设置插件基础参数
        $pluginUtil->initPlugin();
        // 加载插件配置项
        $pluginUtil->loadConfig();
        // 加载插件内composer包
        $pluginUtil->loadComposer();
        // 加载中间件
        $configMiddleware = config("plugins.middleware", []);
        $pluginMiddleware = config("plugin.{$this->app->request->plugin}.middleware.", []);
        $middlewares      = array_merge($configMiddleware, $pluginMiddleware);
        $this->app->middleware->import($middlewares, 'plugin');
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
}