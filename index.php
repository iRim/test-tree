<?php

class Conn
{
    const server = 'localhost';
    const user = 'root';
    const pass = '';
    const dbname = 'test';
    const table_name = 'branch';

    static private $conn = null;

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        self::$conn = new mysqli(self::server, self::user, self::pass, self::dbname);
        if (self::$conn->connect_error) {
            self::error("Connection failed: " . self::$conn->connect_error);
        }
        self::$conn->set_charset('utf8');
    }

    static private function error(
        string $message
    ): void {
        new Exception("SQL Error! <br>" . $message);
        die;
    }

    static private function request(
        string $query
    ): mysqli_stmt {
        $response = self::$conn->prepare($query);
        if (!$response) {
            self::error("SQL Error: <br>" . self::$conn->error);
        }
        return $response;
    }

    static private function query(
        string $query
    ): mysqli_result {
        $response = self::$conn->query($query);
        if (!$response) {
            self::error("SQL Error: <br>" . self::$conn->error);
        }
        return $response;
    }

    public function create(
        string $title,
        int $parent_id = 0
    ): void {
        $response = self::request("INSERT INTO `" . self::table_name . "` (title, parent_id) VALUES(?, ?)");
        $response->bind_param('si', $title, $parent_id);
        $response->execute();
        $response->close();
    }

    public function update(
        int $id,
        string $title
    ): void {
        $response = self::request("UPDATE `" . self::table_name . "` SET title=? WHERE id=?");
        $response->bind_param('si', $title, $id);
        $response->execute();
        $response->close();
    }

    public function delete(
        int $id
    ): void {
        $response = self::request("DELETE FROM `" . self::table_name . "` WHERE id=? OR (parent_id=id AND id=?)");
        $response->bind_param('ii', $id, $id);
        $response->execute();
        $response->close();
    }

    public function get(): mysqli_result
    {
        return self::query("SELECT * FROM `" . self::table_name . "` ORDER BY id");
    }
}

foreach ((new Conn)->get() as $item) {
    echo 'ID: ' . $item['id'] . '; TITLE: ' . $item['title'];
    echo '<br>';
}
die;

if (!empty($_GET['action']) and in_array($_GET['action'], ['create', 'read', 'update', 'delete'])) {
    $conn = new Conn;

    echo json_encode(
        [
            'success' => match ($_GET['action']) {
                'create' => function () use ($conn) {
                    if (!empty($_POST['title'])) {
                        $conn->create($_POST['title'], !empty($_POST['parent_id']) ? $_POST['parent_id'] : 0);
                        return true;
                    }
                    return false;
                },
                'update' => function () use ($conn) {
                    if (!empty($_POST['id']) and !empty($_POST['title'])) {
                        $conn->update($_POST['id'], $_POST['title']);
                        return true;
                    }
                    return false;
                },
                'delete' => function () use ($conn) {
                    if (!empty($_POST['id'])) {
                        $conn->delete($_POST['id']);
                        return true;
                    }
                    return false;
                },
                default => true
            },

            'items' => $conn->get()
        ]
    );
    die;
}


?>


<!doctype html>
<html lang="en" data-bs-theme="auto">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.122.0">
    <title>Tree</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
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

            <div class="p-5 mb-4 bg-body-tertiary rounded-3">
                q
            </div>

            <footer class="pt-3 mt-4 text-body-secondary border-top">
                &copy; 2024
            </footer>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>

</html>