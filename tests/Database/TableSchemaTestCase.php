<?php declare(strict_types=1);

namespace Imhotep\Tests\Database;

use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\ISchemaBuilder;
use PHPUnit\Framework\TestCase;

abstract class TableSchemaTestCase extends TestCase
{
    protected IConnection $connection;

    protected ISchemaBuilder $schema;

    protected string $testPrefix = '';

    protected string $testTable = 'test_table';

    protected string $testTableRenamed = 'test_table_renamed';

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createConnection();
        $this->schema = $this->connection->getSchemaBuilder();

        $this->rawDropTable($this->testTable);
        $this->rawDropTable($this->testPrefix.$this->testTable);
        $this->rawDropTable($this->testTableRenamed);
        $this->rawDropTable($this->testPrefix.$this->testTableRenamed);
    }

    protected function tearDown(): void
    {
        $this->rawDropTable($this->testTable);
        $this->rawDropTable($this->testPrefix.$this->testTable);
        $this->rawDropTable($this->testTableRenamed);
        $this->rawDropTable($this->testPrefix.$this->testTableRenamed);

        $this->connection->disconnect();

        parent::tearDown();
    }

    abstract protected function createConnection(): IConnection;

    abstract protected function getType(): string;

    abstract protected function rawTableExists(string $name): bool;


    public function test_table_does_not_exist_initially(): void
    {
        $this->assertFalse($this->schema->hasTable($this->testTable));
        $this->assertFalse($this->rawTableExists($this->testPrefix.$this->testTable));
    }

    public function test_create_table(): void
    {
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
        });

        $this->assertTrue($this->schema->hasTable($this->testTable));
        $this->assertTrue($this->rawTableExists($this->testPrefix.$this->testTable));
    }

    public function test_get_tables_returns_array(): void
    {
        $this->assertIsArray($this->schema->getTables());
    }

    public function test_get_tables_contains_created_table(): void
    {
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
        });

        $this->assertContains($this->testPrefix.$this->testTable,
            $this->normalizeTables($this->schema->getTables()));
    }

    public function test_rename_table(): void
    {
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
        });

        $this->schema->rename($this->testTable, $this->testTableRenamed);

        $this->assertFalse($this->schema->hasTable($this->testTable));
        $this->assertFalse($this->rawTableExists($this->testPrefix.$this->testTable));

        $this->assertTrue($this->schema->hasTable($this->testTableRenamed));
        $this->assertTrue($this->rawTableExists($this->testPrefix.$this->testTableRenamed));
    }

    public function test_rename_table_appears_in_get_tables(): void
    {
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
        });

        $this->schema->rename($this->testTable, $this->testTableRenamed);

        $tables = $this->normalizeTables($this->schema->getTables());

        $this->assertNotContains($this->testPrefix.$this->testTable, $tables);
        $this->assertContains($this->testPrefix.$this->testTableRenamed, $tables);
    }

    public function test_drop_table(): void
    {
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
        });

        $this->assertTrue($this->schema->hasTable($this->testTable));
        $this->assertTrue($this->rawTableExists($this->testPrefix.$this->testTable));

        $this->schema->drop($this->testTable);

        $this->assertFalse($this->schema->hasTable($this->testTable));
        $this->assertFalse($this->rawTableExists($this->testPrefix.$this->testTable));

        $tables = $this->normalizeTables($this->schema->getTables());
        $this->assertNotContains($this->testPrefix.$this->testTable, $tables);
    }

    public function test_drop_if_exists_when_exists(): void
    {
        $this->schema->create($this->testTable, function ($table) {
            $table->id();
        });

        $this->assertTrue($this->schema->hasTable($this->testTable));
        $this->assertTrue($this->rawTableExists($this->testPrefix.$this->testTable));

        $this->schema->dropIfExists($this->testTable);

        $this->assertFalse($this->schema->hasTable($this->testTable));
        $this->assertFalse($this->rawTableExists($this->testPrefix.$this->testTable));

        $tables = $this->normalizeTables($this->schema->getTables());
        $this->assertNotContains($this->testPrefix.$this->testTable, $tables);
    }

    public function test_drop_if_exists_when_not_exists(): void
    {
        $this->schema->dropIfExists($this->testTable);

        $this->assertFalse($this->schema->hasTable($this->testTable));
        $this->assertFalse($this->rawTableExists($this->testPrefix.$this->testTable));

        $tables = $this->normalizeTables($this->schema->getTables());
        $this->assertNotContains($this->testPrefix.$this->testTable, $tables);
    }

    public function test_create_and_drop_cycle(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->schema->create($this->testTable, function ($table) {
                $table->id();
            });
            $this->assertTrue($this->schema->hasTable($this->testTable), "cycle #{$i}: create");
            $this->assertTrue($this->rawTableExists($this->testPrefix.$this->testTable));

            $this->schema->drop($this->testTable);

            $this->assertFalse($this->schema->hasTable($this->testTable), "cycle #{$i}: drop");
            $this->assertFalse($this->rawTableExists($this->testPrefix.$this->testTable));
        }
    }

    protected function normalizeTables(mixed $tables): array
    {
        return array_map(function ($table) {
            if (is_string($table)) return $table;
            if (is_object($table)) {
                $values = array_values((array)$table);
                return (string)$values[0];
            }
            if (is_array($table)) {
                $values = array_values($table);
                return (string)$values[0];
            }
            return (string)$table;
        }, (array)$tables);
    }
}