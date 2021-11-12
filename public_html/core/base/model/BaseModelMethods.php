<?php

namespace core\base\model;

abstract class BaseModelMethods
{
    protected $sqlFunc = ['NOW()'];

    /**
     * @param false $table
     * @param $set
     * @return string
     */
    protected function createFields($set, $table = false)
    {
        //Проверяем, пришла ли таблица для формирования запроса вида: "SELECT * FROM table.value....."
        $table = $table ? $table . '.' : '';

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields']))
            ? $set['fields'] : ['*'];

        $fields = '';

        foreach ($set['fields'] as $field) {
            $fields .= $table . $field . ',';
        }

        return $fields;
    }

    /**
     * @param false $table
     * @param $set
     * @return string
     */
    protected function createOrder($set, $table = false)
    {
        $table = $table ? $table . '.' : '';

        $order_by = '';

        if (is_array($set['order']) && !empty($set['order'])) {
            $set['order_direction'] = (is_array($set['order_direction']) && !empty($set['order_direction']))
                ? $set['order_direction'] : ['ASC'];

            $order_by = 'ORDER BY ';

            $direct_count = 0;
            foreach ($set['order'] as $order) {
                if ($set['order_direction'][$direct_count]) {
                    $order_direction = strtoupper($set['order_direction'][$direct_count]);
                    $direct_count++;
                } else {
                    $order_direction = strtoupper($set['order_direction'][$direct_count - 1]);
                }

                //Проверка в случае с запросом с UNION
                if (is_int($order)) $order_by .= $order . ' ' . $order_direction . ',';
                else $order_by .= $table . $order . ' ' . $order_direction . ',';
            }

            $order_by = rtrim($order_by, ',');
        }

        return $order_by;
    }

