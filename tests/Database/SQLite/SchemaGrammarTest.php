<?php

namespace Imhotep\Tests\Database\SQLite;

use Closure;
use Imhotep\Database\Connection;
use Imhotep\Database\SQLite\Schema\Grammar;
use Imhotep\Database\Schema\Table;
use Mockery;
use PHPUnit\Framework\TestCase;

class SchemaGrammarTest extends TestCase
{
    public string $tableName = 'test';

    protected function getStatements(Closure $callback)
    {
        $table = new Table($this->tableName);

        $callback($table);

        return $table->toSql(new Grammar());
    }

    public function test_macro()
    {
        $grammar = new Grammar();

        $grammar::macro('typeFoo', function () {
            return true;
        });

        $this->assertTrue($grammar->typeFoo());
    }

    public function test_create_table()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->id();
            $table->string('title');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE "test" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "title" TEXT NOT NULL)', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->temporary();
            $table->id();
            $table->string('title');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TEMPORARY TABLE "test" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "title" TEXT NOT NULL)', $statements[0]);

    }

    public function test_rename_table()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->rename('foo');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" RENAME TO "foo"', $statements[0]);
    }

    public function test_drop_table()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->drop();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('DROP TABLE "test"', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->dropIfExists();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('DROP TABLE IF EXISTS "test"', $statements[0]);
    }

    public function test_add_base_data_types_columns()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->id();
            $table->int('int');
            $table->smallInt('smallInt');
            $table->bigInt('bigInt');
            $table->decimal('decimal');
            $table->float('float');
            $table->double('double');
            $table->bool('bool');
            $table->blob('blob');
            $table->string('string');
            $table->char('char');
            $table->varchar('varchar');
            $table->text('text');
            $table->json('json');
            $table->jsonb('jsonb');
            $table->date('date');
            $table->date('date')->useCurrent();
            $table->time('time');
            $table->time('time')->useCurrent();
            $table->datetime('datetime');
            $table->datetime('datetime')->useCurrent();
            $table->timestamp('timestamp');
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });

        $expected = [
            'ALTER TABLE "test" ADD COLUMN "id" INTEGER PRIMARY KEY AUTOINCREMENT',
            'ALTER TABLE "test" ADD COLUMN "int" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "smallInt" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "bigInt" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "decimal" REAL NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "float" REAL NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "double" REAL NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "bool" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "blob" BLOB NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "string" TEXT NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "char" TEXT NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "varchar" TEXT NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "text" TEXT NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "json" JSON NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "jsonb" JSONB NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "date" DATE NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "date" DATE DEFAULT CURRENT_DATE NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "time" TIME NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "time" TIME DEFAULT CURRENT_TIME NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "datetime" DATETIME NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "datetime" DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "timestamp" TIMESTAMP NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "timestamp" TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "deleted_at" TIMESTAMP DEFAULT NULL',
        ];

        $this->assertCount(26, $statements);
        $this->assertEquals($expected, $statements);
    }

    public function test_rename_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->renameColumn('foo', 'bar');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" RENAME COLUMN "foo" TO "bar"', $statements[0]);
    }

    public function test_drop_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->dropColumn('foo');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" DROP COLUMN "foo"', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->dropColumn(['foo','bar','baz']);
        });

        $this->assertCount(3, $statements);
        $this->assertSame([
            'ALTER TABLE "test" DROP COLUMN "foo"',
            'ALTER TABLE "test" DROP COLUMN "bar"',
            'ALTER TABLE "test" DROP COLUMN "baz"',
        ], $statements);
    }

    public function test_add_index_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->index('bar', 'foo');
            $table->index(['bar','baz'], 'foo');
        });

        $this->assertCount(2, $statements);
        $this->assertSame('CREATE INDEX "foo" ON "test" ("bar")', $statements[0]);
        $this->assertSame('CREATE INDEX "foo" ON "test" ("bar", "baz")', $statements[1]);
    }

    public function test_add_index_unique_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->unique('foo'); // Without index name and one column
            $table->unique(['foo','bar','baz']); // Without index name and multi columns
            $table->unique(['foo','bar','baz'], 'myindex'); // With index name and multi columns
        });

        $expected = [
            'CREATE UNIQUE INDEX "test_foo_unique" ON "test" ("foo")',
            'CREATE UNIQUE INDEX "test_foo_bar_baz_unique" ON "test" ("foo", "bar", "baz")',
            'CREATE UNIQUE INDEX "myindex" ON "test" ("foo", "bar", "baz")',
        ];

        $this->assertCount(3, $statements);
        $this->assertSame($expected, $statements);
    }

    public function test_drop_index()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->dropPrimary('foo');
            $table->dropPrimary(['foo1','foo2']);

            $table->dropUnique('bar');
            $table->dropUnique(['bar1','bar2']);

            $table->dropIndex('baz');
            $table->dropIndex(['baz1','baz2']);
        });

        $expected = [
            'DROP INDEX "foo"',
            'DROP INDEX "test_foo1_foo2_primary"',
            'DROP INDEX "bar"',
            'DROP INDEX "test_bar1_bar2_unique"',
            'DROP INDEX "baz"',
            'DROP INDEX "test_baz1_baz2_index"',
        ];

        $this->assertCount(6, $statements);
        $this->assertSame($expected, $statements);
    }

    public function test_raw_index()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->rawIndex('(function(column))', 'foo');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE INDEX "foo" ON "test" ((function(column)))', $statements[0]);
    }

    public function test_enum_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->enum('type', ['foo','bar','baz']);
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD COLUMN "type" TEXT CHECK ("type" in (\'foo\', \'bar\', \'baz\')) NOT NULL', $statements[0]);
    }

    public function test_generated_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->id('a');
            $table->int('b');
            $table->text('c');
            $table->int('d')->virtualAs('a*abs(b)')->nullable();
            $table->int('e')->storedAs('substr(c,b,b+1)')->nullable();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE "test" ("a" INTEGER PRIMARY KEY AUTOINCREMENT, "b" INTEGER NOT NULL, "c" TEXT NOT NULL, "d" INTEGER GENERATED ALWAYS AS (a*abs(b)) VIRTUAL, "e" INTEGER GENERATED ALWAYS AS (substr(c,b,b+1)) STORED)', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->int('f')->virtualAs('a + b')->nullable();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD COLUMN "f" INTEGER GENERATED ALWAYS AS (a + b) VIRTUAL', $statements[0]);
    }

    public function test_foreign_key()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->string('foo')->primary();
            $table->int('bar_id')->default(0);
            $table->foreign('bar_id')->references('id')->on('bars')->onUpdateCascade()->onDeleteSetDefault();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE "test" ("foo" TEXT NOT NULL, "bar_id" INTEGER NOT NULL DEFAULT "0", PRIMARY KEY ("foo"), FOREIGN KEY ("bar_id") REFERENCES "bars"("id"))', $statements[0]);
    }

    public function test_foreignId()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->id();
            $table->foreingId('foo_id')->constrained();
            $table->foreingId('foo_bar_id')->constrained();
            $table->foreingId('item_id')->references('id')->on('items');
            $table->foreingId('item_foo_id')->references('id')->on('items');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE "test" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "foo_id" INTEGER NOT NULL, "foo_bar_id" INTEGER NOT NULL, "item_id" INTEGER NOT NULL, "item_foo_id" INTEGER NOT NULL, FOREIGN KEY ("foo_id") REFERENCES "foo"("id"), FOREIGN KEY ("foo_bar_id") REFERENCES "foo_bar"("id"), FOREIGN KEY ("item_id") REFERENCES "items"("id"), FOREIGN KEY ("item_foo_id") REFERENCES "items"("id"))', $statements[0]);


        $statements = $this->getStatements(function (Table $table) {
            $table->foreingId('foo_id')->constrained();
            $table->foreingId('foo_bar_id')->constrained();
            $table->foreingId('item_id')->references('id')->on('items');
            $table->foreingId('item_foo_id')->references('id')->on('items');
        });

        $expected = [
            'ALTER TABLE "test" ADD COLUMN "foo_id" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "foo_bar_id" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "item_id" INTEGER NOT NULL',
            'ALTER TABLE "test" ADD COLUMN "item_foo_id" INTEGER NOT NULL',
        ];

        $this->assertCount(4, $statements);
        $this->assertSame($expected, $statements);
    }

    public function test_generated_json_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->string('first_json');
            $table->string('second_json')->virtualAsJson('first_json->attribute');
            $table->string('third_json')->virtualAsJson('first_json->attribute->value');
            $table->string('fourth_json')->virtualAsJson('first_json->attribute[0][1]');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE "test" ("first_json" TEXT NOT NULL, "second_json" TEXT GENERATED ALWAYS AS (json_extract("first_json", \'$."attribute"\')) VIRTUAL, "third_json" TEXT GENERATED ALWAYS AS (json_extract("first_json", \'$."attribute"."value"\')) VIRTUAL, "fourth_json" TEXT GENERATED ALWAYS AS (json_extract("first_json", \'$."attribute"[0][1]\')) VIRTUAL)', $statements[0]);
    }
}