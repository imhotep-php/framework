<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query\Traits;

trait HasQueryLockTest
{
    public function testLockForUpdate(): void
    {
        $sql = $this->users()
            ->where('id', 1)
            ->lockForUpdate()
            ->toSql();

        $sqlExpected = '';
        if ($this->getType() === 'mysql' || $this->getType() === 'pgsql') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ? FOR UPDATE';
        }

        if ($this->getType() === 'sqlite') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ?';
        }

        $this->assertSame($sqlExpected, $this->fixSqlQuote($sql));
    }

    public function testSharedLock(): void
    {
        $sql = $this->users()
            ->where('id', 1)
            ->sharedLock()
            ->toSql();

        $sqlExpected = '';
        if ($this->getType() === 'mysql') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ? LOCK IN SHARE MODE';
        }

        if ($this->getType() === 'pgsql') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ? FOR SHARE';
        }

        if ($this->getType() === 'sqlite') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ?';
        }

        $this->assertSame($sqlExpected, $this->fixSqlQuote($sql));
    }

    public function testLock(): void
    {
        $sql = $this->users()
            ->where('id', 1)
            ->lock('FOR UPDATE NOWAIT')
            ->toSql();

        $this->assertSame(
            'SELECT * FROM "users" WHERE "id" = ? FOR UPDATE NOWAIT',
            $this->fixSqlQuote($sql)
        );
    }

    public function testLockFalse(): void
    {
        $sql = $this->users()
            ->where('id', 1)
            ->lockForUpdate()
            ->lock(false)
            ->toSql();

        $sqlExpected = '';
        if ($this->getType() === 'mysql') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ? LOCK IN SHARE MODE';
        }

        if ($this->getType() === 'pgsql') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ? FOR SHARE';
        }

        if ($this->getType() === 'sqlite') {
            $sqlExpected = 'SELECT * FROM "users" WHERE "id" = ?';
        }

        $this->assertSame($sqlExpected, $this->fixSqlQuote($sql));
    }
}