<?php

namespace Imhotep\Tests\Database;

use Imhotep\Contracts\Database\Connection;
use Imhotep\Database\Query\Builder;
use Imhotep\Database\Query\Grammar;
use PHPUnit\Framework\TestCase;

class QueryGrammarTest extends TestCase
{
    protected function getConnection(): Connection
    {
        return $this->createMock(\Imhotep\Database\Connection::class);
    }

    protected function getBuilder(): Builder
    {
        return new Builder($this->getConnection(), new Grammar());
    }

    protected function table(string $tableName = 'test'): Builder
    {
        return $this->getBuilder()->from($tableName)->withSQL();
    }

    protected function assertQueryEquals(string $expectedSql, array $expectedBindings, array $actual): void
    {
        [$actualSql, $actualBindings] = $actual;

        $this->assertSame($expectedSql, $actualSql);
        $this->assertSame($expectedBindings, $actualBindings);
    }

    public function testBasicInsert()
    {
        $query = $this->table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ]);

        $expectedSql = 'INSERT INTO "users" ("name", "email", "age") VALUES (?, ?, ?)';
        $expectedBindings = ['John Doe', 'john@example.com', 30];

        $this->assertQueryEquals($expectedSql, $expectedBindings, $query);
    }
}