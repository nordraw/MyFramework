<?php

namespace core\base\controller;

use Cassandra\Set;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use core\base\settings\ShopSettings;

class RouteController
{
    static private $_instance;

    protected $routes;

    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    private function __clone()
    {
    }

    static public function getInstance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        return self::$_instance = new self;
    }

    private function __construct()
    {
        $address_str = $_SERVER['REQUEST_URI'];

        //Если в конце URI есть '/' и это не корень сайта,
        //выполняем редирект на этот же адрес без '/'
        if (strrpos($address_str, '/') === strlen($address_str) - 1 && strrpos($address_str, '/') !== 0) {
            $this->redirect(rtrim($address_str, '/'), 301);
        }

        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));

        if ($path === PATH) {

            $this->routes = Settings::get('routes');

            if (!$this->routes) throw new RouteException('Сайт находится на техническом обслуживании');

            //Проверка, не пытается ли пользователь попасть в админ.панель
            if (strpos($address_str, $this->routes['admin']['alias']) === strlen(PATH)) {

            } else {
                $url = explode('/', substr($address_str, strlen(PATH)));

                $hrUrl = $this->routes['user']['hrUrl'];

                $this->controller = $this->routes['user']['path'];

                $route = 'user';
            }

            $this->createRoute($route, $url);

        } else {
            try {
                throw new \Exception('Не корректная директория сайта');
            } catch (\Exception $e) {
                exit($e->getMessage());
            }
        }
    }

    private function createRoute($var, $arr)
    {
        $route = [];

        if (!empty($arr[0])) {
            //Проверка, описан ли маршрут
            if ($this->routes[$var]['routes'][$arr[0]]) {
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

                $this->controller .= ucfirst($route[0] . 'Controller');
            } else {
                $this->controller .= ucfirst($arr[0] . 'Controller');
            }
        } else {
            $this->controller .= $this->routes['default']['controller'];
        }

        $this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];

    }
}