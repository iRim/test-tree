<?php

class Conn
{
    const server = 'localhost';
    const user = 'root';
    const pass = '';
    const db_name = 'test';
    const table_name = 'branch';

    static private $conn = null;

    /**
     * Connect to MySQL
     */
    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        self::$conn = new mysqli(self::server, self::user, self::pass);
        if (self::$conn->connect_error) {
            self::error("Помилка зєднання: " . self::$conn->connect_error);
        }

        self::createDB();
        self::$conn->select_db(self::db_name);
        self::$conn->set_charset('utf8');
        self::createTable();
    }

    /**
     * Створення БД, якщо не існує
     *
     * @return mysqli_result
     */
    static private function createDB(): mysqli_result|bool
    {
        return self::query('CREATE DATABASE IF NOT EXISTS ' . self::db_name);
    }

    /**
     * Створення таблиці якщо не існує
     *
     * @return mysqli_result
     */
    static private function createTable(): mysqli_result|bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . self::table_name . "` (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                parent_id INT(6) DEFAULT 0,
                title VARCHAR(50) NOT NULL
            )";
        return self::query($sql);
    }

    /**
     * Помилки
     *
     * @param string $message
     * @return void
     */
    static private function error(
        string $message
    ): void {
        new Exception("SQL Error! <br>" . $message);
        die;
    }

    /**
     * Загальна функція для запитів в MySQL
     *
     * @param string $query
     * @return mysqli_stmt
     */
    static private function request(
        string $query
    ): mysqli_stmt {
        $response = self::$conn->prepare($query);
        if (!$response) {
            self::error(self::$conn->error);
        }
        return $response;
    }

    /**
     * Запит в MySQL для отримання даних
     *
     * @param string $query
     * @return mysqli_result
     */
    static private function query(
        string $query
    ): mysqli_result|bool {
        $response = self::$conn->query($query);
        if (!$response) {
            self::error(self::$conn->error);
        }
        return $response;
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
        $response = self::request("INSERT INTO `" . self::table_name . "` (title, parent_id) VALUES(?, ?)");
        $response->bind_param('si', $title, $parent_id);
        $response->execute();
        $response->close();
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

    /**
     * Отримання записів з БД
     *
     * @return array
     */
    public function get(): array
    {
        $items = [];
        foreach (self::query("SELECT * FROM `" . self::table_name . "` ORDER BY parent_id, id") as $item) {
            $items[] = $item;
        }
        return $items;
    }
}

if (!empty($_GET['response'])) {
    $conn = new Conn;
    $post = json_decode(file_get_contents("php://input"), true);

    if ($_GET['response'] == 'create') {
        if (!empty($post['title'])) {
            $conn->create($post['title'], !empty($post['parent_id']) ? $post['parent_id'] : 0);
        }
    }
    if ($_GET['response'] == 'update') {
        if (!empty($post['id']) and !empty($post['title'])) {
            $conn->update($post['id'], $post['title']);
        }
    }
    if ($_GET['response'] == 'delete') {
        if (!empty($post['id'])) {
            $conn->delete($post['id']);
        }
    }

    echo json_encode($conn->get());
    die;
}

new Conn;

?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.122.0">
    <link rel="icon" href="https://getbootstrap.com/docs/5.3/assets/img/favicons/favicon.ico">
    <title>Tree</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        li {
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }

        li>ul {
            display: none;
        }

        li>div {
            display: flex;
            flex-direction: row;
            gap: 1rem;
            height: 2rem
        }

        li>div>i,
        li>div>span {
            display: flex;
            align-self: center;
            cursor: pointer;
        }

        li>div>.btn-group {
            display: none;
        }

        li>div:hover {
            background-color: #eee;
        }

        li>div:hover>.btn-group {
            display: block;
        }

        .modal-footer {
            justify-content: space-between;
        }

        .timer {
            color: #cc0000;
            font-weight: bold;
        }
    </style>
</head>

