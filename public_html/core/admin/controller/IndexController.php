<?php

namespace core\admin\controller;

use core\base\controller\BaseController;
use core\admin\model\Model;

class IndexController extends BaseController
{
    protected function inputData()
    {
        $db = Model::instance();

        $table = 'teachers';

        $query = "SELECT * FROM teachers WHERE name LIKE 'Masha%'";

        //Тестовые данные
//        $res = $db->get($table, [
//            'fields' => ['id', 'name'],
//            'where' => ['surname' => 'Ivanova', 'name' => 'Masha', 'surname' => 'Ivanovna'],
//            'operand' => ['IN', '<>'],
//            'condition' => ['AND'],
//            'order' => ['fio', 'name'],
//            'order_direction' => ['ASC', 'DESC'],
//            'limit' => '1',
//            'join' => [
//                'join_table1' => [
//                    //Таблица, к которой нужно присоединиться
//                    'table' => 'join_table1',
//                    'fields' => ['id as j_id', 'name as j_name'],
//                    'type' => 'left',
//                    'where' => ['name' => 'sasha'],
//                    'operand' => ['='],
//                    'condition' => ['OR'],
//                    'on' => [
//                        'table' => 'teachers',
//                        'fields' => ['id', 'name']
//                    ]
//                ],
//                'join_table2' => [
//                    'table' => 'join_table2',
//                    'fields' => ['id as j_id', 'name as j_name'],
//                    'type' => 'left',
//                    'where' => ['name' => 'sasha'],
//                    'operand' => ['='],
//                    'condition' => ['AND'],
//                    'on' => [
//                        'table' => 'teachers',
//                        'fields' => ['id', 'name']
//                    ]
//                ],
//            ],
//        ]);

        $files['gallery_img'] = ["red.jpg", "blue.jpg", "black.jpg"];
        $files['img'] = 'main_img.jpg';

        $res = $db->add($table, [
            'fields' => ['name' => 'Katya', 'content' => 'Hello'],
            'except' => ['name'],
            'files' => $files,
        ]);

    }
}