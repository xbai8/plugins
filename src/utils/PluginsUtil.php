<?php
namespace Xbai\Plugins\utils;

use think\App;

class PluginsUtil
{
    /**
     * 应用实例
     * @var \think\App
     * @author 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private $app;

    /**
     * 插件标识名称
     * @var string
     * @author 贵州猿创科技有限公司
     * @email 416716328@qq.com
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
        $this->app    = $app;
        $this->plugin = getPluginName();
    }

    /**
     * 初始化插件基础参数设置
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function initPlugin()
    {
        $request = $this->app->request;
        // 设置插件名称
        $request->plugin = $this->plugin;
        // 设置插件目录
        $request->pluginPath = $this->app->getRootPath() . "plugin/{$this->plugin}/";
        // 设置插件应用目录
        $request->pluginAppPath = $request->pluginPath . "app/";
        // 设置插件模板目录
        $request->pluginViewPath = $request->pluginPath . "view/";
        // 设置插件配置文件目录
        $request->pluginConfigPath = $request->pluginPath . "config/";
        // 设置插件语言包目录
        $request->pluginLangPath = $request->pluginPath . "lang/";
        // 设置插件静态资源目录
        $request->pluginPublicPath = $request->pluginPath . "public/";
    }

    /**
     * 加载插件配置项
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function loadConfig()
    {
        // 请求对象
        $request = $this->app->request;
        // 插件名称
        $plugin = $request->plugin;
        // 插件目录
        $pluginPath = $request->pluginPath;
        // 插件应用目录
        $appPath = $request->pluginAppPath;
        // 插件配置目录
        $configPath = $request->pluginConfigPath;

        // 加载兼容函数库文件
        if (is_file($pluginPath . '/functions.php')) {
            include_once $pluginPath . '/functions.php';
        }
        // 加载TP类型函数库文件
        if (is_file($pluginPath . '/common.php')) {
            include_once $pluginPath . '/common.php';
        }

        $files = [];
        // 合并配置文件
        $files = array_merge($files, glob($configPath . '*' . $this->app->getConfigExt()));
        // 加载配置文件
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $configName = pathinfo($file, PATHINFO_FILENAME);
            $configData = include $file;
            if (is_array($configData)) {
                $configs = $this->app->config->get("plugin.{$plugin}", []);
                if (empty($configs)) {
                    // 首次添加
                    $configData = [
                        $plugin => [
                            $configName => $configData,
                        ],
                    ];
                } else {
                    // 后续添加
                    $configs[$configName] = $configData;
                    $configData  = [
                        $plugin => $configs,
                    ];
                }
                $this->app->config->set($configData, 'plugin');
            }
        }

        // 加载事件文件
        if (is_file($configPath . '/event.php')) {
            $this->app->loadEvent(include $configPath . '/event.php');
        }

        // 加载容器文件
        if (is_file($configPath . '/provider.php')) {
            $this->app->bind(include $configPath . '/provider.php');
        }
    }

    /**
     * 加载插件内composer包
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function loadComposer()
    {
        $request    = $this->app->request;
        $pluginPath = $request->pluginPath;

        // 检测插件内composer包
        $packageFile = $pluginPath . "package/vendor/autoload.php";
        if (!is_file($packageFile)) {
            return;
        }
        // 加载插件内composer包
        $pluginLoader = include_once $packageFile;
        // $data         = $pluginLoader->getPrefixesPsr4();
        // if (empty($data)) {
        //     return;
        // }
        // 拼接命名空间:plugin\{$pluginName}\{$name}
        // $loader     = $this->app->rootLoader;
        // $paths = [];
        // foreach ($data as $name => $value) {
        //     foreach ($value as $path) {
        //         $loader->setPsr4("plugin\\{$this->plugin}\\package\\{$name}", $path);
        //     }
        // }
    }

    /**
     * 加载插件后台
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    public function loadAdmin()
    {
        $data = config("plugin.{$this->plugin}.admin",[]);
        if (empty($data)) {
            return;
        }
        foreach ($data as $key => $value) {
            $adminName  = $value ? "/{$value}" : '';
            $data[$key] = "app/{$this->plugin}{$adminName}";
        }
        $this->registerAdmin($data);
    }

    /**
     * 注册后台视图路由
     * @return void
     * @author 贵州猿创科技有限公司
     * @copyright 贵州猿创科技有限公司
     * @email 416716328@qq.com
     */
    private function registerAdmin(array $data)
    {
        if (empty($data)) {
            return;
        }
        echo 'ok';
        // 视图路径
        $viewPath = str_replace('\\', '/', root_path('view'));
        print_r($viewPath);
        exit;
    }
}