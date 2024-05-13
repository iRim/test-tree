<?php

namespace App\Classes;

use App\Classes\Abstract\AbstractRequest;

class Request extends AbstractRequest
{
    private $db;

    public function __construct()
    {
        $this->db = new DB;
    }

    public function create(string $title, int $parent_id = 0): array
    {
        $this->db->create($title, $parent_id);
        return $this->read();
    }

    public function read(): array
    {
        $items = [];
        foreach ($this->db->read() as $item) {
            $items[] = $item;
        }
        return $items;
    }

    public function update(int $id, string $title): array
    {
        $this->db->update($id, $title);
        return $this->read();
    }

    public function delete(int $id): array
    {
        $this->db->delete($id);
        return $this->read();
    }
}
