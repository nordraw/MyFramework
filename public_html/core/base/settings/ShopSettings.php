<?php

namespace core\base\settings;

use core\base\settings\Settings;

class ShopSettings
{
    private $templateArr = [
        'text' => ['name', 'phone', 'adress', 'price', 'short'],
        'textarea' => ['content', 'keywords', 'goods_content'],
    ];

    static private $_instance;
    private $baseSettings;

    private function __clone()
    {
    }

    static public function get($property)
    {
        return self::getInstance()->$property;
    }

    static public function getInstance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        self::$_instance = new self;
        self::$_instance->baseSettings = Settings::getInstance();
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