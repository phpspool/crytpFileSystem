<?php

/**
 * Help functions
 */
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
defined('ROOT_PATH') || define('ROOT_PATH', BASE_PATH . 'src' . DIRECTORY_SEPARATOR);
defined('CONFIG_PATH') || define('CONFIG_PATH', BASE_PATH . 'config' . DIRECTORY_SEPARATOR);
defined('STORAGE_PATH') || define('STORAGE_PATH', BASE_PATH . 'storage' . DIRECTORY_SEPARATOR);

//将pcntl信号处理改为异步,避免性能损耗
if (!pcntl_async_signals()) {
    pcntl_async_signals(true);
}
if (!function_exists('env')) {
    function env(string $key, string $defaultValue = null): string
    {
        return BASE_PATH;
    }
}
