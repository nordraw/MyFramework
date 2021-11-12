<?php

namespace core\base\model;

use core\base\model\BaseModelMethods;
use core\base\controller\Singleton;
use core\base\exceptions\DbException;

class BaseModel extends BaseModelMethods
{
    use Singleton;

    protected $db;

    private function __construct()
    {
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        if ($this->db->connect_error) {
            throw new DbException('Ошибка подключения к базе данных: '
                . $this->db->connect_errno . ' ' . $this->db->connect_error);
        }

        //Установка кодировки соединения
        $this->db->query("SET NAMES UTF8");
    }

    /**
     * @param $query
     * @param string $crud = 'r' - SELECT/ 'c' - INSERT/ 'u' - UPDATE/ 'd' - DELETE
     * @param false $return_id
     * @return array|bool|int|string
     * @throws DbException
     */
    final public function query($query, $crud = 'r', $return_id = false)
    {

        //Объект, содержащий выборку из БД
        $result = $this->db->query($query);

        //affected_rows === -1 означает ошибку
        if ($this->db->affected_rows === -1) {
            throw new DbException('Ошибка в SQL запросе: '
                . $query . ' - ' . $this->db->errno . ' ' . $this->db->error);
        }

        switch ($crud) {
            // read
            case 'r':
                //Если что-то пришло из БД
                if ($result->num_rows) {
                    $res = [];
                    for ($i = 0; $i < $result->num_rows; $i++) {
                        $res[] = $result->fetch_assoc();
                    }
                    return $res;
                }

                return false;
                break;

            // create
            case 'c':
                if ($return_id) return $this->db->insert_id;

                return true;
                break;

            default:
                return true;
                break;
        }
    }

    /**
     * Получить данные из базы данных
     * @param $table <p>Таблица базы данных</p>
     * @param array $set
     * <p>'fields' => ['fio', 'name']</p>
     * <p>'where' => ['surname' => 'Smirnova', 'name' => 'Masha', 'patronymic' => 'Ivanova']</p>
     * <p>'operand' => ['=', '<>']</p>
     * <p>'condition' => ['AND']</p>
     * <p>'order' => ['fio', 'name']</p>
     * <p>'order_direction' => ['ASC', 'DESC']</p>
     * <p>'limit' => '1'</p>
     * <p>'join' => [
     *      'join_table1' => [
     *          Таблица, к которой нужно присоединиться
     *          'table' => 'join_table1',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => ['OR'],
     *          'group_condition' => 'AND',
     *          'on' => [
     *               'table' => 'teachers',
     *               'fields' => ['id', 'name']
     *          ],
     *      ],
     *          'join_table2' => [
     *          'table' => 'join_table2',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'left',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => ['AND'],
     *          'on' => ['id', 'name']
     *      ]
     * ]</p>
     * @return array|bool|int|string
     * @throws DbException
     */
    final public function get($table, $set = [])
    {
        $fields = $this->createFields($set, $table);

        $where = $this->createWhere($set, $table);

        if (!$where) $new_where = true;
        else $new_where = false;

        $join_arr = $this->createJoin($set, $table, $new_where);

        $order = $this->createOrder($set, $table);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');

        $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);
    }

    /**
     * @param $table - таблица для вставки данных
     * @param array $set - массив параметров:
     * <p>'fields' => [поле => значение]; - если не указан, то обрабатывается $_POST[поле => значение]
     *    разрешена передача MySql функции обычной строкой, например, NOW()</p>
     * <p>'files' => [поле => значение]; - можно подать массив вида [поле => [массив значений]]</p>
     * <p>'except' => ['исключение 1', 'исключение 2'] - исключает данные элементы массива из добавленных в запрос</p>
     * <p>'return_id' => true | false - возвращать или нет идентификатор вставленной записи</p>
     * @return mixed
     */
    final public function add($table, $set = [])
    {
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        //Если не пришли ни поля, ни данные для вставки, выполнение скрипта не имеет смысла
        if (!$set['fields'] && !$set['files']) return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;
        $set['return_id'] = $set['return_id'] ? true : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

        if ($insert_arr) {
            $query = "INSERT INTO $table({$insert_arr['fields']}) VALUES ({$insert_arr['values']});";

            return $this->query($query, 'c', $set['return_id']);
        }

        return false;
    }

    /**
     * @param $table
     * @param array $set - массив параметров:
     * <p>'fields' => [поле => значение];
     * <p>'files' => [поле => значение]; - можно подать массив вида [поле => [массив значений]]</p>
     * <p>'except' => ['исключение 1', 'исключение 2'] - исключает данные элементы массива из добавленных в запрос</p>
     * <p>'all_rows' => true|false - обновить все поля таблицы</p>
     * <p>'where' => [поле => значение];</p>
     * <p>'operand' => ['=', '<>']</p>
     * <p>'condition' => ['AND']</p>
     * @return array|bool|int|string
     * @throws DbException
     */
    final public function edit($table, $set = [])
    {
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        //Если не пришли ни поля, ни данные для вставки, выполнение скрипта не имеет смысла
        if (!$set['fields'] && !$set['files']) return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        // 'all_rows' отвечает за обновление всех полей таблицы
        if (!$set['all_rows']) {
            //Создание запроса WHERE
            if ($set['where']) {
                $where = $this->createWhere($set);
            } else {
                $columns = $this->showColumns($table);

                if (!$columns) return false;

                //Если первичный ключ есть
                if ($columns['id_row'] && $set['fields'][$columns['id_row']]) {
                    $where = 'WHERE' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];

                    //Поле id автоинкрементное, значит, оно больше не понадобится
                    unset($set['fields'][$columns['id_row']]);
                }
            }
        }

        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);

        $query = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');
    }

    /**
     * Показать информацию о полях в таблице
     * @param $table
     * @throws DbException
     */
    final public function showColumns($table)
    {
        $query = "SHOW COLUMNS FROM $table";
        $res = $this->query($query);

        $columns = [];

        if ($res) {
            foreach ($res as $row) {
                $columns[$row['Field']] = $row;
                //Если ключ является первичным
                if ($row['Key'] === 'PRI') $columns['id_row'] = $row['Field'];
            }
        }

        return $columns;
    }
}