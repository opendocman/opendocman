<?php

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class File
{
    private static $finfoDetector;
    private static $extDetector;

    private static function getFinfoDetector()
    {
        if (self::$finfoDetector === null) {
            self::$finfoDetector = new FinfoMimeTypeDetector();
        }
        return self::$finfoDetector;
    }

    private static function getExtDetector()
    {
        if (self::$extDetector === null) {
            self::$extDetector = new ExtensionMimeTypeDetector();
        }
        return self::$extDetector;
    }

    public static function mime($filename, $realname)
    {
        $filename = realpath($filename);
        $extension = strtolower(pathinfo($realname, PATHINFO_EXTENSION));

        if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension)) {
            $file = getimagesize($filename);
            if (isset($file['mime'])) {
                return $file['mime'];
            }
        }

        $mimeType = self::getFinfoDetector()->detectMimeTypeFromFile($filename);

        if ($mimeType !== null) {
            return $mimeType;
        }

        if (!empty($extension)) {
            return self::mime_by_ext($extension);
        }

        return false;
    }

    public static function mime_by_ext($extension)
    {
        $detector = self::getExtDetector();
        $mimeType = $detector->detectMimeTypeFromPath('file.' . $extension);
        return $mimeType !== null ? $mimeType : false;
    }

    public static function mimes_by_ext($extension)
    {
        $mime = self::mime_by_ext($extension);
        return $mime !== false ? [$mime] : [];
    }

    public static function exts_by_mime($type)
    {
        $extensions = self::getExtDetector()->lookupAllExtensions($type);
        return !empty($extensions) ? $extensions : false;
    }

    public static function ext_by_mime($type)
    {
        $extensions = self::exts_by_mime($type);
        return is_array($extensions) ? current($extensions) : false;
    }

}
