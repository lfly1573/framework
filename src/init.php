<?php

/**
 * 框架载入初始页
 */

define('LFLY_VERSION', '1.2');
define('LFLY_START_TIME', microtime(true));
define('LFLY_START_MEMORY', memory_get_usage());
define('DS', DIRECTORY_SEPARATOR);
define('LFLY_PATH', __DIR__ . DS);

defined('EXT') or define('EXT', '.php');
defined('ROOT_PATH') or define('ROOT_PATH', realpath(LFLY_PATH . '..' . DS) . DS);
defined('APP_PATH') or define('APP_PATH', ROOT_PATH . 'app' . DS);
defined('CONFIG_PATH') or define('CONFIG_PATH', APP_PATH . 'config' . DS);
defined('ROUTER_PATH') or define('ROUTER_PATH', APP_PATH . 'router' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', ROOT_PATH . 'cache' . DS);
defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
defined('WEB_PATH') or define('WEB_PATH', ROOT_PATH . 'public' . DS);

spl_autoload_register(function ($className) {
    $namespaceAlias = [
        'lfly' => LFLY_PATH,
        'app' => APP_PATH,
    ];
    $original = '';
    $configFilename = CONFIG_PATH . 'autoload' . EXT;
    $namespaceConfig = is_file($configFilename) ? require_once $configFilename : array();
    if (isset($namespaceConfig[$className])) {
        $loadFile = $namespaceConfig[$className];
    } elseif (false === strpos($className, '\\') && !is_file(VENDOR_PATH . $className . EXT)) {
        $loadFile = LFLY_PATH . 'facade' . DS . $className . EXT;
        $original = '\\lfly\\facade\\' . $className;
    } else {
        $fileArray = explode('\\', $className);
        if (isset($namespaceAlias[$fileArray[0]])) {
            $loadFile = $namespaceAlias[array_shift($fileArray)] . implode(DS, $fileArray) . EXT;
        } else {
            $curNamespace = '';
            $isHaveAlias = false;
            foreach ($fileArray as $key => $value) {
                if ($key > 0) {
                    $curNamespace .= $isHaveAlias ? DS : '\\';
                }
                $curNamespace .= $value;
                if (!$isHaveAlias && isset($namespaceConfig[$curNamespace])) {
                    $isHaveAlias = true;
                    $curNamespace = $namespaceConfig[$curNamespace];
                }
            }
            if ($isHaveAlias) {
                $loadFile = $curNamespace . EXT;
            } else {
                $loadFile = VENDOR_PATH . implode(DS, $fileArray) . EXT;
            }
        }
    }

    if (is_file($loadFile)) {
        include_once $loadFile;
        if (!empty($original)) {
            class_alias($original, $className);
        }
        return true;
    }
    return false;
}, false, true);
