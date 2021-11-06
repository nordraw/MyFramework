<?php

namespace core\base\controller;

use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseController
{
    use \core\base\controller\BaseMethods;

    //Переменная для хранения страницы сайта
    protected $page;

    protected $errors;

    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    public function route()
    {
        $controller = str_replace('/', '\\', $this->controller);

        try {
            // Класс ReflectionMethod проверяет существование метода 'request'
            // в классе $controller, и, передав массив аргументов $args,
            // вызвывает метод 'request'
            $object = new \ReflectionMethod($controller, 'request');

            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod,
            ];

            $object->invoke(new $controller, $args);
        } catch (\ReflectionException $e) {
            throw new RouteException($e->getMessage());
        }
    }

    /**
     * @param $args
     */
    public function request($args)
    {
        $this->parameters = $args['parameters'];

        //Метод, формирующий параметры запроса для модели
        $inputData = $args['inputMethod'];
        //Метод, отвечающий за подключение вида
        $outputData = $args['outputMethod'];

        $data = $this->$inputData();

        //Выполним метод outputData только при условии, что он существует
        if (method_exists($this, $outputData)) {
            $page = $this->$outputData($data);
            if ($page) $this->page = $page;
        } //Иначе в page запишем результат работы метода inputData
        elseif ($data) {
            $this->page = $data;
        }

        //Логирование ошибок
        if ($this->errors) {
            $this->writeLog();
        }

        $this->getPage();
    }

    /**
     * Метод-шаблонизатор
     * @param string $path <p>Путь к шаблону</p>
     * @param array $parameters <p>Параметры, передаваемые в вид</p>
     */
    protected function render($path = '', $parameters = [])
    {
        extract($parameters);

        if (!$path) {

            $class = new \ReflectionClass($this);

            $space = str_replace('\\', '/', $class->getNamespaceName() . '\\');
            $routes = Settings::get('routes');

            if ($space === $routes['user']['path']) $template = TEMPLATE;
            else $template = ADMIN_TEMPLATE;

            $path = $template . explode('controller', strtolower((new \ReflectionClass($this))->getShortName()))[0];
        }

        //Открытие буфера обмена
        ob_start();

        //Подключение шаблона
        if (!@include_once $path . '.php') {
            throw new RouteException('Отсутствует шаблон - ' . $path);
        }

        //Вернёт значение, хранящееся в буфере обмена и очистит его
        return ob_get_clean();
    }

    /**
     * Показ вида пользователю
     */
    protected function getPage()
    {
        if (is_array($this->page)) {
            foreach ($this->page as $block) {
                echo $block;
            }
        } else {
            echo $this->page;
        }
        exit();
    }
}