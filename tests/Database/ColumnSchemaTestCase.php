<?php declare(strict_types=1);

namespace Imhotep\Tests\Database;

use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\ISchemaBuilder;
use PHPUnit\Framework\TestCase;

abstract class ColumnSchemaTestCase extends TestCase
{
    protected IConnection $connection;

    protected ISchemaBuilder $schema;

    protected string $testTable = 'test_table';

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createConnection();
        $this->schema = $this->connection->getSchemaBuilder();

        $this->schema->dropIfExists($this->testTable);
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropIfExists($this->testTable);

        $this->connection->disconnect();

        parent::tearDown();
    }

    abstract protected function createConnection(): IConnection;

    abstract protected function getType(): string;


    public function test_has_column(): void
    {
        $this->assertTrue($this->schema->hasColumn($this->testTable, 'id'));
        $this->assertFalse($this->schema->hasColumn($this->testTable, 'missing'));
    }

    public function test_has_columns(): void
    {
        $this->assertTrue($this->schema->hasColumns($this->testTable, ['id', 'created_at', 'updated_at']));
    }

    public function test_has_columns_when_any_missing(): void
    {
        $this->assertFalse(
            $this->schema->hasColumns($this->testTable, ['id', 'missing'])
        );
    }

    public function test_get_columns_returns_array(): void
    {
        $this->assertIsArray($this->schema->getColumns($this->testTable));
    }

    public function test_get_columns(): void
    {
        $this->assertContains('id', $this->schema->getColumnNames($this->testTable));
    }

    public function test_add_column(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('name');
        });

        $this->assertTrue($this->schema->hasColumn($this->testTable, 'name'));
    }

    public function test_add_multiple_columns_in_one_call(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('a');
            $table->string('b');
            $table->integer('c');
        });

        $this->assertTrue($this->schema->hasColumns($this->testTable, ['a', 'b', 'c']));
    }

    public function test_rename_column(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('old_name');
        });

        $this->assertTrue($this->schema->hasColumn($this->testTable, 'old_name'));

        $this->schema->renameColumn($this->testTable, 'old_name', 'new_name');

        $this->assertFalse($this->schema->hasColumn($this->testTable, 'old_name'));
        $this->assertTrue($this->schema->hasColumn($this->testTable, 'new_name'));
    }

    public function test_rename_column_keeps_other_columns(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('a');
            $table->string('b');
        });

        $this->schema->renameColumn($this->testTable, 'a', 'a_renamed');

        $this->assertTrue($this->schema->hasColumn($this->testTable, 'a_renamed'));
        $this->assertTrue($this->schema->hasColumn($this->testTable, 'b'));
        $this->assertTrue($this->schema->hasColumn($this->testTable, 'id'));
    }

    public function test_drop_column(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('to_drop');
        });

        $this->assertTrue($this->schema->hasColumn($this->testTable, 'to_drop'));

        $this->schema->dropColumn($this->testTable, 'to_drop');

        $this->assertFalse($this->schema->hasColumn($this->testTable, 'to_drop'));
    }

    public function test_drop_multiple_columns(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('a');
            $table->string('b');
            $table->string('c');
        });

        $this->schema->dropColumn($this->testTable, ['a', 'b']);

        $this->assertFalse($this->schema->hasColumn($this->testTable, 'a'));
        $this->assertFalse($this->schema->hasColumn($this->testTable, 'b'));
        $this->assertTrue($this->schema->hasColumn($this->testTable, 'c'));
    }

    public function test_get_column_type(): void
    {
        $this->schema->table($this->testTable, function ($table) {
            $table->string('name');
        });

        $type = $this->schema->getColumnType($this->testTable, 'name');

        $this->assertNotEmpty($type);
        $this->assertIsString($type);
    }
}