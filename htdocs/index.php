<?php
## BOOTSTRAP ##
ob_start();
ob_implicit_flush(0);

define('TINY_DEBUG', 1);

if (!defined('TINY_AREA'))
    define('TINY_AREA', 'public');

ini_set('display_errors', (TINY_DEBUG) ? 'on' : 'off');
error_reporting(E_ALL);

if (version_compare(PHP_VERSION, '5.3', '<'))
    @set_magic_quotes_runtime(0);

## INIT & ROOTS ##
if (file_exists(realpath(dirname(__FILE__) . '/../init.php'))) {

    # Load original init.php (may define wrong paths, we fix below)
    require_once realpath(dirname(__FILE__) . '/../init.php');

    # --- FORCE FIX FOR TINY_ROOT on Render / Docker ---
    if (!defined('TINY_ROOT')) {
        define('TINY_ROOT', dirname(__FILE__) . '/../');
    }
    if (!defined('WWW_ROOT')) {
        define('WWW_ROOT', TINY_ROOT);
    }
    if (!defined('CFG_EXT')) {
        define('CFG_EXT', '.php');
    }
    # -----------------------------------------------------

} else { 
    # Fallback: installation resides only within htdocs
    define('TINY_ROOT', str_replace('\\', '/', realpath(dirname(__FILE__))) . '/');
    define('WWW_ROOT', TINY_ROOT); 
    define('CFG_EXT', '.php');
}

function ___tinyTidyError($msg){
    die("<!DOCTYPE html><html><head><title>tinyScript fatal error</title></head>
    <body><h1>Fatal error</h1><p>$msg</p><address>tinyScript</address></body></html>");
}

## TINY APP CONFIG ##
define('APP_ROOT', TINY_ROOT . 'app/');
define('HELPER_ROOT', TINY_ROOT . 'tiny/');

if (!file_exists(APP_ROOT . 'cfg' . CFG_EXT))
    ___tinyTidyError('Application config not found!');

$cfg = parse_ini_file(APP_ROOT . 'cfg' . CFG_EXT, true);
if (!isset($cfg['tiny']))
    ___tinyTidyError('Invalid application config!');

if (!empty($cfg['tiny']['session']))
    session_start();

if (empty($cfg['tiny']['timezone']))
    $cfg['tiny']['timezone'] = 'UTC';
date_default_timezone_set($cfg['tiny']['timezone']);

## FIX ZF_ROOT ALWAYS ##
if (empty($cfg['zf']['root'])) {
    $cfg['zf']['root'] = dirname(__FILE__) . '/../zf/';
}
define('ZF_ROOT', rtrim($cfg['zf']['root'], '/') . '/');

## ZCT FRAMEWORK!! ##
require_once ZF_ROOT . 'zwei.class.php'; # ZWEI is REQUIRED
if (!class_exists('ZWEI'))
    ___tinyTidyError('Missing required component: ZWEI!');

if (!empty($cfg['zf']['pagination']))
    require_once ZF_ROOT . 'zpage.class.php';

if (!empty($cfg['zf']['db']))
    require_once ZF_ROOT . 'zdbc.class.php';

## TINY CORE ##
require_once HELPER_ROOT . 'tiny.class.php';

## TINY PLUGINS ##
if (!empty($cfg['tiny']['plugins'])) {
    $plugins = (strpos($cfg['tiny']['plugins'], ',')) 
        ? explode(',', $cfg['tiny']['plugins']) 
        : array($cfg['tiny']['plugins']);

    foreach ($plugins as $p)
        if ($p && file_exists(HELPER_ROOT . "plugins/tiny.$p.class.php"))
            require_once HELPER_ROOT . "plugins/tiny.$p.class.php";

    unset($plugins, $p);
}

## APPLICATION ##
require_once APP_ROOT . 'lib.class.php';

if ('public' != TINY_AREA && file_exists(APP_ROOT . 'app.' . TINY_AREA . '.class.php'))
    require_once APP_ROOT . 'app.' . TINY_AREA . '.class.php';
else
    require_once APP_ROOT . 'app.class.php';

TinyApplication::run();
