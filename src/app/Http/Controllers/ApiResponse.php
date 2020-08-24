<?php


namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;

trait ApiResponse
{
    protected function success($msg="ok", $data = [], $code=200) {
        if (is_object($data)) {
            $data = json_decode( json_encode( $data),true);
            if (isset($data['__fields'])) {
                unset($data['__fields']);
            }
            if (isset($data['__typeName'])) {
                unset($data['__typeName']);
            }
        }

        return $this->response($code, $msg, $data);
    }

    protected function failed($code=400, $msg='', $data=[])
    {
        $ret = [
            'code' => $code,
            'message' => $msg,
            'data' => $data
        ];
        return response()->json($ret)->setStatusCode(200);
    }

    protected function response($code = null, $msg = null, $data = null) {
        $code = $code ?? Response::HTTP_OK;
        $msg = $msg ?? Response::$statusTexts[$code];
        $ret = [
            'code' => $code,
            'message' => $msg,
            'data' => $data,
        ];
        return response()->json($ret)->setStatusCode($code);
    }

    protected function formatPaginator(LengthAwarePaginator $paginator) {
        return $this->success("ok", [
            'items'=>$paginator->items(),
            'total'=>$paginator->total(),
            'current_page'=>$paginator->currentPage(),
            'per_page'=>$paginator->perPage(),
        ]);
    }

    protected function customPaginator($list,$perPage = 10, $isSlice = true, $total = null) {

        if (request()->has('page')) {
            $current_page = request()->input('page');
            $current_page = $current_page <= 0 ? 1 : $current_page;
        } else {
            $current_page = 1;
        }
        if ($isSlice) {
            $item = array_slice($list, ($current_page - 1) * $perPage, $perPage);//$Array为要分页的数组
        } else {
            $item = $list;
        }

        if (is_null($total)) {
            $totals = count($list);
        } else {
            $totals = $total;
        }
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($item, $totals, $perPage, $current_page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        return $this->formatPaginator($paginator);
    }

    public function validator($rules, $message = [])
    {
        $validator = Validator::make(request()->all(), $rules, $message);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        return null;
    }

    public function equal($model, $key, $field=null)
    {
        if (!is_null($item = request()->input($key, null))) {
            if (is_null($field)) {
                $field = $key;
            }
            $model->where([$field => $item]);
        }
    }

    public function in($model, $key, $field=null)
    {
        if (!is_null($item = request()->input($key, null))) {
            if (is_null($field)) {
                $field = $key;
            }
            $model->whereIn($field, explode(",", $item));
        }
    }

    public function like($model, $key, $field=null, $format=null)
    {
        if (!is_null($item = request()->input($key, null))) {
            if (is_null($field)) {
                $field = $key;
            }
            if (!is_null($format)) {
                $item = sprintf($format, $item);
            }
            $model->where($field, 'like', $item);
        }
    }

    public function date($model, $key = 'date', $field='created_at')
    {
        if (!is_null($date = request()->input($key, null))) {
            switch ($date) {
                case 'day':
                    $model->whereDate($field, Carbon::today()->toDateString());
                    break;
                case '-day':
                    $model->whereDate($field, Carbon::yesterday()->toDateString());
                    break;
                case '-7days':
                    $model->whereDate($field, '>=', Carbon::parse('-7 days')->toDateString())
                    ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-15days':
                    $model->whereDate($field, '>=', Carbon::parse('-15 days')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-30days':
                    $model->whereDate($field, '>=', Carbon::parse('-30 days')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-1/2year':
                    $model->whereDate($field, '>=', Carbon::parse('-6 months')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
                case '-year':
                    $model->whereDate($field, '>=', Carbon::parse('-1 year')->toDateString())
                        ->whereDate($field, '<=', Carbon::now()->toDateString());
                    break;
            }
        }
    }

    public function dateBetween($model, $col="created_at", $start="date_start", $end="date_end")
    {
        $start = request()->input($start, null);
        $end = request()->input($end, null);
        if (!is_null($start) && !is_null($end)) {
            $model->where($col, '>=', $start)
                ->where($col, '<=', $end);
        }
    }
}
