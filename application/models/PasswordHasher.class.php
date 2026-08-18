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
 * Centralized password hashing using bcrypt via PHP's native password_*
 * functions. Also verifies legacy unsalted MD5 hashes so they can be lazily
 * upgraded to bcrypt on login.
 */
class PasswordHasher
{
    /**
     * @param string $plain
     * @return string bcrypt hash
     */
    public static function hash($plain)
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * @param string $plain
     * @param string $stored
     * @return bool
     */
    public static function verify($plain, $stored)
    {
        if (!is_string($plain) || !is_string($stored)) {
            return false;
        }
        if (self::isMd5Hash($stored)) {
            return hash_equals($stored, md5($plain));
        }
        return password_verify($plain, $stored);
    }

    /**
     * @param string $stored
     * @return bool true if the stored hash is legacy MD5 or an outdated bcrypt hash
     */
    public static function needsRehash($stored)
    {
        if (!is_string($stored)) {
            return false;
        }
        if (self::isMd5Hash($stored)) {
            return true;
        }
        return password_needs_rehash($stored, PASSWORD_DEFAULT);
    }

    /**
     * @param string $stored
     * @return bool
     */
    private static function isMd5Hash($stored)
    {
        return is_string($stored) && strlen($stored) === 32 && ctype_xdigit($stored);
    }
}
