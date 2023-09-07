<?php
namespace Xbai\Plugins\service;

use think\App;
use think\exception\HttpException;

/**
 * 插件服务
 * @author 贵州猿创科技有限公司
 * @copyright 贵州猿创科技有限公司
 * @email 416716328@qq.com
 */
class Route
{
    /** @var App */
    protected $app;

    /**
     * 插件名称
     * @var string
     */
    protected $plugin;

    /**
     * 构造函数
     * @param \think\App $app
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function __construct(App $app)
    {
        // 设置对象实例
        $this->app = $app;
        // 实例插件工具
        $pluginUtil = new \Xbai\Plugins\utils\PluginsUtil($app);
        // 设置插件基础参数
        $pluginUtil->initPlugin();
        // 加载插件配置项
        $pluginUtil->loadConfig();
        // 加载插件内composer包
        $pluginUtil->loadComposer();
    }

    /**
     * 插件路由请求
     * @param mixed $plugin
     * @return string
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function execute($plugin)
    {
        $request = $this->app->request;
        if (!is_dir($this->app->getRootPath() . "plugin/{$plugin}")) {
            throw new HttpException(500,"插件不存在：{$plugin}");
        }
        // 解析插件
        $route = $this->parseRoute($request, $plugin);
        $class = "{$route['namespace']}{$route['control']}";
        if (!class_exists($class)) {
            throw new HttpException(500,"控制器不存在：{$class}");
        }
        if (!method_exists($class, $route['action'])) {
            throw new HttpException(500,"方法不存在：{$class}@{$route['action']}");
        }
        // 注册中间件
        $this->registerMiddleware();
        // 执行操作方法
        $class = new $class($this->app);
        $call  = [$class, $route['action']];
        $vars  = [$request];
        // 转发操作
        return call_user_func_array($call, $vars);
    }

    private function registerMiddleware()
    {
        // 获取框架中间件
        $middleware = config('plugins.middleware', []);
        // 获取插件配置中间件
        $pluginMiddleware = config("plugin.{$this->plugin}.middleware.", []);
        // 注册配置中间件
        $middlewares = array_merge($middleware, $pluginMiddleware);
        // 注册全局中间件
        $this->app->middleware->import($middlewares);
    }

    /**
     * 解析参数
     * @param mixed $request
     * @param mixed $plugin
     * @return array
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function parseRoute($request, $plugin)
    {
        $this->plugin = $plugin;
        // 获取请求URL
        $pathinfo = $request->pathinfo();
        // 移除前缀
        $path = str_replace("app/{$plugin}", '', $pathinfo);
        // 场景1：无模块，默认控制器，默认方法
        if (empty($path)) {
            return $this->getDefaultAttrs();
        }
        $path  = substr($path, 1);
        $array = explode('/', $path);
        $count = count($array);
        // 场景2：1个参数，无模块，实际控制器，默认方法
        if ($count === 1) {
            return $this->getDefaultAttrs(null, $array[0]);
        }
        // 场景3：2个参数，无模块，实际控制器，实际方法
        if ($count === 2) {
            return $this->getDefaultAttrs(null, $array[0], $array[1]);
        }
        $namespace = "\\plugin\\{$this->plugin}\\app\\{$array[0]}\\controller\\";
        // 场景4：3个参数，模块，控制器，方法
        if ($count === 3) {
            return $this->getDefaultAttrs($namespace, $array[1], $array[2]);
        }
        // 场景5：4个参数，模块，层级，控制器，方法
        if ($count === 4) {
            return $this->getDefaultAttrs("{$namespace}{$array[1]}\\", $array[2], $array[3]);
        }
        // 场景6：模块，无限层级，控制器，方法
        if ($count > 4) {
            $module       = current($array);
            $namespace    = "\\plugin\\{$this->plugin}\\app\\{$module}\\controller\\";
            $controlIndex = $count - 2;
            $control      = $array[$controlIndex];
            $actionIndex  = $count - 1;
            $action       = $array[$actionIndex];
            unset($array[0]);
            unset($array[$controlIndex]);
            unset($array[$actionIndex]);
            foreach ($array as $level) {
                $namespace .= "{$level}\\";
            }
            return $this->getDefaultAttrs($namespace, $control, $action);
        }
        throw new HttpException(500,"插件路由解析失败");
    }

    /**
     * 获取默认参数
     * @param mixed $namespace
     * @param mixed $control
     * @param mixed $action
     * @return array
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function getDefaultAttrs($namespace = null, $control = null, $action = null)
    {
        if (empty($control)) {
            $control = 'Index';
        }
        if (empty($action)) {
            $action = 'index';
        }
        if (empty($namespace)) {
            $namespace = "\\plugin\\{$this->plugin}\\app\\controller\\";
        }
        $control    = ucfirst($control);
        $controller = $control . "Controller";
        return [
            'namespace' => $namespace,
            'control' => $controller,
            'action' => $action,
        ];
    }
}