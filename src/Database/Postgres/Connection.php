<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres;

use Imhotep\Database\Connection as ConnectionBase;
use Imhotep\Database\Postgres\QueryBuilder as PostgresQueryBuilder;
use Imhotep\Database\Postgres\QueryGrammar as PostgresQueryGrammar;
use Imhotep\Database\Postgres\SchemaBuilder as PostgresSchemaBuilder;
use Imhotep\Database\Postgres\SchemaGrammar as PostgresSchemaGrammar;

class Connection extends ConnectionBase
{
    public function __construct($pdo, array $config = [])
    {
        if (empty($config['schema'])) {
            $config['schema'] = 'public';
        }

        parent::__construct($pdo, $config);
    }

    protected function createSchemaGrammar(): PostgresSchemaGrammar
    {
        return new PostgresSchemaGrammar();
    }

    protected function createQueryGrammar(): PostgresQueryGrammar
    {
        return new PostgresQueryGrammar();
    }

    protected function createSchemaBuilder(): PostgresSchemaBuilder
    {
        return new PostgresSchemaBuilder($this);
    }

    protected function createQueryBuilder(): PostgresQueryBuilder
    {
        return new PostgresQueryBuilder($this, $this->getQueryGrammar());
    }

    protected function configureSchemaGrammar($grammar): void
    {
        parent::configureSchemaGrammar($grammar);

        $grammar->setCharset($this->getConfig('charset', 'utf8'));
    }
}