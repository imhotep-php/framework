<?php

declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Contracts\Database\SchemaBuilder as SchemaBuilderContract;
use Imhotep\Database\Connection;

abstract class Builder implements SchemaBuilderContract
{
    protected $connection;

    protected $grammar;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    public function connection($name){

    }

    public function getColumnListing(string $table): array
    {
        return [];
    }

    public function create($table, \Closure $callback)
    {
        $table = $this->createTable($table, $callback);
        $table->create();
        $this->build($table);
    }

    public function table($table, \Closure $callback){
        $this->build($this->createTable($table, $callback));
    }

    public function drop($table)
    {
        $table = $this->createTable($table);
        $table->drop();
        $this->build($table);
    }

    public function dropIfExists($table)
    {
        $table = $this->createTable($table);
        $table->dropIfExists();
        $this->build($table);
    }

    protected function createTable(string $table, \Closure $callback = null): Table
    {
        throw new DatabaseException("Table for schema not configured.");
    }

    protected function build($table)
    {
        $table->build($this->connection, $this->grammar);
    }
}