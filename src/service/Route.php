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
        // 注册中间件
        $this->registerMiddleware();
        // 检测控制器是否存在
        if (!class_exists($class)) {
            throw new HttpException(500,"插件控制器不存在：{$class}");
        }
        // 检测插件控制器方法是否存在
        if (!method_exists($class, $route['action'])) {
            throw new HttpException(500,"插件控制器方法不存在：{$class}@{$route['action']}");
        }
        // 执行操作方法
        $class = new $class($this->app);
        $call  = [
            $class,
            $route['action']
        ];
        $vars  = [
            $request
        ];
        // 转发操作
        return call_user_func_array($call, $vars);
    }

    /**
     * 注册中间件
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function registerMiddleware()
    {
        // 获取框架中间件
        $middleware = config('plugins.middleware', []);
        // 获取插件配置中间件
        $pluginMiddleware = config("plugin.{$this->plugin}.middleware.", []);
        // 注册配置中间件
        $middlewares = array_merge($middleware, $pluginMiddleware);
        // 注册插件全局中间件
        $this->app->middleware->import($middlewares,'plugins');
    }

    /**
     * 解析路由参数
     * @param mixed $request
     * @param mixed $plugin
     * @return array
     * @author John
     */
    private function parseRoute($request,$plugin)
    {
        $this->plugin = $plugin;
        // 获取请求URL
        $pathinfo = $request->pathinfo();
        // 移除前缀
        $path = str_replace("app/{$plugin}", '', $pathinfo);
        // 删除前尾
        $path = trim($path, '/');
        // 场景1：无模块，默认控制器，默认方法
        if (empty($path)) {
            return $this->getDefaultAttrs();
        }
        // 获得分组参数
        $array = explode('/', $path);
        $dataCount = substr_count($path, '/');
        // 场景2：1个参数：无模块，实际控制器，默认方法
        if ($dataCount === 0) {
            return $this->getDefaultAttrs(null,$array[0]);
        }
        // 场景3：2个参数，无模块，实际控制器，默认方法
        if ($dataCount === 1) {
            return $this->getDefaultAttrs(null,$array[0], $array[1]);
        }
        // 场景4：实际模块，实际控制器，实际方法
        if ($dataCount === 2) {
            return $this->getDefaultAttrs($array[0], $array[1], $array[2]);
        }
        // 场景5：实际模块，实际层级，实际控制器，实际方法
        if ($dataCount >= 3) {
            $countAttrs   = count($array);
            $controlIndex = $countAttrs-2;
            $control      = $array[$controlIndex];
            $actionIndex  = $countAttrs-1;
            $action       = $array[$dataCount];
            unset($array[$controlIndex]);
            unset($array[$actionIndex]);
            $namespace = implode('\\', $array);
            return $this->getDefaultAttrs($namespace, $control, $action);
        }
        throw new HttpException(500,"插件路由解析失败");
    }

    /**
     * 获取默认参数
     * @param mixed $module
     * @param mixed $control
     * @param mixed $action
     * @return array
     * @author John
     */
    private function getDefaultAttrs($module = null,$control = null, $action = null)
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
        $namespace .= "{$module}";
        $control    = ucfirst($control);
        $controller = $control . "Controller";
        return [
            'namespace'     => $namespace,
            'control'       => $controller,
            'action'        => $action,
        ];
    }
}