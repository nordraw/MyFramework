<?php

namespace core\base\controller;

trait Singleton
{
    static private $_instance;

    private function __clone()
    {
    }

    private function __construct()
    {
    }

    static public function instance()
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        return self::$_instance = new self;
    }
}