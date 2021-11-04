<?php

// Главные настройки фреймворка

use core\base\exceptions\RouteException;

defined('VG_ACCESS') or die('Access denied');

//Шаблоны пользовательской части сайта
const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/view';

const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = '';
//Время бездействия пользователя
const COOKIE_TIME = 60;
//Кол-во попыток ввода пароля
const BLOCK_TIME = 3;

//Постраничная навигация
const QTY = 8;
const QTY_LINKS = 3;

const ADMIN_CSS_JS = [
    'styles' => [],
    'scripts' => []
];

function autoloadMainClasses($class_name)
{
    $class_name = str_replace('\\', '/', $class_name);

    if (!@include_once $class_name . '.php') {
        throw new RouteException('Не верное имя файла для подключения - ' . $class_name);
    }
}

spl_autoload_register('autoloadMainClasses');