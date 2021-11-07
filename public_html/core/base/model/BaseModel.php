<?php

namespace core\base\model;

use core\base\controller\Singleton;
use core\base\exceptions\DbException;

class BaseModel
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
     * <p>'where' => ['fio' => 'Smirnova', 'name' => 'Masha', 'surname' => 'Ivanova']</p>
     * <p>'operand' => ['=', '<>']</p>
     * <p>'condition' => ['AND']</p>
     * <p>'order' => ['fio', 'name']</p>
     * <p>'order_direction' => ['ASC', 'DESC']</p>
     * <p>'limit' => '1'</p>
     */
    final public function get($table, $set = [])
    {
        $fields = $this->createFields($table, $set);

        $where = $this->createWhere($table, $set);

        $join_arr = $this->createJoin($table, $set);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');

        $order = $this->createOrder($table, $set);

        $limit = $set['limit'] ? $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);
    }
}