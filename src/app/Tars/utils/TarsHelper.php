<?php


namespace App\Tars\utils;

use Tars\registry\QueryFWrapper;
use Tars\Utils;

class TarsHelper
{
    protected static $tarsConfig=[];
    protected static $application;
    protected static $serverName = '';
    protected static $setting;
    protected static $impl;
    protected static $protocol;
    protected static $interval;
    protected static $locator;
    protected static $moduleName;

    /**
     * 获取tars配置文件信息
     * @return mixed
     */
    public static function getTarsConfig()
    {
        if (!self::$tarsConfig) {
            $cfg = config('tars.deploy_cfg');
           self::$tarsConfig = Utils::parseFile($cfg);
        }
        return self::$tarsConfig;
    }

    /**
     * 获取主控地址
     * @return mixed|null
     */
    public static function getTarsLocator(){
        $tarsConfig = static::getTarsConfig();
        return $tarsConfig['tars']['application']['client']['locator'] ?? null;
    }

    /**
     * 返回servant对象
     * @param string $servantClassName
     * @param array $args
     * @return mixed
     */
    public static function servantFactory(string $servantClassName){
            $vars = get_class_vars($servantClassName);
            $endpoint = $vars['_servantName'];
            list($appName,$serviceName,$objName)=explode('.',$endpoint,3);

            $config = new \Tars\client\CommunicatorConfig();

            $locator = static::getTarsLocator();

            $config->setLocator($locator);
            $config->setModuleName("$appName.$serviceName");
            $config->setCharsetName('UTF-8');
            $config->setSocketMode(2); //1标识socket 2标识swoole同步 3标识swoole协程

            return new $servantClassName($config);
    }

    /**
     * 获取http服务的ip和地址
     * @param $id
     * @return array
     */
    public static function getHttpIpAndPort($id)
    {
        try {

            $locator = self::getTarsLocator();

            $queryF = new QueryFWrapper($locator, 2);
            $routeInfo = $queryF->findObjectById($id);

            $count = count($routeInfo) - 1;
            $index = rand(0, $count);
            $ip = empty($sIp) ? $routeInfo[$index]['sIp'] : $sIp;
            $port = empty($iPort) ? $routeInfo[$index]['iPort'] : $iPort;
            $bTcp = isset($routeInfo[$index]['bTcp']) ? $routeInfo[$index]['bTcp'] : 1;
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return helpResponse(400, '查询失败');
        }

        return helpResponse(200, 'ok', [
            'ip' => $ip,
            'port' => $port
        ]);

    }
}
