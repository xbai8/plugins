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
    protected $app;
    protected $request;
    protected $plugin;
    protected $controlSuffix;
    protected $controlLayout;

    /**
     * 应用实例
     * @param \think\App $app
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->plugin = $this->request->plugin;
        $isControlSuffix = config('route.controller_suffix', true);
        $controllerSuffix = $isControlSuffix ? 'Controller' : '';
        $this->controlSuffix = $controllerSuffix;
        $controllerLayout = config('route.controller_layer', '');
        $this->controlLayout = $controllerLayout;
    }

    /**
     * 解析路由参数
     * @param mixed $request
     * @param mixed $plugin
     * @return array
     * @author John
     */
    public function parseRoute()
    {
        // 获取请求URL
        $pathinfo = $this->request->pathinfo();
        // 移除前缀
        $path = str_replace("app/{$this->plugin}", '', $pathinfo);
        // 删除前尾
        $path = trim($path, '/');
        // 场景1：无模块，默认控制器，默认方法
        if (empty($path)) {
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$this->controlLayout}";
            return $this->getDefaultAttrs($namespace);
        }
        // 获得分组参数
        $array = explode('/', $path);
        $dataCount = substr_count($path, '/');
        // 场景2：1个参数：有模块，实际控制器，默认方法
        if ($dataCount === 0) {
            // 是否存在控制器
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$this->controlLayout}\\{$array[0]}{$this->controlSuffix}";
            if (class_exists($namespace)) {
                $namespace = "\\plugin\\{$this->plugin}\\app\\{$this->controlLayout}";
                return $this->getDefaultAttrs($namespace, $array[0]);
            }
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$array[0]}\\{$this->controlLayout}";
            return $this->getDefaultAttrs($namespace);
        }
        // 场景3：2个参数，有模块，实际控制器，默认方法
        if ($dataCount === 1) {
            // 是否存在控制器
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$this->controlLayout}\\{$array[0]}{$this->controlSuffix}";
            if (class_exists($namespace)) {
                $namespace = "\\plugin\\{$this->plugin}\\app\\{$this->controlLayout}";
                return $this->getDefaultAttrs($namespace, $array[0], $array[1]);
            }
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$array[0]}\\{$this->controlLayout}";
            return $this->getDefaultAttrs($namespace, $array[1]);
        }
        // 场景4：实际模块，实际控制器，实际方法
        if ($dataCount === 2) {
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$array[0]}\\{$this->controlLayout}";
            return $this->getDefaultAttrs($namespace, $array[1], $array[2]);
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
            $levelLayout = implode('\\', $array);
            $namespace = "\\plugin\\{$this->plugin}\\app\\{$array[0]}\\{$this->controlLayout}\\{$levelLayout}";
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
    private function getDefaultAttrs($namespace,$control = null, $action = null)
    {
        if (empty($control)) {
            $control = config('route.default_controller', 'Index');
        }
        if (empty($action)) {
            $action = config('route.default_action', 'index');
        }
        $control    = ucfirst($control);
        // 控制器后缀
        $controller = $control . $this->controlSuffix;
        // 返回默认数据
        return [
            'namespace'     => $namespace,
            'control'       => $controller,
            'action'        => $action,
        ];
    }
}