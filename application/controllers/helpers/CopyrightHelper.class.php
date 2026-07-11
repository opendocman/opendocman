<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

/**
 * Copyright Helper Class - Automatic Copyright Year Management
 * Provides dynamic copyright year functionality for OpenDocMan
 */
class CopyrightHelper
{
    private static $cache = [];
    
    public static function getYearRange($startYear = 2000)
    {
        $cacheKey = "range_$startYear";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        $currentYear = (int)date('Y');
        $result = ($currentYear <= $startYear) ? (string)$startYear : $startYear . '-' . $currentYear;
        
        self::$cache[$cacheKey] = $result;
        return $result;
    }
    
    public static function getNotice($holder = 'Stephen Lawrence Jr.', $startYear = 2000)
    {
        return 'Copyright &copy; ' . self::getYearRange($startYear) . ' ' . $holder;
    }
    
    public static function getSourceNotice($holder = 'Stephen Lawrence', $startYear = 2000)
    {
        return 'Copyright (C) ' . self::getYearRange($startYear) . '. ' . $holder;
    }
    
    public static function getApiNotice($startYear = 2000)
    {
        return [
            'copyright' => 'Copyright ' . self::getYearRange($startYear) . ' Stephen Lawrence',
            'year' => (int)date('Y'),
            'range' => self::getYearRange($startYear)
        ];
    }
}

// Global functions
if (!function_exists('get_copyright_years')) {
    function get_copyright_years($startYear = 2000) {
        return CopyrightHelper::getYearRange($startYear);
    }
}

if (!function_exists('get_copyright_notice')) {
    function get_copyright_notice($holder = 'Stephen Lawrence Jr.', $startYear = 2000) {
        return CopyrightHelper::getNotice($holder, $startYear);
    }
}
