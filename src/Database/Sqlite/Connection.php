<?php declare(strict_types=1);

namespace Imhotep\Database\Sqlite;

use Imhotep\Database\Connection as ConnectionBase;
use Imhotep\Database\Sqlite\QueryBuilder as SqliteQueryBuilder;
use Imhotep\Database\Sqlite\QueryGrammar as SqliteQueryGrammar;
use Imhotep\Database\Sqlite\SchemaBuilder as SqliteSchemaBuilder;
use Imhotep\Database\Sqlite\SchemaGrammar as SqliteSchemaGrammar;

class Connection extends ConnectionBase
{
    public function __construct($pdo, array $config = [])
    {
        parent::__construct($pdo, $config);

        if (isset($config['foreign_keys']) && $config['foreign_keys'] === true) {
            $this->getSchemaBuilder()->enableForeignKeys();
        }
        else {
            $this->getSchemaBuilder()->disableForeignKeys();
        }
    }

    protected function createSchemaGrammar(): SqliteSchemaGrammar
    {
        return new SqliteSchemaGrammar();
    }

    protected function createQueryGrammar(): SqliteQueryGrammar
    {
        return new SqliteQueryGrammar();
    }

    protected function createSchemaBuilder(): SqliteSchemaBuilder
    {
        return new SqliteSchemaBuilder($this);
    }

    protected function createQueryBuilder(): SqliteQueryBuilder
    {
        return new SqliteQueryBuilder($this, $this->getQueryGrammar());
    }
}