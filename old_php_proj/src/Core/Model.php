<?php
// =============================================================
// src/Core/Model.php - النموذج الأساسي
// =============================================================

namespace App\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * البحث بالمعرف
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * جلب الكل مع شروط اختيارية
     */
    public function all(array $conditions = [], string $orderBy = 'id DESC', ?int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $col => $val) {
                if ($val === null) {
                    $where[] = "{$col} IS NULL";
                } else {
                    $where[] = "{$col} = ?";
                    $params[] = $val;
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY {$orderBy}";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * البحث بشرط واحد
     */
    public function findWhere(array $conditions): ?array
    {
        $where = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $where[] = "{$col} IS NULL";
            } else {
                $where[] = "{$col} = ?";
                $params[] = $val;
            }
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " LIMIT 1"
        );
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /**
     * إدراج سجل جديد
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    /**
     * تحديث سجل
     */
    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?"
        );
        $params = array_values($data);
        $params[] = $id;
        return $stmt->execute($params);
    }

    /**
     * حذف سجل
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * عدد السجلات
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $col => $val) {
                $where[] = "{$col} = ?";
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Pagination بـ cursor (أفضل أداء من OFFSET)
     */
    public function cursorPaginate(int $perPage = 25, ?int $afterId = null, array $conditions = [], string $orderDir = 'DESC'): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        $where = [];
        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $where[] = "{$col} IS NULL";
            } else {
                $where[] = "{$col} = ?";
                $params[] = $val;
            }
        }

        if ($afterId !== null) {
            $op = $orderDir === 'DESC' ? '<' : '>';
            $where[] = "{$this->primaryKey} {$op} ?";
            $params[] = $afterId;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY {$this->primaryKey} {$orderDir} LIMIT " . ((int)$perPage + 1);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        $hasMore = count($results) > $perPage;
        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = $hasMore && !empty($results) ? end($results)[$this->primaryKey] : null;

        return [
            'data' => $results,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * تنفيذ استعلام مخصص
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