<body onload="readItems()">
    <main>
        <div class="container py-4">
            <header class="pb-3 mb-4 border-bottom">
                <a href="/" class="d-flex align-items-center text-body-emphasis text-decoration-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="32" class="me-2" viewBox="0 0 118 94" role="img">
                        <title>Bootstrap</title>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M24.509 0c-6.733 0-11.715 5.893-11.492 12.284.214 6.14-.064 14.092-2.066 20.577C8.943 39.365 5.547 43.485 0 44.014v5.972c5.547.529 8.943 4.649 10.951 11.153 2.002 6.485 2.28 14.437 2.066 20.577C12.794 88.106 17.776 94 24.51 94H93.5c6.733 0 11.714-5.893 11.491-12.284-.214-6.14.064-14.092 2.066-20.577 2.009-6.504 5.396-10.624 10.943-11.153v-5.972c-5.547-.529-8.934-4.649-10.943-11.153-2.002-6.484-2.28-14.437-2.066-20.577C105.214 5.894 100.233 0 93.5 0H24.508zM80 57.863C80 66.663 73.436 72 62.543 72H44a2 2 0 01-2-2V24a2 2 0 012-2h18.437c9.083 0 15.044 4.92 15.044 12.474 0 5.302-4.01 10.049-9.119 10.88v.277C75.317 46.394 80 51.21 80 57.863zM60.521 28.34H49.948v14.934h8.905c6.884 0 10.68-2.772 10.68-7.727 0-4.643-3.264-7.207-9.012-7.207zM49.948 49.2v16.458H60.91c7.167 0 10.964-2.876 10.964-8.281 0-5.406-3.903-8.178-11.425-8.178H49.948z" fill="currentColor"></path>
                    </svg>
                    <span class="fs-4">Tree</span>
                </a>
            </header>

            <div class="p-5 mb-0 bg-body-tertiary rounded-3" id="app"></div>

            <footer class="pt-3 mt-4 text-body-secondary border-top">
                &copy; 2024
            </footer>
        </div>

        <div class="modal fade" id="add" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Add item</h1>
                        <button type="button" class="btn-close" onclick="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="col-form-label">Title</label>
                            <input type="text" class="form-control" name="title">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="timer"></div>
                        <div>
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                            <button type="button" class="btn btn-primary" onclick="addSubmit()">Add item</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="remove" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Delete confirmation</h1>
                        <button type="button" class="btn-close" onclick="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        This is very dangerous, you shouldn't do it! Are tou really really sure?
                    </div>
                    <div class="modal-footer">
                        <div class="timer"></div>
                        <div>
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">No</button>
                            <button type="button" class="btn btn-primary" onclick="deleteRequest()">Yes I am</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        var app = $('#app');
        var modalAdd = $('#add');
        var modalRemove = $('#remove');
        var modalItemId;
        var modalItemUpdate = false;

        const START_TIME = 30;
        var timer;

        function loading() {
            app.empty().append('<i class="fa-solid fa-spinner fa-spin"></i>');
        }

        function btnRoot(message = 'Create root') {
            app.empty().append('<span class="btn btn-primary" onclick="createRoot()">' + message + '</span>');
        }

        function request(
            method = 'read',
            data = {}
        ) {
            this.loading();

            fetch('./index.php?response=' + method, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        app
                            .empty()
                            .append(
                                this.template(
                                    this.generateTreeFromItems(
                                        data
                                    )
                                )
                            );
                    } else {
                        this.btnRoot()
                    }
                    this.closeModal();
                });
        }

        function createRequest(data) {
            this.request('create', data)
        }

        function updateRequest(data) {
            this.request('update', data)
        }

        function deleteRequest() {
            this.request('delete', {
                id: modalItemId
            })
        }

        function readItems() {
            this.request()
        }

        function generateTreeFromItems(params) {
            let values = Object.values(params);
            let map = {};

            values
                .forEach(function(row) {
                    map[row.id] = {
                        title: row.title,
                        id: row.id,
                        parent: row.parent_id,
                        children: []
                    };
                });
            values.forEach(function(row) {
                if (map[row.parent_id]) {
                    map[row.parent_id].children.push(map[row.id]);
                }
            });

            Object.keys(map).forEach(k => {
                if (map[k].parent != 0) {
                    delete map[k];
                }
            })

            return map;
        }

        function template(params) {
            let li = [];
            Object
                .values(params)
                .forEach(row => {
                    li.push(
                        '<li>' +
                        '<div>' +
                        (row.children.length ? '<i class="fa-solid fa-angle-right" onclick="toggleChildren(this)"></i>' : '') +
                        '<span onclick="update(' + row.id + ', \'' + row.title + '\')">' + row.title + '</span>' +
                        '<div class="btn-group" role="group">' +
                        '<span class="btn btn-outline-secondary btn-sm" onclick="add(' + row.id + ')"><i class="fa-solid fa-plus"></i></span>' +
                        '<span class="btn btn-outline-secondary btn-sm" onclick="remove(' + row.id + ')"><i class="fa-solid fa-minus"></i></span>' +
                        '</div>' +
                        '</div>' +
                        (row.children.length ? this.template(row.children) : '') +
                        '</li>'
                    );
                });
            return li.length ? '<ul>' + li.join('') + '</ul>' : '';
        }

        function openModal(modal_id, id) {
            var modal = new bootstrap.Modal(modal_id, {
                keyboard: false
            });
            modalItemId = id;
            modal.show();

            this.startTimer();
        }

        function closeModal() {
            $('.modal input').val('');
            $('.modal').modal('hide')
            clearInterval(timer);
            modalItemId = 0;
        }

        function createRoot() {
            this.createRequest({
                title: 'root'
            })
        }

        function add(id) {
            this.modalItemUpdate = false;
            modalAdd.find('.btn-primary').text('Add item');
            this.openModal(modalAdd, id);
        }

        function addSubmit() {
            let data = {
                title: modalAdd.find('input').val()
            }
            if (this.modalItemUpdate) {
                data.id = modalItemId
                this.updateRequest(data)
            } else {
                data.parent_id = modalItemId
                this.createRequest(data)
            }
        }

        function remove(id) {
            this.openModal(modalRemove, id)
        }

        function update(id, title) {
            this.modalItemUpdate = true;
            modalAdd.find('.btn-primary').text('Edit item');
            modalAdd.find('input').val(title);
            this.add(id)
        }

        function startTimer() {
            let start = START_TIME;
            $('.timer').text(start)
            timer = setInterval(() => {
                start -= 1;
                $('.timer').text(start)
                if (start <= 0) {
                    this.closeModal();
                }
            }, 1000);
        }

        function toggleChildren(el) {
            $(el).closest('li').children('ul').toggle()
            if ($(el).hasClass('fa-angle-right')) {
                $(el).removeClass('fa-angle-right')
                $(el).addClass('fa-angle-down');
            } else {
                $(el).removeClass('fa-angle-down')
                $(el).addClass('fa-angle-right');
            }
        }
    </script>
</body>

</html>