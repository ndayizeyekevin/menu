<?php

function loadEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if ($name === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            continue;
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($name, $_ENV) && getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function env(string $name, mixed $default = null): mixed
{
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }
    if (array_key_exists($name, $_ENV)) {
        return $_ENV[$name];
    }
    return $default;
}

loadEnv(__DIR__ . '/../.env');
