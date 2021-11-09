<?php

namespace core\base\model;

use core\admin\model\BaseModelMethods;
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

        /////////////////E X I T///////////////////
        exit(var_dump($this->query($query)));
        return $this->query($query);
    }
}