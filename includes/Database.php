<?php
/**
 * CSCA Bridge - 数据库连接类
 * 提供PDO数据库连接和基础CRUD操作
 */

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];
    
    /**
     * 获取数据库连接实例（单例模式）
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }
    
    /**
     * 建立数据库连接
     */
    private static function connect(): void
    {
        $config = require __DIR__ . '/../config/database.php';
        $dbConfig = $config['connections']['mysql'];
        
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['driver'],
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database'],
            $dbConfig['charset']
        );
        
        try {
            self::$instance = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    /**
     * 执行查询语句
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * 获取单行记录
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * 获取多行记录
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * 获取单个值
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * 插入记录
     */
    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));
        
        return (int) self::getInstance()->lastInsertId();
    }
    
    /**
     * 更新记录
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * 删除记录
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * 开始事务
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }
    
    /**
     * 回滚事务
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }
    
    /**
     * 获取最后插入ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