    /**
     * @param false $table
     * @param $set
     * @param string $instruction
     * @return false|string|void
     *
     * <p> Пример обрабатываемых данных:</p>
     * <p>'where' => ['surname' => 'Smirnova, Ivanova', 'name' => 'Masha', 'patronymic' => 'Ivanovna']</p>
     * <p>'operand' => ['IN', 'LIKE%', '<>']</p>
     * <p>'condition' => ['AND']</p>
     */
    protected function createWhere($set, $table = false, $instruction = 'WHERE ')
    {
        $table = $table ? $table . '.' : '';

        $where = '';

        if (is_array($set['where']) && !empty($set['where'])) {
            //Операнд по-умолчанию "="
            $set['operand'] = (is_array($set['operand']) && !empty($set['operand']))
                ? $set['operand'] : ['='];

            //Условие по-умолчанию "AND"
            $set['condition'] = (is_array($set['condition']) && !empty($set['condition']))
                ? $set['condition'] : ['AND'];

            $where = $instruction;

            $operand_count = 0;
            $condition_count = 0;

            foreach ($set['where'] as $key => $item) {
                $where .= ' ';

                //Разбираем, какой операнд пришёл
                if ($set['operand'][$operand_count]) {
                    $operand = $set['operand'][$operand_count];
                    $operand_count++;
                } else {
                    $operand = $set['operand'][$operand_count - 1];
                }

                //Разбираем условие (AND, OR)
                if ($set['condition'][$condition_count]) {
                    $condition = $set['condition'][$condition_count];
                    $condition_count++;
                } else {
                    $condition = $set['condition'][$condition_count - 1];
                }

                if ($operand === 'IN' || $operand === 'NOT IN') {
                    //Если пришёл вложенный запрос вида: (SELECT * .....)
                    if (is_string($item) && strpos($item, 'SELECT') === 0) {
                        $in_str = $item;
                    } else {
                        if (is_array($item)) $temp_item = $item;
                        else $temp_item = explode(',', $item);

                        $in_str = '';
                        foreach ($temp_item as $value) {
                            $in_str .= "'" . addslashes(trim($value)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand . ' (' . rtrim($in_str, ',') . ') ' . $condition;

                }
                //Знаки % приходят в ячейку operand. Например, "%LIKE%".
                //Их необходимо переместить в $item.
                //Таким образом формируем запрос вида: 'SELECT * FROM table WHERE name LIKE "%Ivan%"'
                elseif (strpos($operand, 'LIKE') !== false) {
                    $like_template = explode('%', $operand);

                    foreach ($like_template as $lt_key => $lt) {
                        if (!$lt) {
                            if (!$lt_key) {
                                $item = '%' . $item;
                            } else {
                                $item .= '%';
                            }
                        }
                    }

                    $where .= $table . $key . ' LIKE ' . "'" . addslashes($item) . "' $condition";
                } //Если приходят обычные операторы, например "=", "<>"
                else {
                    //Если в $item лежит вложенный запрос
                    if (strpos($item, 'SELECT') === 0) {
                        $where .= $table . $key . $operand . ' (' . $item . ") $condition";
                    } else {
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";
                    }
                }
            }

            //Обрезаем последнее условие
            $where = substr($where, 0, strrpos($where, $condition));

            return $where;
        }
    }

    /**
     * @param $table
     * @param $set
     * @param false $new_where
     *
     * 'join' => [
     *      'join_table1' => [
     *          'table' => 'join_table1',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => ['OR'],
     *          'on' => [
     *               'table' => 'teachers',
     *               'fields' => ['id', 'name']
     *          ]
     *      ],
     *      'join_table2' => [.......],
     */
    protected function createJoin($set, $table, $new_where = false)
    {
        $fields = '';
        $join = '';
        $where = '';

        if ($set['join']) {
            $join_table = $table;

            foreach ($set['join'] as $key => $item) {
                //Если название таблицы не указано
                if (is_int($key)) {
                    if (!$item['table']) {
                        continue;
                    } else {
                        $key = $item['table'];
                    }
                }

                if ($join) $join .= ' ';

                //Если не существует условия объединения таблиц,
                //выполнение каких-либо действий не имеет смысла
                if ($item['on']) {
                    $join_fields = [];

                    switch (2) {
                        case count($item['on']['fields']):
                            $join_fields = $item['on']['fields'];
                            break;
                        case count($item['on']):
                            $join_fields = $item['on'];
                            break;
                        default:
                            continue 2;
                    }

                    //Если тип JOIN`а не пришёл, по-умолчанию используется LEFT JOIN
                    if (!$item['type']) $join .= 'LEFT JOIN ';
                    else $join .= trim(strtoupper($item['type'])) . ' JOIN ';

                    $join .= $key . ' ON ';

                    if ($item['on']['table']) $join .= $item['on']['table'];
                    else $join .= $join_table;

                    $join .= '.' . $join_fields[0] . '=' . $key . '.' . $join_fields[1];

                    //Присваиваем join_table значение текущей таблицы,
                    //чтобы следующая итерация цикла могла работать с предыдущей таблицей
                    $join_table = $key;

                    if ($new_where) {
                        if ($item['where']) {
                            $new_where = false;
                        }

                        $group_condition = 'WHERE';
                    } else {
                        $group_condition = $item['group_condition'] ? strtoupper($item['group_condition']) : 'AND';
                    }

                    $fields .= $this->createFields($item, $key);
                    $where .= $this->createWhere($item, $key, $group_condition);
                }
            }
        }
        return compact('fields', 'join', 'where');
    }

    /**
     * @param $fields
     * @param $files
     * @param $except
     * @return array
     */
    protected function createInsert($fields, $files, $except)
    {
        $insert_arr = [];

        if ($fields) {
            foreach ($fields as $row => $value) {
                if ($except && in_array($row, $except)) continue;

                $insert_arr['fields'] .= $row . ',';

                //Проверка на наличие SQL функции
                if (in_array($value, $this->sqlFunc)) {
                    $insert_arr['values'] .= $value . ',';
                } else {
                    $insert_arr['values'] .= "'" . addslashes($value) . "',";
                }
            }
        }

        if ($files) {
            foreach ($files as $row => $file) {
                $insert_arr['fields'] .= $row . ',';

                if (is_array($file)) $insert_arr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                else $insert_arr['values'] .= "'" . addslashes($file) . "',";
            }
        }

        //Обрезаем лишние запятые в конце
        foreach ($insert_arr as $key => $arr) {
            $insert_arr[$key] = rtrim($arr, ',');
        }

        return $insert_arr;
    }

    /**
     * @param $fields
     * @param $files
     * @param $except
     * @return string
     */
    protected function createUpdate($fields, $files, $except)
    {
        $update = '';

        if ($fields) {
            foreach ($fields as $row => $value) {
                if ($except && in_array($row, $except)) continue;

                $update .= $row . '=';

                if (in_array($value, $this->sqlFunc)) {
                    $update .= $value . ',';
                } else {
                    $update .= "'" . addslashes($value) . "',";
                }
            }
        }

        if ($files) {
            foreach ($files as $row => $file) {
                $update .= $row . '=';

                if (is_array($file)) $update .= "'" . addslashes(json_encode($file)) . "',";
                else $update .= "'" . addslashes($file) . "',";
            }
        }

        return rtrim($update, ',');
    }
}