<?php


namespace App\Dao;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

abstract class BaseDao
{
    protected $model;
    protected $params;
    public function __construct(Model $model)
    {
        $this->model = $model::query();
        $this->params = [];
    }

    public function hasDateBetween($field="created_at", $start="date_start", $end="date_end")
    {
        if (
            !is_null($startDate = request()->input($start, null)) &&
            !is_null($endDate = request()->input($end, null))
        ) {
            $this->model->where($field, '>=', $startDate)
                ->where($field, '<=', $endDate);
        }

        return $this;
    }

    public function hasDate($key = 'date', $field='created_at')
    {
        if (!is_null($date = request()->input($key, null))) {
            switch ($date) {
                case 'day':
                    $this->model->whereDate($field, Carbon::today()->toDateString());
                    break;
                case '-day':
                    $this->model->whereDate($field, Carbon::yesterday()->toDateString());
                    break;
                case '-7days':
                    $this->model->whereDate($field, '>=', Carbon::parse('-7 days')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-15days':
                    $this->model->whereDate($field, '>=', Carbon::parse('-15 days')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-30days':
                    $this->model->whereDate($field, '>=', Carbon::parse('-30 days')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-1/2year':
                    $this->model->whereDate($field, '>=', Carbon::parse('-6 months')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-year':
                    $this->model->whereDate($field, '>=', Carbon::parse('-1 year')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
            }
        }

        return $this;
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

    public function hasIn($key, $field=null)
    {
        if (!is_null($item = request()->input($key, null))) {
            if (is_null($field)) {
                $field = $key;
            }
            $this->model->whereIn($field, explode(",", $item));
        }

        return $this;
    }

    public function hasEqual($key, $field=null)
    {
        if (!is_null($item = request()->input($key, null))) {
            if (is_null($field)) {
                $field = $key;
            }
            $this->model->where([$field => $item]);
        }

        return $this;
    }

    abstract public function pagination($perPage);

    abstract public function insert();

    abstract public function update($id);

    abstract public function delete($id);

    abstract public function show($id);
}
