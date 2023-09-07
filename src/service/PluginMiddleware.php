<?php
declare (strict_types = 1);

namespace Xbai\Plugins\service;

use Closure;
use think\App;
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
        $this->app  = $app;
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
        // 注册插件命名空间
        $this->registerNamespace();
        // 设置插件基础参数
        $this->setPluginApp();

        // 调度转发
        return $this->app->middleware->pipeline('plugin')
            ->send($request)
            ->then(function ($request) use ($next) {
                return $next($request);
            });
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
        $middlewares = array_merge($configMiddleware, $pluginMiddleware);
        $this->app->middleware->import($middlewares,'plugin');
    }

    /**
     * 注册插件命名空间
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
