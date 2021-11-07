<?php

// Константа безопасности
// (Если константа не определена в файле, доступ к нему будет заблокирован)
define('VG_ACCESS', true);

use core\base\exceptions\RouteException;
use core\base\exceptions\DbException;
use core\base\controller\RouteController;

header('Content-Type:text/html;charset:utf-8');
session_start();

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';

try {
    RouteController::instance()->route();
} catch (RouteException $e) {
    exit($e->getMessage());
} catch (DbException $e) {
    exit($e->getMessage());
}