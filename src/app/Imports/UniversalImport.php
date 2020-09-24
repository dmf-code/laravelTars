<?php

namespace App\Imports;

use App\Exceptions\ExcelException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UniversalImport implements ToCollection, WithHeadingRow
{
    private $headingRow = 1;
    private $rules = [];

    private $filename;

    private $model;

    private $fillKeys;

    public function __construct(Model $model, array $fillKeys, array $rules)
    {
        $this->model = $model;
        $this->fillKeys = $fillKeys;
        $this->rules = $rules;
    }

    /**
     * 校验数据不能为空
     * @param $col
     * @return bool|string
     */
    public function checkNotEmpty($col)
    {
        if (empty($col)) {
            return '数据不能为空';
        }

        return true;
    }

    public function checkMobile($col)
    {
        if (!preg_match("/^1[34578]\d{9}$/", $col)) {
            return "请填写正确手机号";
        }

        return true;
    }

    /**
     * @param $col
     * @param $args ['max_len' =>  12]
     * @return bool|string
     */
    public function checkMoreThan($col, $args)
    {
        if (mb_strlen($col) > $args['max_len']) {
            return "该字段不能超过 {$args['max_len']} 个字";
        }

        return true;
    }

    /**
     * @param $col
     * @param $args ['label' => 'key 标签', 'key' => ['1' => 1]]
     * @return bool|string
     */
    public function checkKeyExists($col, $args)
    {
        if (!array_key_exists($col, $args['key'])) {
            return "{$args['label']} 不存在";
        }

        return true;
    }

    /**
     * @param $col
     * @param $args ['label' => '导入的head名称']
     * @return string
     */
    public function checkNumber($col, $args)
    {
        if (!is_numeric($col)) {
            return "{$args['label']} 不是数字";
        }
    }

    /**
     * 设置文件名
     * @param $name
     * @return $this
     */
    public function setFileName($name)
    {
        $this->filename = $name;
        return $this;
    }

    /**
     * 获取文件名
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function verifyRow(int $line, array $header, array $cols)
    {
        $errors = [];
        if (empty($this->rules)) {
            return true;
        }
        foreach ($this->rules as $k => $v) {
            $func = explode(',', $v['func']);
            foreach ($func as $kk => $vv) {
                switch ($vv) {
                    case 'checkNotEmpty':
                        $error = $this->checkNotEmpty($cols[$k]);
                        break;
                    case 'checkMobile':
                        $error = $this->checkMobile($cols[$k]);
                        break;
                    case 'checkMoreThan':
                        $error = $this->checkMoreThan($cols[$k], $v['args']);
                        break;
                    case 'checkKeyExists':
                        $error = $this->checkKeyExists($cols[$k], $v['args']);
                        break;
                    case 'checkNumber':
                        $error = $this->checkNumber($cols[$k], $v['args']);
                        break;
                    default:
                        $error = true;
                }
            }

            if (is_string($error)) {
                array_push($errors, "表格{$line}行-{$k}列($header[$line][$k])，{$error}");
            }
        }

        return $errors;
    }

    /**
     * @param Collection $rows
     * @throws ExcelException
     */
    public function collection(Collection $rows)
    {
        $errors = [];
        $headers = [];
        $data = [];
        $fillKeys = collect($this->fillKeys);
        foreach ($rows as $k => $v) {
            // 不校验表头
            if ($k < $this->headingRow) {
                array_push($headers, $v);
                continue;
            }
            $error = $this->verifyRow($k, $headers, $v);

            if (is_string($error)) {
                array_push($errors, $error);
            }

            array_push($data, $fillKeys->zip($v)->toArray());
        }

        if (!empty($errors)) {
            throw new ExcelException(implode(PHP_EOL, $errors));
        }

        $this->model->insert($data);
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }
}
