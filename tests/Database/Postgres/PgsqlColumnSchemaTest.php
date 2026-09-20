<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Postgres;

use Imhotep\Tests\Database\ColumnSchemaTestCase;

class PgsqlColumnSchemaTest extends ColumnSchemaTestCase
{
    use CreatesPgsqlConnection;

    public function test_create_table_with_basic_types(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('string_col', 120)->nullable();
            $table->text('text_col')->nullable();
            $table->integer('int_col')->nullable()->default(10);
            $table->bigInteger('bigint_col')->nullable();
            $table->boolean('bool_col')->nullable();
            $table->decimal('decimal_col', 10, 2)->nullable();
            $table->float('float_col')->nullable();
            $table->date('date_col')->nullable();
            $table->time('time_col')->nullable();
            $table->json('json_col')->nullable();
        });

        $columns = $this->schema->getColumns($this->testTable);

        $this->assertCount(13, $columns);

        $columnsByName = [];
        foreach ($columns as $column) {
            $columnsByName[$column->name] = $column;
        }

        // string_col: string(120), nullable, без default
        $this->assertArrayHasKey('string_col', $columnsByName);
        $this->assertSame('character varying', $columnsByName['string_col']->type);
        $this->assertEquals(120, $columnsByName['string_col']->length);
        $this->assertTrue($columnsByName['string_col']->is_nullable);
        $this->assertNull($columnsByName['string_col']->default);

        // text_col: text, nullable, без default
        $this->assertArrayHasKey('text_col', $columnsByName);
        $this->assertSame('text', $columnsByName['text_col']->type);
        $this->assertTrue($columnsByName['text_col']->is_nullable);
        $this->assertNull($columnsByName['text_col']->default);

        // int_col: integer, nullable, default = 10
        $this->assertArrayHasKey('int_col', $columnsByName);
        $this->assertSame('integer', $columnsByName['int_col']->type);
        $this->assertTrue($columnsByName['int_col']->is_nullable);
        $this->assertEquals(10, $columnsByName['int_col']->default);

        // bigint_col: bigint, nullable, без default
        $this->assertArrayHasKey('bigint_col', $columnsByName);
        $this->assertSame('bigint', $columnsByName['bigint_col']->type);
        $this->assertTrue($columnsByName['bigint_col']->is_nullable);
        $this->assertNull($columnsByName['bigint_col']->default);

        // bool_col: boolean, nullable, без default
        $this->assertArrayHasKey('bool_col', $columnsByName);
        $this->assertSame('boolean', $columnsByName['bool_col']->type);
        $this->assertTrue($columnsByName['bool_col']->is_nullable);
        $this->assertNull($columnsByName['bool_col']->default);

        // decimal_col: numeric(10,2), nullable, без default
        $this->assertArrayHasKey('decimal_col', $columnsByName);
        $this->assertSame('numeric', $columnsByName['decimal_col']->type);
        $this->assertTrue($columnsByName['decimal_col']->is_nullable);
        $this->assertNull($columnsByName['decimal_col']->default);

        // float_col: float, nullable, без default
        $this->assertArrayHasKey('float_col', $columnsByName);
        $this->assertSame('double precision', $columnsByName['float_col']->type);
        $this->assertTrue($columnsByName['float_col']->is_nullable);
        $this->assertNull($columnsByName['float_col']->default);

        // date_col: date, nullable, без default
        $this->assertArrayHasKey('date_col', $columnsByName);
        $this->assertSame('date', $columnsByName['date_col']->type);
        $this->assertTrue($columnsByName['date_col']->is_nullable);
        $this->assertNull($columnsByName['date_col']->default);

        // time_col: time, nullable, без default
        $this->assertArrayHasKey('time_col', $columnsByName);
        $this->assertSame('time without time zone', $columnsByName['time_col']->type);
        $this->assertTrue($columnsByName['time_col']->is_nullable);
        $this->assertNull($columnsByName['time_col']->default);

        // json_col: json, nullable, без default
        $this->assertArrayHasKey('json_col', $columnsByName);
        $this->assertSame('json', $columnsByName['json_col']->type);
        $this->assertTrue($columnsByName['json_col']->is_nullable);
        $this->assertNull($columnsByName['json_col']->default);
    }
}