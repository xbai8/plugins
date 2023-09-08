<?php
declare(strict_types=1);
use think\helper\{
    Str, Arr
};
if (!function_exists('getPluginName')) {
    /**
     * 获取当前请求的插件名称
     * @return string|null
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    function getPluginName()
    {
        $request  = request();
        $pathInfo = $request->pathinfo();
        // 检测字符串中是否带有app/字符串
        if (strpos($pathInfo, 'app/') === false) {
            return '';
        }
        $response = explode('/',$pathInfo);
        if (count($response) < 2) {
            return '';
        }
        return isset($response[1]) ? $response[1] : '';
    }
}
if (!function_exists('getPluginConfig')) {
    /**
     * 获取插件配置
     * @param mixed $name
     * @param mixed $default
     * @return array
     * @author John
     */
    function getPluginConfig($name,$default = null)
    {
        $pluginName = getPluginName();
        if (empty($pluginName)) {
            return $default;
        }
        $pluginPath = getPluginPath($pluginName, '');
        if (empty($pluginPath)) {
            return $default;
        }
        $configPath = "{$pluginPath}/config";
        // 加载配置
        \think\facade\Config::load("{$configPath}/{$name}.php",$name);
        // 读取配置
        $config = config($name,$default);
        return $config;
    }
}
if (!function_exists('getPluginPath')) {
    /**
     * 获取插件路径
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     * @author John
     */
    function getPluginPath($name,$default = null)
    {
        $app = app();
        $rootPath = $app->getRootPath();
        $pluginPath = "{$rootPath}plugin/{$name}/";
        if (!is_dir($pluginPath)) {
            return $default;
        }
        return $pluginPath;
    }
}
if (!function_exists('getPluginInfo')) {
    /**
     * 获取插件信息
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    function getPluginInfo($name,$default = null)
    {
        $pluginPath = getPluginPath($name);
        if (empty($pluginPath)) {
            return $default;
        }
        $infoFile = "{$pluginPath}/info.json";
        if (!is_file($infoFile)) {
            return $default;
        }
        $info = file_get_contents($infoFile);
        $info = json_decode($info,true);
        return $info;
    }
}
if (!function_exists('getPluginControl')) {
    /**
     * 获取插件类的类命名空间
     * @param mixed $name
     * @param mixed $class
     * @param mixed $suffix
     * @return string
     * @author John
     */
    function getPluginControl($pluginName, $class = null,$suffix = '')
    {
        $name = trim($pluginName);
        // 处理多级控制器情况
        if (!is_null($class) && strpos($class, '/')) {
            $class = explode('/', $class);

            $class[count($class) - 1] = Str::studly(end($class));
            $class = implode('\\', $class);
        } else {
            $class = Str::studly(is_null($class) ? $name : $class);
            $class = "controller\\{$class}";
        }
        $namespace = "\\plugin\\{$name}\\app\\{$class}{$suffix}";
        return $namespace;
    }
}
if (!function_exists('getBetween')) {
    /**
     * 截取指定两个字符之间内容
     * @param mixed $input
     * @param mixed $start
     * @param mixed $end
     * @return string
     * @author John
     */
    function getBetween($input, $start, $end)
    {
        $substr = substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
        return $substr;
    }
}