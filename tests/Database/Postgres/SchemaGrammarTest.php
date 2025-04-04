<?php

namespace Imhotep\Tests\Database\Postgres;

use Closure;
use Imhotep\Database\Postgres\Schema\Grammar;
use Imhotep\Database\Schema\Table;
use PHPUnit\Framework\TestCase;

class SchemaGrammarTest extends TestCase
{
    public string $tableName = 'test';

    protected function getGrammar()
    {
        $grammar = new Grammar();
        $grammar->setCharset('UTF8');

        return $grammar;
    }

    protected function getStatements(Closure $callback)
    {
        $table = new Table($this->tableName);

        $callback($table);

        return $table->toSql($this->getGrammar());
    }

    public function test_macro()
    {
        $grammar = new Grammar();

        $grammar::macro('typeFoo', function () {
            return true;
        });

        $this->assertTrue($grammar->typeFoo());
    }

    public function test_create_database()
    {
        $statement = $this->getGrammar()->compileCreateDatabase('foo');

        $this->assertSame('CREATE DATABASE "foo" ENCODING "UTF8"', $statement);
    }

    public function test_drop_database()
    {
        $statement = $this->getGrammar()->compileDropDatabase('foo');

        $this->assertSame('DROP DATABASE "foo"', $statement);

        $statement = $this->getGrammar()->compileDropDatabaseIfExists('foo');

        $this->assertSame('DROP DATABASE IF EXISTS "foo"', $statement);
    }

    public function test_create_table()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->id();
            $table->string('title');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TABLE "test" ("id" bigserial PRIMARY KEY NOT NULL, "title" varchar(255) NOT NULL)', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->temporary();
            $table->id();
            $table->string('title');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('CREATE TEMPORARY TABLE "test" ("id" bigserial PRIMARY KEY NOT NULL, "title" varchar(255) NOT NULL)', $statements[0]);
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

    public function test_numeric_data_types_columns()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->id();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "id" bigserial PRIMARY KEY NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->bool('bool');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "bool" boolean NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->int('int');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "int" integer NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->smallInt('smallInt');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "smallInt" smallint NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->bigInt('bigInt');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "bigInt" bigint NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->decimal('decimal');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "decimal" numeric NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->float('float');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "float" double precision NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->double('double');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "double" double precision NOT NULL', $statements[0]);
    }

    public function test_characters_data_types_columns()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->string('string');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "string" varchar(255) NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->char('char');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "char" char(1) NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->varchar('varchar');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "varchar" varchar(255) NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->text('text');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "text" text NOT NULL', $statements[0]);
    }

    public function test_json_data_types_columns()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->json('json');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "json" json NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->jsonb('jsonb');
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "jsonb" jsonb NOT NULL', $statements[0]);
    }

    public function test_datetime_data_types_columns()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->date('date');
            $table->date('date')->useCurrent();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "date" date NOT NULL, ADD "date" date default CURRENT_DATE NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->time('time');
            $table->time('time')->useCurrent();
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "time" time without time zone NOT NULL, ADD "time" time without time zone default CURRENT_TIME NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->timestamp('added');
            $table->timestamp('updated')->useCurrent();
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "added" timestamp without time zone NOT NULL, ADD "updated" timestamp without time zone default CURRENT_TIMESTAMP NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->timestamps();
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "created_at" timestamp without time zone default CURRENT_TIMESTAMP NOT NULL, ADD "updated_at" timestamp without time zone default CURRENT_TIMESTAMP NOT NULL', $statements[0]);

        $statements = $this->getStatements(function (Table $table) {
            $table->softDeletes();
        });
        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "deleted_at" timestamp without time zone DEFAULT NULL', $statements[0]);
    }

    public function test_auto_increment_column_from()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->create();
            $table->id()->from(1000);
        });

        $expected = [
            'CREATE TABLE "test" ("id" bigserial PRIMARY KEY NOT NULL)',
            'ALTER SEQUENCE test_id_seq RESTART WITH 1000',
        ];

        $this->assertCount(2, $statements);
        $this->assertSame($expected, $statements);

        $statements = $this->getStatements(function (Table $table) {
            $table->id()->from(1000);
        });

        $expected = [
            'ALTER TABLE "test" ADD "id" bigserial PRIMARY KEY NOT NULL',
            'ALTER SEQUENCE test_id_seq RESTART WITH 1000',
        ];

        $this->assertCount(2, $statements);
        $this->assertSame($expected, $statements);
    }

    public function test_enum_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->enum('type', ['foo','bar','baz']);
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" ADD "type" varchar(3) check ("type" in (\'foo\', \'bar\', \'baz\')) NOT NULL', $statements[0]);
    }

    public function test_rename_column()
    {
        // For new mysql version
        $statements = $this->getStatements(function (Table $table) {
            $table->renameColumn('foo', 'bar');
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" RENAME COLUMN "foo" TO "bar"', $statements[0]);
    }

    public function test_drop_column()
    {
        $statements = $this->getStatements(function (Table $table) {
            $table->dropColumn('foo', true);
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" DROP COLUMN "foo" CASCADE', $statements[0]);


        $statements = $this->getStatements(function (Table $table) {
            $table->dropColumn(['foo','bar','baz']);
        });

        $this->assertCount(1, $statements);
        $this->assertSame('ALTER TABLE "test" DROP COLUMN "foo", DROP COLUMN "bar", DROP COLUMN "baz"', $statements[0]);
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
            'ALTER TABLE "test" ADD CONSTRAINT "test_foo_unique" UNIQUE ("foo")',
            'ALTER TABLE "test" ADD CONSTRAINT "test_foo_bar_baz_unique" UNIQUE ("foo", "bar", "baz")',
            'ALTER TABLE "test" ADD CONSTRAINT "myindex" UNIQUE ("foo", "bar", "baz")',
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
            'ALTER TABLE "test" DROP CONSTRAINT "test_pkey"',
            'ALTER TABLE "test" DROP CONSTRAINT "test_pkey"',
            'ALTER TABLE "test" DROP CONSTRAINT "bar"',
            'ALTER TABLE "test" DROP CONSTRAINT "test_bar1_bar2_unique"',
            'DROP INDEX "baz"',
            'DROP INDEX "test_baz1_baz2_index"',
        ];

        $this->assertCount(6, $statements);
        $this->assertSame($expected, $statements);
    }
}