<?php declare(strict_types=1);

namespace Imhotep\Database\Postgres;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Connection as ConnectionBase;
use Imhotep\Database\Postgres\Schema\Builder as SchemaBuilder;
use Imhotep\Database\Postgres\Schema\Grammar as SchemaGrammar;
use Imhotep\Database\Postgres\Query\Builder as QueryBuilder;
use Imhotep\Database\Postgres\Query\Grammar as QueryGrammar;

class Connection extends ConnectionBase
{
    public function getSchema()
    {
        if (empty($this->config['schema'])) {
            throw new DatabaseException("For connection [%s] schema not configured.");
        }

        return $this->config['schema'];
    }

    public function useSchemaGrammar(): static
    {
        $this->schemaGrammar = new SchemaGrammar();
        $this->schemaGrammar->setTablePrefix($this->tablePrefix);
        $this->schemaGrammar->setCharset($this->getConfig('charset', 'utf8mb4'));

        return $this;
    }

    public function getSchemaBuilder(): SchemaBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    public function useQueryGrammar(): static
    {
        $this->queryGrammar = new QueryGrammar();
        $this->queryGrammar->setTablePrefix($this->tablePrefix);

        return $this;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if (is_null($this->queryGrammar)) {
            $this->useQueryGrammar();
        }

        return new QueryBuilder($this, $this->queryGrammar);
    }
}