<?php


namespace App\Tars\utils;

use Illuminate\Support\Facades\Log;
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
     * 获取主控配置对象
     * @param $moduleName
     * @param int $socketMode
     * @param string $charset
     * @return \Tars\client\CommunicatorConfig
     */
    public static function getCommunicatorConfig($moduleName, $socketMode=2, $charset='UTF-8')
    {
        try {
            $config = new \Tars\client\CommunicatorConfig();

            $locator = static::getTarsLocator();

            $config->setLocator($locator);
            $config->setModuleName($moduleName);
            $config->setCharsetName($charset);
            $config->setSocketMode($socketMode); //1标识socket 2标识swoole同步 3标识swoole协程
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return null;
        }
        return $config;
    }

    /**
     * 返回servant对象
     * @param string $servantClassName
     * @return mixed
     * @throws \Exception
     */
    public static function servantFactory(string $servantClassName) {
        $vars = get_class_vars($servantClassName);
        $endpoint = $vars['_servantName'];
        list($appName,$serviceName,$objName)=explode('.',$endpoint,3);

        $config = static::getCommunicatorConfig("$appName.$serviceName");

        if (is_null($config)) {
            throw new \Exception('获取主控配置对象失败');
        }

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

    /**
     * 获取配置
     * @param $fileName
     * @param string $dstPath
     * @param string $appName
     * @param string $serverName
     * @return bool
     * @throws \Exception
     */
    public static function getConfig($fileName, $dstPath='', $appName='Activity', $serverName='groupBuy')
    {
        try {
            $moduleName = "{$appName}.{$serverName}";
            $config = static::getCommunicatorConfig($moduleName);

            if (is_null($config)) {
                throw new \Exception('获取主控配置对象失败');
            }

            $conigServant = new \Tars\config\ConfigServant($config);
            $result = $conigServant->loadConfig($appName, $serverName, $fileName, $configtext); //参数分别为 appName(servant name 第一部分)，server name(servant name第二部分)，文件名，最后一个是引用传参，是输出的配置文件内容。

            $filePath = app()->basePath() . DIRECTORY_SEPARATOR . $dstPath . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($filePath, $configtext);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return false;
        }
        return true;
    }
}
