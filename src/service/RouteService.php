<?php
declare(strict_types=1);

namespace Xbai\Plugins\service;

use support\Request;
use think\App;

/**
 * 插件业务处理
 * @author 贵州猿创科技有限公司
 * @copyright 贵州猿创科技有限公司
 * @email 416716328@qq.com
 */
class RouteService
{

    /** @var App */
    protected $app;

    /**
     * 请求对象
     * @var Request
     * @author 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    protected $request;

    /**
     * 构造函数
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function __construct()
    {
        $this->app = app();
        $this->request = $this->app->request;
    }

    /**
     * 路由注册
     * @param mixed $plugin
     * @param mixed $module
     * @param mixed $control
     * @param mixed $action
     * @return mixed
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function execute($plugin,$module = null,$control = null,$action = null)
    {
        // 获取三层数据
        $module = $module ?: config('app.default_app','index');
        $controlLayout = config('route.controller_layer','controller');
        $control = $control ?: config('route.default_controller','Index');
        $action = $action ?: config('route.default_action','index');

        // 组装命名空间
        $pluginNameSpace = "plugin\\{$plugin}";
        $this->app->setNamespace($pluginNameSpace);

        // 组装控制器命名空间
        $isControlSuffix    = config('route.controller_suffix',true);
        $controllerSuffix   = $isControlSuffix ? 'Controller' : '';
        $class = "{$pluginNameSpace}\\app\\{$module}\\{$controlLayout}\\{$control}{$controllerSuffix}";

        // 获取实例类
        $instance   = new $class($this->app);
        $call       = [$instance, $action];
        $vars       = [$this->request];

        // 执行调度转发
        return call_user_func_array($call, $vars);
    }
}