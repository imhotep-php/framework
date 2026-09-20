<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query\Traits;

trait HasQueryFromTest
{
    public function testFromBasic(): void
    {
        $sql = $this->builder()->from('users')->toSql();
        $this->assertSame('SELECT * FROM "users"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->from('users', 'u')->toSql();
        $this->assertSame('SELECT * FROM "users" as "u"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->from('users data', 'u')->toSql();
        $this->assertSame('SELECT * FROM "users data" as "u"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->from('users u')->toSql();
        $this->assertSame('SELECT * FROM "users" as "u"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->from('users as u')->toSql();
        $this->assertSame('SELECT * FROM "users" as "u"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->from('users As u')->toSql();
        $this->assertSame('SELECT * FROM "users" as "u"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->from('users AS u')->toSql();
        $this->assertSame('SELECT * FROM "users" as "u"', $this->fixSqlQuote($sql));

        $sql = $this->builder()->fromRaw('users AS u')->toSql();
        $this->assertSame('SELECT * FROM users AS u', $this->fixSqlQuote($sql));

        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->from('users as')->toSql();
    }

    public function testFromWithClosure(): void
    {
        $sql = $this->builder()
            ->from(function($query) {
                $query->from('posts')->where('status', 'active');
            }, 'p')
            ->toSql();

        $this->assertSame(
            'SELECT * FROM (SELECT * FROM "posts" WHERE "status" = ?) as "p"',
            $this->fixSqlQuote($sql));
    }

    public function testFromWithQuery(): void
    {
        $subQuery = $this->builder()
            ->from('posts')
            ->where('status', 'active');

        $sql = $this->builder()
            ->from($subQuery, 'p')
            ->toSql();

        $this->assertSame(
            'SELECT * FROM (SELECT * FROM "posts" WHERE "status" = ?) as "p"',
            $this->fixSqlQuote($sql));
    }
}