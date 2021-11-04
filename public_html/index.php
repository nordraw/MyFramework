<?php

use core\base\exceptions\RouteException;
use core\base\controller\RouteController;

// Константа безопасности
// (Если константа не определена в файле, доступ к нему будет заблокирован)
define('VG_ACCESS', true);

header('Content-Type:text/html;charset:utf-8');

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';

try {
    //RouteController::getInstance()->route();
    RouteController::getInstance();
} catch (RouteException $e) {
    exit($e->getMessage());
}