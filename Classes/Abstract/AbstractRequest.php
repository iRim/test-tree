<?php

namespace App\Classes\Abstract;


abstract class AbstractRequest
{
    abstract protected function create(string $title, int $parent_id = 0): array;
    abstract protected function read(): array;
    abstract protected function update(int $id, string $title): array;
    abstract protected function delete(int $id): array;

    public function handleRequest()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        echo json_encode(
            match ($_GET['method']) {
                'create' => $this->create(
                    $data['title'],
                    $data['parent_id'] ?? 0
                ),
                'update' => $this->update(
                    $data['id'],
                    $data['title']
                ),
                'delete' => $this->delete(
                    $data['id']
                ),
                default => $this->read()
            }
        );
    }
}
