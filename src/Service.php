<?php
namespace Xbai\Plugins;

use support\Request;
use think\Route;
use think\Service as BaseService;
use Xbai\Plugins\middleware\PluginMiddleware;

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
            // 注册插件路由
            $execute = '\\Xbai\\Plugins\\service\\RouteService@execute';
            $route->rule("app/:plugin/[:module]/[:control]/[:action]", $execute)
            ->middleware(PluginMiddleware::class);
        });
    }
}