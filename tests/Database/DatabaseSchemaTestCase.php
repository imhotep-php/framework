<?php declare(strict_types=1);

namespace Imhotep\Tests\Database;

use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\ISchemaBuilder;
use PHPUnit\Framework\TestCase;

abstract class DatabaseSchemaTestCase extends TestCase
{
    protected IConnection $connection;

    protected ISchemaBuilder $schema;

    protected string $testDatabase = 'test_db';

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createConnection();
        $this->schema = $this->connection->getSchemaBuilder();

        $this->schema->dropDatabaseIfExists($this->testDatabase);
    }

    protected function tearDown(): void
    {
        $this->schema->dropDatabaseIfExists($this->testDatabase);

        $this->connection->disconnect();

        parent::tearDown();
    }

    abstract protected function createConnection(): IConnection;

    abstract protected function getType(): string;

    public function test_database_does_not_exist_initially(): void
    {
        $this->assertFalse(
            $this->schema->hasDatabase($this->testDatabase),
            "База {$this->testDatabase} не должна существовать до создания"
        );
    }

    public function test_create_database(): void
    {
        $this->schema->createDatabase($this->testDatabase);

        $this->assertTrue(
            $this->schema->hasDatabase($this->testDatabase),
            "База {$this->testDatabase} должна существовать после createDatabase()"
        );
    }

    public function test_drop_database(): void
    {
        $this->schema->createDatabase($this->testDatabase);
        $this->assertTrue($this->schema->hasDatabase($this->testDatabase));

        $this->schema->dropDatabase($this->testDatabase);

        $this->assertFalse(
            $this->schema->hasDatabase($this->testDatabase),
            "База {$this->testDatabase} не должна существовать после dropDatabase()"
        );
    }

    public function test_drop_database_if_exists_when_exists(): void
    {
        $this->schema->createDatabase($this->testDatabase);
        $this->assertTrue($this->schema->hasDatabase($this->testDatabase));

        $this->schema->dropDatabaseIfExists($this->testDatabase);

        $this->assertFalse($this->schema->hasDatabase($this->testDatabase));
    }

    public function test_drop_database_if_exists_when_not_exists(): void
    {
        $this->schema->dropDatabaseIfExists($this->testDatabase);

        $this->assertFalse($this->schema->hasDatabase($this->testDatabase));
    }

    public function test_create_and_drop_cycle(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->schema->createDatabase($this->testDatabase);
            $this->assertTrue($this->schema->hasDatabase($this->testDatabase), "cycle #{$i}: create");

            $this->schema->dropDatabase($this->testDatabase);
            $this->assertFalse($this->schema->hasDatabase($this->testDatabase), "cycle #{$i}: drop");
        }
    }
}