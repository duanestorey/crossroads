<?php

namespace CR;

class SQLite
{
    protected $sql;
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;

        if (!file_exists(CROSSROADS_DB_DIR)) {
            @mkdir(CROSSROADS_DB_DIR);
        }

        $this->sql = new \SQLite3(CROSSROADS_DB_DIR . '/db.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $this->sql->exec('PRAGMA foreign_keys = ON');
    }

    public function __destruct()
    {
        if ($this->sql) {
            $this->sql->close();
        }
    }

    public function rebuild()
    {
        $schemaFiles = Utils::findAllFilesWithExtension(CROSSROADS_CORE_DIR . '/schemas', 'sql');
        if ($schemaFiles) {
            foreach ($schemaFiles as $schema) {
                $schemaContents = file_get_contents($schema);
                $this->sql->query($schemaContents);
            }
        }
    }

    public function prepare($sql)
    {
        return $this->sql->prepare($sql);
    }

    public function query($sql)
    {
        return $this->sql->query($sql);
    }

    public function getLastRowID()
    {
        return $this->sql->lastInsertRowID();
    }
}
