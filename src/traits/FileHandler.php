<?php

namespace DAI\Utils\Traits;

use Exception;
use Illuminate\Support\Facades\Storage;

trait FileHandler
{
    private function get_file_extension($file_name)
    {
        return substr(strrchr($file_name, '.'), 1);
    }

    private function disk() {
        return Storage::disk(env('ASSET_STORAGE', env('FILESYSTEM_DRIVER', 'public')));
    }

    public function saveFile($file, $filename = null, $dirname = null, $time = null, $index = 1)
    {
        ini_set('memory_limit', '-1');
        $time = $time ? $time : time();
        $original_name = $file->getClientOriginalName();
        $extension = $this->get_file_extension(($original_name));
        $filename = $filename ? $filename + "-" + $time + "-" + $index + "." + $extension : $time + "-" + $original_name;
        $uploaded_path = $this->disk()->putFileAs($dirname, $file, $filename);

        return $uploaded_path;
    }

    public function saveFiles($files, $filename = null, $dirname = null)
    {
        ini_set('memory_limit', '-1');
        $time = time();
        $uploaded_path = [];
        foreach ($files as $index => $file) {
            $uploaded_path[] = $this->saveFile($file, $filename, $dirname, $time, $index);
        }

        return $uploaded_path;
    }

    public function pathFile($file)
    {
        $disk = env('ASSET_STORAGE', env('FILESYSTEM_DRIVER', 'public'));
        $path = '';
        if (!is_null($file) && $file != '') {
            $file_exists = $this->disk()->exists($file);
            if ($file_exists) {
                if ($disk != 'public' && $disk != 'local') {
                    $path = $this->disk()->url($file);
                } else {
                    $path = $this->disk()->path($file);
                }
            }
        }

        return $path;
    }

    public function viewFile($file)
    {
        try {
            $path = $this->handlePathFile($file);
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $path = str_replace('\\', '/', $path);
                $headers = get_headers($path, 1);
                $type = $headers['Content-Type'];
                header("Content-type:$type");
                ob_clean();
                readfile($path);

                return;
            }
            $mime = mime_content_type($path);
            header("Content-type:$mime");
            ob_clean();
            readfile($path);
        } catch (Exception $e) {
            $path = public_path('images/logo.png');
            $mime = mime_content_type($path);
            header("Content-type:$mime");
            ob_clean();
            readfile($path);
        }
    }

    public function deleteFile($stored_file)
    {
        $this->disk()->delete($stored_file);
    }

    public function downloadFile($file) {
        return $this->disk()->download($file);
    }
}
