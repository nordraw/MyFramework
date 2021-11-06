<?php

namespace core\user\controller;

use core\base\controller\BaseController;

class IndexController extends BaseController
{
    protected $name;

    protected function inputData()
    {
        $name = 'Ivan';
        $content = $_SERVER['HTTP_REFERER'];
        $header = $this->render(TEMPLATE . 'header');
        $footer = $this->render(TEMPLATE . 'footer');

        return compact('header', 'content', 'footer');
    }

    protected function outputData()
    {
        $vars = func_get_arg(0);

        return $this->render(TEMPLATE . 'templater', $vars);
        //$this->page = $this->render(TEMPLATE . 'templater', $vars);
    }
}