<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query\Traits;

trait HasUnionTest
{
    public function testUnionWithString(): void
    {
        $query = $this->users()->union('SELECT * FROM admins');

        $this->assertEquals(
            'SELECT * FROM "users" UNION (SELECT * FROM admins)',
            $this->fixSqlQuote($query->toSql())
        );
        $this->assertEmpty($query->getBindings());


        $query = $this->users()->union('SELECT * FROM admins WHERE active = ?', [true]);

        $this->assertEquals(
            'SELECT * FROM "users" UNION (SELECT * FROM admins WHERE active = ?)',
            $this->fixSqlQuote($query->toSql())
        );
        $this->assertCount(1, $query->getBindings());
    }

    public function testUnionAllWithString(): void
    {
        $query = $this->users()->unionAll('SELECT * FROM admins');

        $this->assertEquals(
            'SELECT * FROM "users" UNION ALL (SELECT * FROM admins)',
            $this->fixSqlQuote($query->toSql())
        );
        $this->assertEmpty($query->getBindings());


        $query = $this->users()->unionAll('SELECT * FROM admins WHERE active = ?', [true]);

        $this->assertEquals(
            'SELECT * FROM "users" UNION ALL (SELECT * FROM admins WHERE active = ?)',
            $this->fixSqlQuote($query->toSql())
        );
        $this->assertCount(1, $query->getBindings());
    }

    public function testUnionWithClosure(): void
    {
        $query = $this->users()->union(function ($query) {
            $query->from('admins')->where('active', 1);
        });

        $this->assertEquals(
            'SELECT * FROM "users" UNION (SELECT * FROM "admins" WHERE "active" = ?)',
            $this->fixSqlQuote($query->toSql())
        );
        $this->assertEquals([1], $query->getBindings());
    }

    public function testUnionWithSubBuilder(): void
    {
        $subQuery = $this->builder()->from('admins')->where('role', 'admin');

        $query = $this->users()->union($subQuery);

        $this->assertEquals(
            'SELECT * FROM "users" UNION (SELECT * FROM "admins" WHERE "role" = ?)',
            $this->fixSqlQuote($query->toSql())
        );
        $this->assertEquals(['admin'], $query->getBindings());
    }

    public function testMultipleUnions(): void
    {
        $query = $this->users()
            ->union('SELECT * FROM admins')
            ->unionAll('SELECT * FROM guests');

        $this->assertEquals(
            'SELECT * FROM "users" UNION (SELECT * FROM admins) UNION ALL (SELECT * FROM guests)',
            $this->fixSqlQuote($query->toSql())
        );
    }
}