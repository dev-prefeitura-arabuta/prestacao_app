<?php

if (! function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        return url($path);
    }
}

if (! function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return asset($path);
    }
}

if (! function_exists('esc')) {
    function esc(mixed $data, string $context = 'html'): string
    {
        $str = (string) ($data ?? '');
        if ($context === 'js') {
            return addslashes($str);
        }

        return e($str);
    }
}

if (! function_exists('uri_string')) {
    function uri_string(): string
    {
        $p = request()->path();
        return $p === '/' ? '' : $p;
    }
}

if (! function_exists('csrf_hash')) {
    function csrf_hash(): string
    {
        return (string) csrf_token();
    }
}
