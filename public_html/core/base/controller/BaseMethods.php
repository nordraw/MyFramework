<?php

namespace core\base\controller;

trait BaseMethods
{

    /**
     * Метод очистки строковых данных
     * @param $str
     */
    protected function clearStr($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                $str[$key] = trim(strip_tags($item));
                return $str;
            }
        } else {
            return trim(strip_tags($str));
        }
    }

    /**
     * @param $num
     */
    protected function clearNum($num)
    {
        //Преобразование к числовому типу данных
        return $num * 1;
    }

    /**
     * Проверка, пришёл ли запрос методом POST
     * @return bool
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Проверка на AJAX
     * @return bool
     */
    protected function isAjax()
    {
        return is_set($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    protected function redirect($http = false, $code = false)
    {
        if ($code) {
            $codes = ['301' => 'HTTP/1.1 301 Move Permanently'];

            if ($codes[$code]) {
                header($codes[$code]);
            }
        }

        if ($http) $redirect = $http;
        else $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;

        header("Location: $redirect");
    }

    /**
     * Логирование сообщений
     * @param $message
     * @param string $file
     * @param string $event
     */
    protected function writeLog($message, $file = 'log.txt', $event = 'Fault')
    {
        $dateTime = new \DateTime();

        //Данные об ошибке
        $str = $event . ':' . $dateTime->format('d-m-Y G:i:s') . ' - ' . $message . '\r\n';

        file_put_contents('log/' . $file, $str, FILE_APPEND);
    }
}