<?php


namespace App\Manager;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadManager
{
    protected static function handle($file, $name, $uploadType=1)
    {
        if ($file->isValid()) {
            $originalName = $file->getClientOriginalName();

            $ext = $file->getClientOriginalExtension();

            $type = $file->getMimeType();

            $realPath = $file->getRealPath();

            if ($uploadType == 1) {
                $extArray = ["jpg", "jpeg", "png"];
                if (!in_array($ext, $extArray)) {
                    throw new \Exception("上传图片后缀不符合");
                }
            } else {
                $extArray = ["xls", "xlsx"];
                if (!in_array($ext, $extArray)) {
                    throw new \Exception("上传文件后缀不符合");
                }
            }

            // 两兆,不能触发，未到代码层就报错了
//            if ($file->getSize() > 16777216) {
//                throw new \Exception("上传文件不能大于2M");
//            }

            $filename = date("Ymd").'/'.uniqid(). '.'.$ext;

            $bool = Storage::disk($name)->put($filename, file_get_contents($realPath));

            if ($bool) {
                return Storage::disk($name)->url($filename);
            }
        }
        return null;
    }

    /**
     * @param Request $request
     * @return
     */
    public static function upload($file, $type)
    {
        try {
            if ($type == 1) {
                $res = self::handle($file, 'images', $type);
                if (is_null($res)) {
                    return helpResponse(400, "上传图片失败");
                }
            } else {
                $res = self::handle($file, 'files', $type);
                if (is_null($res)) {
                    return helpResponse(400, "上传文件失败");
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return helpResponse(400, $e->getMessage());
        }
        return helpResponse(200, "ok", $res);
    }
}
