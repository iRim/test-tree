<?php

namespace App\Classes;

use App\Classes\Abstract\AbstractDb;
use mysqli_result;

class DB extends AbstractDb
{
    const host = 'localhost';
    const user = 'root';
    const pass = '';
    const db_name = 'test';
    const table_name = 'branch';

    public function __construct()
    {
        parent::__construct(
            self::host,
            self::user,
            self::pass,
            self::db_name
        );

        $this->createTable();
    }

    /**
     * Створення таблиці якщо не існує
     *
     * @return mysqli_result
     */
    private function createTable(): mysqli_result|bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . self::table_name . "` (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                parent_id INT(6) DEFAULT 0,
                title VARCHAR(50) NOT NULL
            )";
        return $this->query($sql);
    }

    /**
     * Додавання запису в БД
     *
     * @param string $title
     * @param integer $parent_id
     * @return void
     */
    public function create(
        string $title,
        int $parent_id = 0
    ): void {
        $response = $this->request("INSERT INTO `" . self::table_name . "` (title, parent_id) VALUES(?, ?)");
        $response->bind_param('si', $title, $parent_id);
        $response->execute();
        $response->close();
    }

    /**
     * Отримання записів з БД
     *
     * @return array
     */
    public function read(): mysqli_result
    {
        return $this->query("SELECT * FROM `" . self::table_name . "` ORDER BY parent_id, id");
    }

    /**
     * Оновлення запису в БД
     *
     * @param integer $id
     * @param string $title
     * @return void
     */
    public function update(
        int $id,
        string $title
    ): void {
        $response = self::request("UPDATE `" . self::table_name . "` SET title=? WHERE id=?");
        $response->bind_param('si', $title, $id);
        $response->execute();
        $response->close();
    }

    /**
     * Видалення записів з БД
     *
     * @param integer $id
     * @return void
     */
    public function delete(
        int $id
    ): void {
        $row = self::query("SELECT * FROM `" . self::table_name . "` WHERE id=" . $id);
        // перевіряємо чи це корінь, якщо так тоді очищаємо всю таблицю
        if ($row->fetch_assoc()['parent_id'] == 0) {
            self::query("TRUNCATE TABLE `" . self::table_name . "`");
        }
        // якщо не корінь - робимо вибірку та видаляємо тільки потрібні дані
        else {
            self::query('WITH RECURSIVE tree (id, parent_id) AS (
                SELECT id, parent_id
                FROM ' . self::table_name . '
                WHERE id=' . $id . '
                UNION ALL
                SELECT t.id, t.parent_id
                FROM ' . self::table_name . ' t
                JOIN tree d ON t.parent_id = d.id
            )
            DELETE FROM branch WHERE id IN (SELECT id FROM tree);');
        }
    }
}
