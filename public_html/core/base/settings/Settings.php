<?php

namespace core\base\settings;

class Settings
{
    static private $_instance;

    private function __clone()
    {
    }

    private $routes = [
        'admin' => [
            'name' => 'admin',
            'path' => 'core/admin/controllers/',
            //human readable URL
            'hrUrl' => false,
        ],
        'settings' => [
            'path' => 'core/base/settings/',
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
        ],
        'user' => [
            'path' => 'core/user/controllers/',
            'hrUrl' => true,
            'routes' => [],
        ],
        //Настройки по-умолчанию
        'default' => [
            'controller' => 'IndexController',
            //Имя метода контроллера для сбора данных по-умолчанию
            'inputMethod' => 'inputData',
            //Имя метода контроллера для вывода данных в пользовательский шаблон по-умолчанию
            'outputMethod' => 'outputData',
        ],
    ];

    private $templateArr = [
        'text' => ['name', 'phone', 'adress'],
        'textarea' => ['content', 'keywords'],
    ];

    static public function get($property)
    {
        return self::getInstance()->$property;
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
    }


    public function glueProperties($class)
    {
        $baseProperties = [];

        foreach ($this as $name => $item) {
            $property = $class::get($name);

            if (is_array($property) && is_array($item)) {
                $baseProperties[$name] = arrayMergeRecursive($this->$name, $property);
                continue;
            }

            if (!$property) {
                $baseProperties[$name] = $this->$name;
            }
        }

        return $baseProperties;
    }

    /**
     * Собственная функция слияния нескольких массивов
     * @return mixed|null
     */
    public function arrayMergeRecursive()
    {
        $arrays = func_get_args();

        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && is_array($base[$key])) {
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                } else {
                    if (is_int($key)) {
                        if (!in_array($value, $base)) array_push($base, $value);
                        continue;
                    }
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }
}