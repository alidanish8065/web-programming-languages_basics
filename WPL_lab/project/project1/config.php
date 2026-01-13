<?php
// config.php – configuration + helpers ONLY

define('BASE_PATH', __DIR__); // __DIR__ is already project root

define('BASE_URL', '/project1');

// URL helper
function url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// File include helper
function include_file(string $path = ''): string {
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
