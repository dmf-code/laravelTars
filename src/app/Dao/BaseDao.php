<?php


namespace App\Dao;


use Illuminate\Database\Eloquent\Model;

abstract class BaseDao
{
    protected $model;
    protected $params;
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->params = [];
    }

    public function appendParam($key, $value=null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->params[$k] = $v;
            }
            return $this;
        }

        $this->params[$key] = $value;

        return $this;
    }

    public function hasParam($key, $field=null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (!is_null($param = request()->input($k, null))) {
                    $this->params[$v] = $param;
                }
            }
            return $this;
        }
        if (is_null($field)) {
            $field = $key;
        }

        if (!is_null($param = request()->input($key, null))) {
            $this->params[$field] = $param;
        }

        return $this;
    }

    public function hasLike($key, $filed=null, $format='%%%s%%')
    {
        if (is_null($filed)) {
            $filed = $key;
        }
        if (!is_null($param = request()->input($key, null))) {
            $value = sprintf($format, $param);
            $this->model->where($filed, 'like', $value);
        }
        return $this;
    }

    abstract public function pagination($perPage);

    abstract public function insert();

    abstract public function update($id);

    abstract public function delete($id);
}
