<?php

namespace Hrmshandy\Finder\Helpers;

use finfo;
use Illuminate\Support\Str;
use Dflydev\ApacheMimeTypes\PhpRepository;

class File
{
    /**
     * @param int $sizeInBytes
     *
     * @return string
     */
    public static function getHumanReadableSize($sizeInBytes)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        if ($sizeInBytes == 0) {
            return '0 '.$units[1];
        }

        for ($i = 0; $sizeInBytes > 1024; ++$i) {
            $sizeInBytes /= 1024;
        }

        return round($sizeInBytes, 2).' '.$units[$i];
    }

    /**
     * Get the mime type of a file.
     *
     * @param $path
     *
     * @return string
     */
    public static function getMimetype($path)
    {
        $mimeDetect = new PhpRepository;

        return $mimeDetect->findType(
            self::getExtension($path)
        );
    }

    /**
     * Get the extension of file
     * @param  $path
     * @return string
     */
    public static function getExtension($path)
    {
        return Str::lower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Sanitize the given file name.
     *
     * @param $fileName
     *
     * @return string
     */
    public static function sanitizeFileName($fileName)
    {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
        "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
        ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($fileName)));
        $clean = preg_replace('/\s+/', "-", $clean);
        return (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean);
    }
}
