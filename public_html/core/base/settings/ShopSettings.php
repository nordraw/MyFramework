<?php

namespace core\base\settings;

use core\base\controller\Singleton;
use core\base\settings\Settings;

class ShopSettings
{
    use Singleton;

    private $routes = [
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => 'controller',
            'routes' => [
                'product' => 'goods',
            ],
        ],
    ];

    private $templateArr = [
        'text' => ['name', 'phone', 'adress', 'price', 'short'],
        'textarea' => ['content', 'keywords', 'goods_content'],
    ];

    private $baseSettings;

    static public function get($property)
    {
        return self::getInstance()->$property;
    }

    static private function getInstance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        self::instance()->baseSettings = Settings::instance();
        $baseProperties = self::$_instance->baseSettings->glueProperties(get_class());
        self::$_instance->setProperty($baseProperties);

        return self::$_instance;
    }

    private function __construct()
    {
    }

    protected function setProperty($properties)
    {
        if ($properties) {
            foreach ($properties as $name => $property) {
                $this->name = $property;
            }
        }
    }
}