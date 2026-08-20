<?php

class MailToken
{
    public static function generate(): string
    {
        return 'odm-' . bin2hex(random_bytes(16));
    }
}