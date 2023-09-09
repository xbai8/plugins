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
        // 1.使用composer注册插件命名空间
        $this->registerNamespace();
        // 2.注册插件全局中间件
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
        $pluginMiddleware = config("plugin.{$request->plugin}.middleware", []);
        // 注册配置中间件
        $middlewares = array_merge($middleware, $pluginMiddleware);
        // 注册插件全局中间件
        $this->app->middleware->import($middlewares, 'plugins');
    }
}