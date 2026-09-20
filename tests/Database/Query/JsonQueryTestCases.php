<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query;

use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\IQueryBuilder;
use PHPUnit\Framework\TestCase;

abstract class JsonQueryTestCases extends TestCase
{
    protected IConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createConnection();

        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanupDatabase();

        parent::tearDown();
    }

    abstract protected function createConnection(): IConnection;

    protected function setupDatabase(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $schema->create('temp', function ($table) {
            $table->id();
            $table->string('title');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        $users = [
            ['name' => 'John Doe', 'metadata' => json_encode([
                'age' => 25,
                'city' => 'New York',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true
                ],
                'skills' => ['php', 'javascript', 'python']
            ])],
            ['name' => 'Jane Smith', 'metadata' => json_encode([
                'age' => 30,
                'city' => 'London',
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => false
                ],
                'skills' => ['java', 'kotlin', 'php']
            ])],
            ['name' => 'Bob Johnson', 'metadata' => json_encode([
                'age' => 35,
                'city' => 'Paris',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true
                ],
                'skills' => ['python', 'go', 'rust']
            ])],
            ['name' => 'Alice Brown', 'metadata' => json_encode([
                'age' => 28,
                'city' => 'Berlin',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true
                ],
                'skills' => ['javascript', 'typescript', 'node']
            ])],
            ['name' => 'Charlie Wilson', 'metadata' => json_encode([
                'age' => 40,
                'city' => 'Madrid',
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => false
                ],
                'skills' => ['php', 'ruby', 'c#']
            ])],
            ['name' => 'Diana Prince', 'metadata' => null],
        ];

        foreach ($users as $user) {
            $this->users()->insert($user);
        }
    }

    protected function cleanupDatabase(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->dropIfExists('users');
        $schema->dropIfExists('temp');
    }

    protected function users(): IQueryBuilder
    {
        return $this->connection->table('users');
    }

    protected function temp(): IQueryBuilder
    {
        return $this->connection->table('temp');
    }



    public function testWhereJson(): void
    {
        $result = $this->users()
            ->where('metadata->age', 25)
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals('John Doe', $result->name);

        $result = $this->users()
            ->where('metadata->age', '>', 30)
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals('Bob Johnson', $result[0]->name);
        $this->assertEquals('Charlie Wilson', $result[1]->name);


        $result = $this->users()
            ->where('metadata->preferences->theme', 'dark')
            ->get();

        $this->assertCount(3, $result); // John, Bob, Alice
        foreach ($result as $user) {
            $metadata = json_decode($user->metadata, true);
            $this->assertEquals('dark', $metadata['preferences']['theme']);
        }

        $result = $this->users()
            ->where('metadata->city', null)
            ->first();

        $this->assertEquals('Diana Prince', $result->name);
    }

    public function testWhereJsonContains(): void
    {
        // Check single value
        $result = $this->users()
            ->whereJsonContains('metadata->skills', 'php')
            ->get();

        $this->assertCount(3, $result); // John, Jane, Charlie
        foreach ($result as $user) {
            $metadata = json_decode($user->metadata, true);
            $this->assertContains('php', $metadata['skills']);
        }

        // Check multiple values
        $result = $this->users()
            ->whereJsonContains('metadata->skills', ['php', 'javascript'])
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]->name);

        // OR condition
        $result = $this->users()
            ->whereJsonContains('metadata->skills', 'python')
            ->orWhereJsonContains('metadata->skills', 'java')
            ->get();

        $this->assertCount(3, $result); // John (python), Bob (python), Jane (java)

        // NOT condition
        $result = $this->users()
            ->whereJsonNotContains('metadata->skills', 'php')
            ->get();

        $this->assertCount(3, $result); // Bob, Alice, Diana
        foreach ($result as $user) {
            if ($user->metadata) {
                $metadata = json_decode($user->metadata, true);
                $this->assertNotContains('php', $metadata['skills']);
            }
        }

        // With OR and NOT
        $result = $this->users()
            ->whereJsonNotContains('metadata->skills', 'php')
            ->orWhereJsonContains('metadata->skills', 'javascript')
            ->get();

        $this->assertCount(4, $result);
    }

    public function testWhereJsonOverlaps(): void
    {
        // Check with list values
        $result = $this->users()
            ->whereJsonOverlaps('metadata->skills', ['php', 'python'])
            ->get();

        $this->assertCount(4, $result); // John (php), Bob (python), Jane (php), Charlie (php)

        // Check with single value
        $result = $this->users()
            ->whereJsonOverlaps('metadata->skills', ['rust'])
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Bob Johnson', $result[0]->name);

        // OR condition
        $result = $this->users()
            ->whereJsonOverlaps('metadata->skills', ['php'])
            ->orWhereJsonOverlaps('metadata->skills', ['rust'])
            ->get();

        $this->assertCount(4, $result); // John, Jane, Charlie (php) + Bob (rust)

        // NOT condition
        $result = $this->users()
            ->whereJsonNotOverlaps('metadata->skills', ['php'])
            ->get();

        $this->assertCount(3, $result); // Bob, Alice, Diana
        foreach ($result as $user) {
            if ($user->metadata) {
                $metadata = json_decode($user->metadata, true);
                $this->assertNotContains('php', $metadata['skills']);
            }
        }
    }

    public function testWhereJsonLength(): void
    {
        $result = $this->users()
            ->whereJsonLength('metadata->skills', 3)
            ->get();
        $this->assertCount(5, $result);

        $result = $this->users()
            ->whereJsonLength('metadata->skills', '>', 3)
            ->get();
        $this->assertCount(0, $result);
    }

    public function testWhereJsonHasKey(): void
    {
        $result = $this->users()
            ->whereJsonHasKey('metadata->age')
            ->get();
        $this->assertCount(5, $result);


        $result = $this->users()
            ->whereJsonHasKey('metadata->preferences->theme')
            ->get();
        $this->assertCount(5, $result);


        $result = $this->users()
            ->whereJsonHasNotKey('metadata->age')
            ->get();
        $this->assertCount(1, $result);
        $this->assertEquals('Diana Prince', $result[0]->name);
    }

    public function testWhereJsonType(): void
    {
        $result = $this->users()
            ->whereJsonType('metadata', 'object')
            ->get();
        $this->assertCount(5, $result);

        $result = $this->users()
            ->whereJsonType('metadata', 'null')
            ->get();
        $this->assertCount(1, $result);
        $this->assertEquals('Diana Prince', $result[0]->name);


        $result = $this->users()
            ->whereJsonType('metadata->skills', 'array')
            ->get();
        $this->assertCount(5, $result);

        $result = $this->users()
            ->whereJsonNotType('metadata', 'object')
            ->get();

        $this->assertCount(1, $result);
    }
}