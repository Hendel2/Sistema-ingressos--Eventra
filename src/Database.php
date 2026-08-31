<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function init(array $dbConfig): void
    {
        if (self::$pdo !== null) {
            return;
        }

        // A porta e opcional: hospedagens compartilhadas as vezes usam
        // uma porta diferente da 3306 padrao.
        $dsn = sprintf(
            'mysql:host=%s;%sdbname=%s;charset=%s',
            $dbConfig['host'],
            isset($dbConfig['port']) ? 'port=' . (int) $dbConfig['port'] . ';' : '',
            $dbConfig['name'],
            $dbConfig['charset']
        );

        self::$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('Database não inicializado. Chame Database::init() primeiro.');
        }

        return self::$pdo;
    }
}
