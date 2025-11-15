<?php
## BOOTSTRAP ##
ob_start();
ob_implicit_flush(0);

define('TINY_DEBUG',1);

if(!defined('TINY_AREA'))
	define('TINY_AREA','public');

ini_set('display_errors',(TINY_DEBUG)?'on':'off');
error_reporting(E_ALL);

if(version_compare(PHP_VERSION,'5.3','<'))
	@set_magic_quotes_runtime(0);

if(file_exists(realpath(dirname(__FILE__).'/../init.php')))
	require_once realpath(dirname(__FILE__).'/../init.php');
else{ # ok so we don't have access to `cd ..` and all installation resides within htdocs/public_html
	define('TINY_ROOT',str_replace('\\','/',realpath(dirname(__FILE__))).'/');
	define('WWW_ROOT',TINY_ROOT); # current = htdocs
	define('CFG_EXT','.php'); # secure configs
}

function ___tinyTidyError($msg){
	die("<!DOCTYPE html><html><head><title>tinyScript fatal error</title></head><body><h1>Fatal error</h1><p>$msg</p><address>tinyScript</address></body></html>");
}

## TINY APP CONFIG ##
define('APP_ROOT',TINY_ROOT.'app/');
define('HELPER_ROOT',TINY_ROOT.'tiny/');

if(!file_exists(APP_ROOT.'cfg'.CFG_EXT))
	___tinyTidyError('Application config not found!');

$cfg = parse_ini_file(APP_ROOT.'cfg'.CFG_EXT,true);
if(!isset($cfg['tiny']))
	___tinyTidyError('Invalid application config!');

if(!empty($cfg['tiny']['session']))
	session_start();
if(empty($cfg['tiny']['timezone']))
	$cfg['tiny']['timezone'] = 'UTC';
date_default_timezone_set($cfg['tiny']['timezone']);
if(empty($cfg['zf']['root']))
	$cfg['zf']['root'] = '';
define('ZF_ROOT',$cfg['zf']['root']);

## ZCT FRAMEWORK!! ##
require_once ZF_ROOT.'zwei.class.php'; # ZWEI is REQUIRED by tinyScript core
if(!class_exists('ZWEI'))
	___tinyTidyError('Missing required component: ZWEI!');
if(!empty($cfg['zf']['pagination']))
	require_once ZF_ROOT.'zpage.class.php';
if(!empty($cfg['zf']['db']))
	require_once ZF_ROOT.'zdbc.class.php';

## TINY CORE ##
require_once HELPER_ROOT.'tiny.class.php';
## TINY STUFF ON DEMAND ##
if(!empty($cfg['tiny']['plugins'])){
	$plugins = (strpos($cfg['tiny']['plugins'],',')) ? explode(',',$cfg['tiny']['plugins']) : array($cfg['tiny']['plugins']);
	foreach($plugins as $p)
		if($p and file_exists(HELPER_ROOT."plugins/tiny.$p.class.php"))
			require_once HELPER_ROOT."plugins/tiny.$p.class.php";
	unset($plugins,$p);
}

## EXAMPLE TINY APPLICATION ##
require_once APP_ROOT.'lib.class.php';
if('public' != TINY_AREA and file_exists(APP_ROOT.'app.'.TINY_AREA.'.class.php'))
	require_once APP_ROOT.'app.'.TINY_AREA.'.class.php';
else
	require_once APP_ROOT.'app.class.php';
TinyApplication::run();
