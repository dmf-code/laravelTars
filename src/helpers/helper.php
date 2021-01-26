<?php

use App\Tars\utils\TarsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists("get_unique_id")) {
    function get_unique_id()
    {
        $EPOCH = 1479533469598;
        $max12bit = 4095;
        $max41bit = 1099511627775;
        $machineId = 1;      // 机器id
        // 时间戳 42字节
        $time = floor(microtime(true) * 1000);
        // 当前时间 与 开始时间 差值
        $time -= $EPOCH;
        // 二进制的 毫秒级时间戳
        $base = decbin($max41bit + $time);
        // 机器id  10 字节
        $machineid = str_pad(decbin($machineId), 10, "0", STR_PAD_LEFT);
        // 序列数 12字节
        $random = str_pad(decbin(mt_rand(0, $max12bit)), 12, "0", STR_PAD_LEFT);
        // 拼接
        $base = $base.$machineid.$random;
        // 转化为 十进制 返回
        return bindec($base);
    }
}

if (!function_exists("resp")) {
    function resp($code, $msg, $data = [])
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
    }
}

if (!function_exists("tars_request")) {
    function tars_request($id, $path, $method='GET', $options=[], $ssl=false)
    {
        $tarsRes = TarsHelper::getHttpIpAndPort($id);

        // 设置默认超时时间
        if (!isset($options['timeout'])) {
            $options['timeout'] = 30;
        }

        if ($tarsRes['code'] != 200) {
            return resp(400, $tarsRes['msg']);
        }

        $client = new \GuzzleHttp\Client();

        $url = 'http';
        if ($ssl) {
            $url = 'https';
        }

        $url .= "://{$tarsRes['data']['ip']}:{$tarsRes['data']['port']}";
        $url .= $path;

        $res = $client->request($method, $url, $options);

        $res = json_decode($res->getBody()->getContents(), true);

        return $res;
    }
}

if (!function_exists('batch_update')) {
    function batch_update(Model $table, array $values, string $index = null) {
        try {

            if (!count($values)) {
                return false;
            }

            if (isset($index) || empty($index)) {
                $index = $table->getKeyName();
            }

            $tableName = DB::getTablePrefix() . $table->getTable();

            $updateSql = "UPDATE {$tableName} SET ";

            $ids = [];
            $sets = [];
            $bindings = [];

            $updateColumn = array_keys(current($values));


            foreach ($updateColumn as $uColumn) {
                $setSql = "`{$uColumn}` = CASE ";
                foreach ($values as $key => $value) {
                    $id = $value[$index];
                    $ids[] = $id;
                    $setSql .= "WHEN `{$index}` = ? THEN ? ";
                    $bindings[] = $id;
                    $bindings[] = $value[$uColumn];
                }

                $setSql .= "ELSE `{$uColumn}` END ";
                $sets[] = $setSql;
            }

            $updateSql .= implode(', ', $sets);
            $updateSql = rtrim($updateSql, ', '). " WHERE `$index` IN(" . '"' . implode('","', $ids) . '"' . ");";

            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}

if (!function_exists('chuck_batch_update')) {
    function chuck_batch_update(Model $table, array $values, string $index = null)
    {
        try {
            $chucks = array_chunk($values, 200, true);

            foreach ($chucks as $chuck) {
                $res = batch_update($table, $chuck, $index);

                if ($res === false) {
                    \Log::error("数据更新失败");
                }

            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}

if (!function_exists('log_standard_error')) {
    function log_standard_error(\Exception $e)
    {
        $str = sprintf("File: %s, Line %s, %s", $e->getFile(), $e->getLine(), $e->getMessage());
        Log::error($str);
    }
}
