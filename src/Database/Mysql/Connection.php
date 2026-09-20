<?php declare(strict_types=1);

namespace Imhotep\Database\Mysql;

use Imhotep\Database\Connection as ConnectionBase;
use Imhotep\Database\Mysql\QueryBuilder as MysqlQueryBuilder;
use Imhotep\Database\Mysql\QueryGrammar as MysqlQueryGrammar;
use Imhotep\Database\Mysql\SchemaBuilder as MysqlSchemaBuilder;
use Imhotep\Database\Mysql\SchemaGrammar as MysqlSchemaGrammar;

class Connection extends ConnectionBase
{
    protected function createSchemaGrammar(): MysqlSchemaGrammar
    {
        return new MysqlSchemaGrammar();
    }

    protected function createQueryGrammar(): MysqlQueryGrammar
    {
        return new MysqlQueryGrammar();
    }

    protected function createSchemaBuilder(): MysqlSchemaBuilder
    {
        return new MysqlSchemaBuilder($this);
    }

    protected function createQueryBuilder(): MysqlQueryBuilder
    {
        return new MysqlQueryBuilder($this, $this->getQueryGrammar());
    }

    protected function configureSchemaGrammar($grammar): void
    {
        parent::configureSchemaGrammar($grammar);

        $grammar->setCharset($this->getConfig('charset', 'utf8mb4'));
        $grammar->setCollate($this->getConfig('collate', 'utf8mb4_unicode_ci'));
        $grammar->setEngine($this->getConfig('engine', 'InnoDB'));
    }
}