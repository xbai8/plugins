<?php
namespace Xbai\Plugins;

use think\Route;
use think\Service as BaseService;
use Xbai\Plugins\service\PluginMiddleware;

/**
 * 插件服务
 * @author 贵州猿创科技有限公司
 * @copyright 贵州猿创科技有限公司
 * @email 416716328@qq.com
 */
class Service extends BaseService
{
    /**
     * 引导服务
     * @param \think\Route $route
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function boot(Route $route)
    {
        // 检测插件目录不存在则创建
        if (!is_dir($this->app->getRootPath() . 'plugin')) {
            mkdir($this->app->getRootPath() . 'plugin', 0755, true);
        }

        // 监听服务
        $this->app->event->listen('HttpRun', function () use ($route) {
            $route->rule('app/:plugin', function ($plugin) {
                $namespace = "{$this->app->getNamespace()}\\{$this->app->request->controller()}";
                $class = new $namespace($this->app);
                // 执行转发
                return call_user_func_array([
                    $class, $this->app->request->action()
                ], [
                    $this->app->request
                ]);
            })->middleware(PluginMiddleware::class);

            // $this->app->middleware->add(PluginMiddleware::class);

            // 注册基础路由
            // $route->rule('app/:plugin', "\\Xbai\\Plugins\\service\\Route@execute")
            // ->middleware(PluginMiddleware::class);
        });
    }
}