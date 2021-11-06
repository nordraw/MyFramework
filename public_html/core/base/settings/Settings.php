<?php

namespace core\base\settings;

use core\base\controller\Singleton;

class Settings
{
    use Singleton;

    private $routes = [
        'admin' => [
            'alias' => 'admin',
            'path' => 'core/admin/controller/',
            //human readable URL
            'hrUrl' => false,
        ],
        'settings' => [
            'path' => 'core/base/settings/',
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => false,
        ],
        'user' => [
            'path' => 'core/user/controller/',
            'hrUrl' => true,
            'routes' => [
                'site' => 'index/inputData',
            ],
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

    /**
     * Получить свойство класса
     * @param $property
     * @return mixed
     */
    static public function get($property)
    {
        return self::instance()->$property;
    }

    /**
     * Функция для склейки свойств базового класса настроек с свойствами пользовательского класса
     * @param $class
     * @return array
     */
    public function glueProperties($class)
    {
        $baseProperties = [];

        foreach ($this as $name => $item) {
            $property = $class::get($name);

            if (is_array($property) && is_array($item)) {
                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
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