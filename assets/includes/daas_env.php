<?php
/**
 * Minimal .env loader for DaaS + GitHub sync.
 * Does not overwrite existing environment variables.
 */

if (!function_exists('daas_env_load')) {
    function daas_env_load($path = null)
    {
        static $loaded = false;
        if ($loaded) {
            return true;
        }

        if ($path === null) {
            $path = dirname(dirname(__DIR__)) . '/.env';
        }

        if (!is_readable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            // Strip optional surrounding quotes
            if (
                (strlen($value) >= 2)
                && (
                    ($value[0] === '"' && substr($value, -1) === '"')
                    || ($value[0] === "'" && substr($value, -1) === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
        }

        $loaded = true;
        return true;
    }
}

if (!function_exists('daas_env')) {
    function daas_env($key, $default = null)
    {
        daas_env_load();
        $val = getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return $val;
    }
}
