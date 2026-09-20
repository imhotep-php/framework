<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query;

use Imhotep\Tests\Database\Query\Traits\HasQueryFromTest;
use Imhotep\Tests\Database\Query\Traits\HasQueryGroupTest;
use Imhotep\Tests\Database\Query\Traits\HasQueryLockTest;
use Imhotep\Tests\Database\Query\Traits\HasQueryOffsetAndLimitTest;
use Imhotep\Tests\Database\Query\Traits\HasUnionTest;
use Imhotep\Tests\Database\Query\Traits\HasWhereBasicTest;
use DateTime;
use Imhotep\Contracts\Database\IConnection;
use Imhotep\Contracts\Database\MultipleRecordsFoundException;
use Imhotep\Contracts\Database\IQueryBuilder;
use Imhotep\Contracts\Database\RecordNotFoundException;
use Imhotep\Database\Expression;
use Imhotep\Database\Model\Model;
use PHPUnit\Framework\TestCase;

abstract class QueryTestCases extends TestCase
{
    use HasQueryFromTest,
        HasWhereBasicTest,
        HasUnionTest,
        HasQueryLockTest,
        HasQueryOffsetAndLimitTest,
        HasQueryGroupTest;

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

        $this->connection->disconnect();

        parent::tearDown();
    }

    abstract protected function createConnection(): IConnection;

    abstract protected function getType(): string;

    protected function setupDatabase(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->int('age')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->bool('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $schema->create('posts', function ($table) {
            $table->id();
            $table->bigInt('user_id')->unsigned();
            $table->string('title');
            $table->text('content');
            $table->int('views')->default(0);
            $table->timestamps();
        });

        $schema->create('temp', function ($table) {
            $table->id();
            $table->string('title');
            $table->int('views')->default(0);
            $table->timestamps();
        });

        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 25, 'salary' => 50000.00, 'active' => true],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 30, 'salary' => 60000.00, 'active' => true],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 35, 'salary' => 55000.00, 'active' => false],
            ['name' => 'Alice Brown', 'email' => 'alice@example.com', 'age' => 28, 'salary' => 52000.00, 'active' => true],
            ['name' => 'Charlie Wilson', 'email' => 'charlie@example.com', 'age' => 40, 'salary' => 70000.00, 'active' => true],
            ['name' => 'Diana Prince', 'email' => 'diana@example.com', 'age' => null, 'salary' => null, 'active' => true],
        ];

        foreach ($users as $user) {
            $this->users()->insert($user);
        }

        $posts = [
            ['user_id' => 1, 'title' => 'First Post', 'content' => 'Content of first post', 'views' => 100],
            ['user_id' => 1, 'title' => 'Second Post', 'content' => 'Content of second post', 'views' => 50],
            ['user_id' => 2, 'title' => 'Third Post', 'content' => 'Content of third post', 'views' => 200],
            ['user_id' => 3, 'title' => 'Fourth Post', 'content' => 'Content of fourth post', 'views' => 75],
            ['user_id' => 4, 'title' => 'Fifth Post', 'content' => 'Content of fifth post', 'views' => 150],
            ['user_id' => 5, 'title' => 'Sixth Post', 'content' => 'Content of sixth post', 'views' => 300],
            ['user_id' => 5, 'title' => 'Seventh Post', 'content' => 'Content of seventh post', 'views' => 250],
        ];

        foreach ($posts as $post) {
            $this->posts()->insert($post);
        }
    }

    protected function cleanupDatabase(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->dropIfExists('posts');
        $schema->dropIfExists('users');
        $schema->dropIfExists('temp');
    }

    protected function builder(): IQueryBuilder
    {
        return $this->connection->getQueryBuilder();
    }

    protected function users(): IQueryBuilder
    {
        return $this->connection->table('users');
    }

    protected function posts(): IQueryBuilder
    {
        return $this->connection->table('posts');
    }

    protected function temp(): IQueryBuilder
    {
        return $this->connection->table('temp');
    }

    protected function fixSqlQuote(string $sql): string
    {
        return str_replace(['`'], '"', $sql);
    }


    public function testExistsMethod(): void
    {
        $exists = $this->users()
            ->where('id', 1)
            ->exists();
        $this->assertTrue($exists);


        $exists = $this->users()
            ->where('id', 999)
            ->exists();
        $this->assertFalse($exists);
    }

    public function testGetMethod(): void
    {
        $result = $this->users()->get();
        $this->assertIsArray($result);
        $this->assertCount(6, $result);
        $this->assertObjectHasProperty('name', $result[0]);
        $this->assertObjectHasProperty('email', $result[0]);

        // C пустым результатом
        $result = $this->users()
            ->where('id', 999)
            ->get();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFirstMethod(): void
    {
        $result = $this->users()->first();
        $this->assertIsObject($result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);

        // С условием
        $result = $this->users()
            ->where('email', 'jane@example.com')
            ->first();
        $this->assertEquals('Jane Smith', $result->name);

        // Пустой результат
        $result = $this->users()
            ->where('email', 'nonexistent@example.com')
            ->first();
        $this->assertNull($result);
    }

    public function testFirstOrMethod(): void
    {
        // Существующая запись
        $result = $this->users()
            ->where('email', 'john@example.com')
            ->firstOr(['*'], function () {
                return 'Default User';
            });
        $this->assertIsObject($result);
        $this->assertEquals('John Doe', $result->name);

        // Несуществующая запись с callback
        $result = $this->users()
            ->where('email', 'nonexistent@example.com')
            ->firstOr(['*'], function () {
                return 'Default User';
            });
        $this->assertEquals('Default User', $result);

        // С короткой записью
        $result = $this->users()
            ->where('email', 'nonexistent@example.com')
            ->firstOr(function () {
                return 'Default Value';
            });
        $this->assertEquals('Default Value', $result);
    }

    public function testFirstOrFailMethod(): void
    {
        // Существующая запись
        $result = $this->users()
            ->where('email', 'john@example.com')
            ->firstOrFail();
        $this->assertEquals('John Doe', $result->name);

        // Несуществующая запись
        $this->expectException(RecordNotFoundException::class);
        $this->users()
            ->where('email', 'nonexistent@example.com')
            ->firstOrFail();
    }

    public function testFirstOrFailMethodWithCustomMessage(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('Custom error message');

        $this->users()
            ->where('email', 'nonexistent@example.com')
            ->firstOrFail(message: 'Custom error message');
    }

    public function testSoleMethod(): void
    {
        // Ровно одна запись
        $result = $this->users()
            ->where('email', 'john@example.com')
            ->sole();
        $this->assertEquals('John Doe', $result->name);

        // Нет записей
        $this->expectException(RecordNotFoundException::class);
        $this->users()
            ->where('email', 'nonexistent@example.com')
            ->sole();
    }

    public function testSoleMethodWithMultipleRecords(): void
    {
        $this->expectException(MultipleRecordsFoundException::class);
        $this->expectExceptionMessage('Expected 1 record, got 2.');

        $this->users()
            ->where('active', true)
            ->sole();
    }

    public function testSoleValueMethod(): void
    {
        // Ровно одна запись
        $name = $this->users()
            ->where('email', 'john@example.com')
            ->soleValue('name');
        $this->assertEquals('John Doe', $name);

        // Ровно одна запись с NULL значением
        $age = $this->users()
            ->where('email', 'diana@example.com')
            ->soleValue('age');
        $this->assertNull($age);

        // Нет записей
        $this->expectException(RecordNotFoundException::class);
        $this->users()
            ->where('email', 'nonexistent@example.com')
            ->soleValue('name');

        // Несколько записей
        $this->expectException(MultipleRecordsFoundException::class);
        $this->users()
            ->where('active', true)
            ->soleValue('email');
    }

    public function testPluckMethod(): void
    {
        // Простой pluck
        $names = $this->users()
            ->orderBy('id')
            ->pluck('name');

        $this->assertEquals([
            'John Doe',
            'Jane Smith',
            'Bob Johnson',
            'Alice Brown',
            'Charlie Wilson',
            'Diana Prince'
        ], $names);

        // Pluck с ключом
        $names = $this->users()
            ->orderBy('id')
            ->pluck('name', 'id');

        $this->assertEquals([
            1 => 'John Doe',
            2 => 'Jane Smith',
            3 => 'Bob Johnson',
            4 => 'Alice Brown',
            5 => 'Charlie Wilson',
            6 => 'Diana Prince'
        ], $names);

        // Pluck с условием
        $emails = $this->users()
            ->where('active', true)
            ->whereNotNull('email')
            ->orderBy('id')
            ->pluck('email', 'id');

        $this->assertCount(5, $emails);
        $this->assertEquals('john@example.com', $emails[1]);

        // Pluck с NULL значением
        $ages = $this->users()
            ->orderBy('id')
            ->pluck('age', 'name');

        $this->assertArrayHasKey('Diana Prince', $ages);
        $this->assertNull($ages['Diana Prince']);

        // Pluck с пустым результатом
        $result = $this->users()
            ->where('id', 999)
            ->pluck('name');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindMethod(): void
    {
        // Найденная запись
        $user = $this->users()->find(1);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);

        // С выборкой колонок
        $user = $this->users()
            ->find(1, ['name', 'email']);

        $this->assertObjectHasProperty('name', $user);
        $this->assertObjectHasProperty('email', $user);
        $this->assertObjectNotHasProperty('age', $user);

        // Несуществующая запись
        $user = $this->users()->find(999);
        $this->assertNull($user);
    }

    public function testFindOrMethod(): void
    {
        // Найденная запись
        $user = $this->users()
            ->findOr(1, ['*'], function () {
                return (object)['name' => 'Default User'];
            });

        $this->assertEquals('John Doe', $user->name);

        // Не найденная запись с callback
        $user = $this->users()
            ->findOr(999, ['*'], function () {
                return (object)['name' => 'Default User'];
            });

        $this->assertEquals('Default User', $user->name);

        // С короткой записью
        $user = $this->users()
            ->findOr(999, function () {
                return (object)['name' => 'Default User'];
            });

        $this->assertEquals('Default User', $user->name);
    }

    public function testFindOrFailMethod(): void
    {
        // Найденная запись
        $user = $this->users()->findOrFail(1);

        $this->assertEquals('John Doe', $user->name);

        // С выборкой колонок
        $user = $this->users()
            ->findOrFail(1, ['name', 'email']);

        $this->assertObjectHasProperty('name', $user);
        $this->assertObjectHasProperty('email', $user);

        // Не найденная запись
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('Record (ID: 999) not found.');

        $this->users()->findOrFail(999);
    }

    public function testFindOrFailMethodWithCustomMessage(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('Custom error message');

        $this->users()->findOrFail(999, message: 'Custom error message');
    }

    public function testValueMethod(): void
    {
        // Существующее значение
        $name = $this->users()
            ->where('email', 'john@example.com')
            ->value('name');

        $this->assertEquals('John Doe', $name);

        // NULL значение
        $age = $this->users()
            ->where('email', 'diana@example.com')
            ->value('age');

        $this->assertNull($age);

        // Несуществующее значение с default
        $name = $this->users()
            ->where('email', 'nonexistent@example.com')
            ->value('name', 'Default Name');

        $this->assertEquals('Default Name', $name);

        // Несуществующее значение без default
        $name = $this->users()
            ->where('email', 'nonexistent@example.com')
            ->value('name');

        $this->assertNull($name);
    }

    public function testRawValueMethod(): void
    {
        // COUNT
        $count = $this->users()
            ->rawValue('COUNT(*)');

        $this->assertEquals(6, $count);

        // SUM
        $sum = $this->connection
            ->table('posts')
            ->rawValue('SUM(views)');

        $this->assertEquals(1125, $sum);

        // AVG
        $avg = $this->users()
            ->whereNotNull('age')
            ->rawValue('AVG(age)');

        $this->assertEquals(31.6, round((float)$avg, 1));

        // MAX
        $max = $this->users()
            ->rawValue('MAX(age)');

        $this->assertEquals(40, $max);

        // С привязками
        $count = $this->users()
            ->rawValue('COUNT(CASE WHEN age > ? THEN 1 END)', [30]);

        $this->assertEquals(2, $count);

        // Несуществующее выражение с default
        $result = $this->users()
            ->where('id', 999)
            ->rawValue('COUNT(*)', [], 0);

        $this->assertEquals(0, $result);
    }

    public function testGetWithModelClass(): void
    {
        $result = $this->users()->setModel(TestModel::class)->get();

        $this->assertIsArray($result);
        $this->assertInstanceOf(TestModel::class, $result[0]);
    }

    public function testFirstWithSelectColumns(): void
    {
        $result = $this->users()
            ->first(['name', 'email']);

        $this->assertObjectHasProperty('name', $result);
        $this->assertObjectHasProperty('email', $result);
        $this->assertObjectNotHasProperty('age', $result);
        $this->assertObjectNotHasProperty('salary', $result);
    }

    public function testPluckWithDuplicateValues(): void
    {
        // Добавляем дублирующиеся значения
        $this->users()->insert([
            'name' => 'John Doe',
            'email' => 'john2@example.com',
            'age' => 25,
        ]);

        $names = $this->users()
            ->where('name', 'John Doe')
            ->pluck('name');

        $this->assertContains('John Doe', $names);
        $this->assertCount(2, $names);
    }


    public function testCountMethod(): void
    {
        // Базовый подсчет всех записей
        $count = $this->users()->count();
        $this->assertEquals(6, $count);
        $this->assertIsInt($count);

        // Подсчет с условием WHERE
        $count = $this->users()
            ->where('active', true)
            ->count();
        $this->assertEquals(5, $count);

        // Подсчет с DISTINCT
        $count = $this->users()
            ->distinct()
            ->count('age');
        $this->assertEquals(5, $count);

        // Подсчет с несколькими условиями
        $count = $this->users()
            ->where('age', '>=', 30)
            ->where('salary', '>', 50000)
            ->count();
        $this->assertEquals(3, $count);

        // Подсчет с NULL условием
        $count = $this->users()
            ->whereNull('age')
            ->count();
        $this->assertEquals(1, $count);

        // Пустой результат
        $count = $this->users()
            ->where('id', 999)
            ->count();
        $this->assertEquals(0, $count);
    }

    public function testMinMethod(): void
    {
        $min = $this->users()
            ->min('age');

        $this->assertEquals(25, $min);
    }

    public function testMaxMethod(): void
    {
        $max = $this->users()
            ->max('age');

        $this->assertEquals(40, $max);
    }

    public function testAvgMethod(): void
    {
        $avg = $this->users()
            ->avg('age');

        $this->assertEquals(31.6, round((float)$avg, 1));
    }

    public function testSumMethod(): void
    {
        $sum = $this->connection
            ->table('posts')
            ->sum('views');

        $this->assertEquals(1125, $sum);
    }


    public function testWhere(): void
    {
        // where('column', 'value')
        $result = $this->users()
            ->where('name', 'John Doe')
            ->first();
        $this->assertEquals('john@example.com', $result->email);
        $this->assertEquals(25, $result->age);

        // where('column', 'operator', 'value')
        $result = $this->users()
            ->where('age', '>=', 30)
            ->get();
        // Jane(30), Bob(35), Charlie(40)
        $this->assertCount(3, $result);

        // where('column operator value')
        $result = $this->users()
            ->where('age >= 30')
            ->get();
        $this->assertCount(3, $result);

        // Используем whereRaw для сложных выражений
        $result = $this->users()
            ->whereRaw('age > ?', [30])
            ->get();
        // Bob(35), Charlie(40)
        $this->assertCount(2, $result);

        // where с Expression
        $expr = new Expression('UPPER(name)');
        $result = $this->users()
            ->where($expr, '=', 'JOHN DOE')
            ->first();

        $this->assertEquals('John Doe', $result->name);
    }

    public function testOrWhere(): void
    {
        $result = $this->users()
            ->where('name', 'John Doe')
            ->orWhere('name', 'Jane Smith')
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals('Jane Smith', $result[1]->name);
    }

    public function testWhereNot(): void
    {
        // whereNot('column', 'value') -> NOT (column = value)
        $result = $this->users()
            ->whereNot('name', 'John Doe')
            ->orderBy('id')
            ->get();

        $this->assertCount(5, $result);
        $this->assertEquals('Jane Smith', $result[0]->name);
        $this->assertEquals('Diana Prince', $result[4]->name);
        foreach ($result as $item) {
            $this->assertNotEquals('John Doe', $item->name);
        }

        // whereNot('column operator value') -> NOT (column operator value)
        $result = $this->users()
            ->whereNot('age >= 30')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals(25, $result[0]->age);
        $this->assertEquals('Alice Brown', $result[1]->name);
        $this->assertEquals(28, $result[1]->age);


        // whereNot Null
        $result = $this->users()
            ->whereNot('age')
            ->get();
        $this->assertCount(5, $result);

        // whereNot со вложенной группой (NOT (A AND B))
        $result = $this->users()
            ->whereNot(function ($query) {
                $query->where('age', '>=', 30)
                    ->where('salary', '>', 55000);
            })
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals('Bob Johnson', $result[1]->name);
        $this->assertEquals('Alice Brown', $result[2]->name);

        // whereNot со вложенной группой (NOT (A OR B))
        $result = $this->users()
            ->whereNot(function ($query) {
                $query->where('name', 'John Doe')
                    ->orWhere('name', 'Jane Smith');
            })
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $result);
        foreach ($result as $item) {
            $this->assertNotEquals('John Doe', $item->name);
            $this->assertNotEquals('Jane Smith', $item->name);
        }


        // whereNot + orWhereNot -> NOT (name='John Doe') OR NOT (name='Jane Smith')
        // Вернет все записи
        $result = $this->users()
            ->whereNot('name', 'John Doe')
            ->orWhereNot('name', 'Jane Smith')
            ->orderBy('id')
            ->get();
        $this->assertCount(6, $result);
    }

    public function testWhereAll(): void
    {
        $result = $this->users()->whereAll(
            ['email', 'name'],
            'like',
            '%ob%'
        )->get();

        $this->assertCount(1, $result);
        $this->assertEquals('bob@example.com', $result[0]->email);

        $result = $this->users()->whereAll(
            ['email', 'name'],
            'like',
            '%ob%'
        )->orWhereAll(
            ['email', 'name'],
            'like',
            '%harlie%'
        )->get();

        $this->assertCount(2, $result);
        $this->assertEquals('bob@example.com', $result[0]->email);
        $this->assertEquals('charlie@example.com', $result[1]->email);
    }

    public function testWhereAny(): void
    {
        $result = $this->users()->whereAny(
            ['email', 'name'],
            'like',
            '%ob%'
        )->get();

        $this->assertCount(1, $result);
        $this->assertEquals('bob@example.com', $result[0]->email);


        $result = $this->users()->whereAny(
            ['email', 'name'],
            'like',
            '%ob%'
        )->orWhereAny(
            ['email', 'name'],
            'like',
            '%harlie%'
        )->get();

        $this->assertCount(2, $result);
        $this->assertEquals('bob@example.com', $result[0]->email);
        $this->assertEquals('charlie@example.com', $result[1]->email);

    }

    public function testWhereNone(): void
    {
        $result = $this->users()
            ->whereNone(['email', 'name'], 'like', '%ob%')
            ->orderBy('id')
            ->get();

        $this->assertCount(5, $result);
        foreach ($result as $item) {
            $this->assertStringNotContainsStringIgnoringCase('ob', $item->email);
            $this->assertStringNotContainsStringIgnoringCase('ob', $item->name);
        }
    }

    public function testNestedWhere(): void
    {
        // Вложенные условия с AND
        $result = $this->users()
            ->where(function($query) {
                $query->where('age', '>=', 30)
                    ->where('salary', '>', 55000);
            })
            ->get();

        $this->assertCount(2, $result); // Jane(60000), Charlie(70000)

        // Вложенные условия с OR
        $result = $this->users()
            ->where(function($query) {
                $query->where('name', 'John Doe')
                    ->orWhere('name', 'Jane Smith');
            })
            ->where('active', true)
            ->get();

        $this->assertCount(2, $result);

        // Комбинация WHERE и OR внутри вложенного
        $result = $this->users()
            ->where('active', true)
            ->where(function($query) {
                $query->where('age', '<', 30)
                    ->orWhere('salary', '>', 60000);
            })
            ->get();

        $this->assertCount(3, $result); // John(25), Alice(28), Charlie(70000)
    }

    public function testWhereColumn(): void
    {
        $result = $this->posts()->whereColumn('views', '<', 'user_id')->get();
        $this->assertCount(0, $result);

        $result = $this->posts()->whereColumn('views', '>', 'user_id')->get();
        $this->assertCount(7, $result);
    }

    public function testWhereNull(): void
    {
        $result = $this->users()
            ->whereNull('age')
            ->first();

        $this->assertEquals('Diana Prince', $result->name);
        $this->assertNull($result->age);

        // С несколькими колонками
        $result = $this->users()
            ->whereNull('age')
            ->whereNull('salary')
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Diana Prince', $result[0]->name);

        $result = $this->users()
            ->where('active', false)
            ->orWhereNull('age')
            ->get();

        // Все неактивные (1) + Diana с null (1) = 2
        $this->assertCount(2, $result);
    }

    public function testWhereNotNull(): void
    {
        $result = $this->users()->whereNotNull('age')->get();
        $this->assertCount(5, $result); // Все кроме Diana
    }

    public function testWhereIn(): void
    {
        $result = $this->users()
            ->whereIn('id', [1, 3, 5])
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(3, $result[1]->id);
        $this->assertEquals(5, $result[2]->id);

        // C пустым массивом
        $result = $this->users()
            ->whereIn('id', [])
            ->get();
        $this->assertEmpty($result);

        // Cо строковыми значениями
        $result = $this->users()
            ->whereIn('email', ['john@example.com', 'jane@example.com'])
            ->get();
        $this->assertCount(2, $result);

        // C подзапросом
        $result = $this->users()
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('posts')
                    ->where('views', '>', 100);
            })
            ->get();
        $this->assertCount(3, $result);
        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals(4, $result[1]->id);
        $this->assertEquals(5, $result[2]->id);
    }

    public function testWhereNotIn(): void
    {
        $result = $this->users()
            ->whereNotIn('id', [1, 3, 5])
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals(4, $result[1]->id);
        $this->assertEquals(6, $result[2]->id);

        // C пустым массивом
        $result = $this->users()
            ->whereNotIn('id', [])
            ->get();
        $this->assertCount(6, $result); // Все пользователи
    }

    public function testWhereBetween(): void
    {
        $result = $this->users()
            ->whereBetween('age', [25, 30])
            ->get();
        $this->assertCount(3, $result); // John(25), Jane(30), Alice(28)

        $result = $this->users()
            ->whereBetween('salary', [52000, 60000])
            ->get();
        $this->assertCount(3, $result); // Jane(60000), Bob(55000), Alice(52000)
    }

    public function testWhereNotBetween(): void
    {
        $result = $this->users()
            ->whereNotBetween('age', [28, 35])
            ->get();

        $this->assertCount(2, $result); // John(25) and Charlie(40)
    }


    public function testWhereLike(): void
    {
        // Поиск подстроки
        $result = $this->users()
            ->whereLike('name', '%ohn%')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals('Bob Johnson', $result[1]->name);

        // Поиск по точному совпадению
        $result = $this->users()
            ->whereLike('name', 'John Doe')
            ->first();

        $this->assertEquals('John Doe', $result->name);
    }

    public function testWhereNotLike(): void
    {
        // Исключить по подстроке
        $result = $this->users()
            ->whereNotLike('name', '%ohn%')
            ->orderBy('id')
            ->get();

        // Все кроме John Doe и Bob Johnson
        $this->assertCount(4, $result);
        $this->assertEquals('Jane Smith', $result[0]->name);
        $this->assertEquals('Alice Brown', $result[1]->name);
        $this->assertEquals('Charlie Wilson', $result[2]->name);
        $this->assertEquals('Diana Prince', $result[3]->name);

        // Исключить по точному совпадению
        $result = $this->users()
            ->whereNotLike('name', 'John Doe')
            ->get();

        $this->assertCount(5, $result);
        foreach ($result as $item) {
            $this->assertNotEquals('John Doe', $item->name);
        }
    }


    public function testWhereDate(): void
    {
        $this->temp()->insert([
            'title' => 'Date Test',
            'created_at' => '2000-01-15 10:00:00'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Date Test',
            'created_at' => '2001-01-15 10:00:00'
        ]);

        // String Date
        $result = $this->temp()
            ->whereDate('created_at','2000-01-15')
            ->first();
        $this->assertEquals('Date Test', $result->title);


        // Integer Timestamp
        $result = $this->temp()
            ->whereDate('created_at', strtotime('2000-01-15'))
            ->first();
        $this->assertEquals('Date Test', $result->title);


        // String Timestamp
        $result = $this->temp()
            ->whereDate('created_at', (string)strtotime('2000-01-15'))
            ->first();
        $this->assertEquals('Date Test', $result->title);


        // DateTimeInterface
        $result = $this->temp()
            ->whereDate('created_at', new DateTime('2000-01-15'))
            ->first();
        $this->assertEquals('Date Test', $result->title);

        // Greater than operator
        $result = $this->temp()
            ->whereDate('created_at', '>', '2000-01-14')
            ->first();
        $this->assertEquals('Date Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereDate('created_at', '<', '2000-01-16')
            ->first();
        $this->assertEquals('Date Test', $result->title);
    }

    public function testWhereYear(): void
    {
        $this->temp()->insert([
            'title' => 'Year Test',
            'created_at' => '2000-01-15 10:00:00'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Year Test',
            'created_at' => '2001-01-15 10:00:00'
        ]);

        // Integer year
        $result = $this->temp()
            ->whereYear('created_at', 2000)
            ->first();
        $this->assertEquals('Year Test', $result?->title);

        // String year
        $result = $this->temp()
            ->whereYear('created_at', '2000')
            ->first();
        $this->assertEquals('Year Test', $result?->title);

        // DateTimeInterface
        $result = $this->temp()
            ->whereYear('created_at', new DateTime('2000-01-01'))
            ->first();
        $this->assertEquals('Year Test', $result?->title);

        // Greater than operator
        $result = $this->temp()
            ->whereYear('created_at', '>', 1999)
            ->first();
        $this->assertEquals('Year Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereYear('created_at', '<', 2001)
            ->first();
        $this->assertEquals('Year Test', $result->title);
    }

    public function testWhereMonth(): void
    {
        $this->temp()->insert([
            'title' => 'Not Used Month Test',
            'created_at' => '2001-01-15 10:00:00'
        ]);
        $this->temp()->insert([
            'title' => 'Month Test',
            'created_at' => '2000-05-15 10:00:00'
        ]);

        // Integer month
        $result = $this->temp()
            ->whereMonth('created_at', 5)
            ->first();
        $this->assertEquals('Month Test', $result?->title);

        // String month
        $result = $this->temp()
            ->whereMonth('created_at', '05')
            ->first();
        $this->assertEquals('Month Test', $result?->title);

        // DateTimeInterface
        $date = new DateTime('2026-05-01');
        $result = $this->temp()
            ->whereMonth('created_at', $date)
            ->first();
        $this->assertEquals('Month Test', $result?->title);

        // Greater than operator
        $result = $this->temp()
            ->whereYear('created_at', 2000)
            ->whereMonth('created_at', '>', 4)
            ->first();
        $this->assertEquals('Month Test', $result?->title);

        // Less than operator
        $result = $this->temp()
            ->whereYear('created_at', 2000)
            ->whereMonth('created_at', '<', 6)
            ->first();
        $this->assertEquals('Month Test', $result?->title);
    }

    public function testWhereDay(): void
    {
        $this->temp()->insert([
            'title' => 'Day Test',
            'created_at' => '2026-05-20 15:30:00'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Day Test',
            'created_at' => '2026-05-21 15:30:00'
        ]);

        // Integer day
        $result = $this->temp()
            ->whereDay('created_at', 20)
            ->first();
        $this->assertEquals('Day Test', $result->title);

        // String day
        $result = $this->temp()
            ->whereDay('created_at', '20')
            ->first();
        $this->assertEquals('Day Test', $result->title);

        // DateTimeInterface
        $result = $this->temp()
            ->whereDay('created_at', new DateTime('2026-05-20'))
            ->first();
        $this->assertEquals('Day Test', $result->title);

        // Greater than operator
        $result = $this->temp()
            ->whereDay('created_at', '>', 19)
            ->first();
        $this->assertEquals('Day Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereDay('created_at', '<', 21)
            ->first();
        $this->assertEquals('Day Test', $result->title);
    }

    public function testWhereTime(): void
    {
        $this->temp()->insert([
            'title' => 'Time Test',
            'created_at' => '2026-05-20 15:30:45'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Time Test',
            'created_at' => '2026-05-20 16:30:45'
        ]);

        // String time (H:i:s)
        $result = $this->temp()
            ->whereTime('created_at', '15:30:45')
            ->first();
        $this->assertEquals('Time Test', $result->title);

        // String time (H:i)
        $result = $this->temp()
            ->whereTime('created_at',  '15:30')
            ->first();
        $this->assertEquals('Time Test', $result->title);

        // DateTimeInterface
        $date = new \DateTime('2026-05-20 15:30:45');
        $result = $this->temp()
            ->whereTime('created_at', $date)
            ->first();
        $this->assertEquals('Time Test', $result->title);

        // Greater than operator
        $result = $this->temp()
            ->whereTime('created_at', '>', '15:30:00')
            ->first();
        $this->assertEquals('Time Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereTime('created_at', '<', '15:31:00')
            ->first();
        $this->assertEquals('Time Test', $result->title);

        // Between times
        $result = $this->temp()
            ->whereTime('created_at', '>=', '15:30:00')
            ->whereTime('created_at', '<=', '15:31:00')
            ->first();
        $this->assertEquals('Time Test', $result->title);
    }

    public function testWhereHour(): void
    {
        $this->temp()->insert([
            'title' => 'Hour Test',
            'created_at' => '2026-05-20 15:30:45'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Hour Test',
            'created_at' => '2026-05-20 16:30:45'
        ]);

        // Integer hour
        $result = $this->temp()
            ->whereHour('created_at', 15)
            ->first();
        $this->assertEquals('Hour Test', $result->title);

        // String hour
        $result = $this->temp()
            ->whereHour('created_at', '15')
            ->first();
        $this->assertEquals('Hour Test', $result->title);

        // DateTimeInterface
        $result = $this->temp()
            ->whereHour('created_at', new DateTime('2026-05-20 15:00:00'))
            ->first();
        $this->assertEquals('Hour Test', $result->title);

        // Greater than operator
        $result = $this->temp()
            ->whereHour('created_at', '>', 14)
            ->first();
        $this->assertEquals('Hour Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereHour('created_at', '<', 16)
            ->first();
        $this->assertEquals('Hour Test', $result->title);

        // Between hours
        $result = $this->temp()
            ->whereHour('created_at', '>=', 15)
            ->whereHour('created_at', '<=', 16)
            ->first();
        $this->assertEquals('Hour Test', $result->title);
    }

    public function testWhereMinute(): void
    {
        $this->temp()->insert([
            'title' => 'Minute Test',
            'created_at' => '2026-05-20 15:30:45'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Minute Test',
            'created_at' => '2026-05-20 15:31:45'
        ]);

        // Integer minute
        $result = $this->temp()
            ->whereMinute('created_at', 30)
            ->first();
        $this->assertEquals('Minute Test', $result->title);

        // String minute
        $result = $this->temp()
            ->whereMinute('created_at', '30')
            ->first();
        $this->assertEquals('Minute Test', $result->title);

        // DateTimeInterface
        $result = $this->temp()
            ->whereMinute('created_at', new DateTime('2026-05-20 15:30:00'))
            ->first();
        $this->assertEquals('Minute Test', $result->title);

        // Greater than operator
        $result = $this->temp()
            ->whereMinute('created_at', '>', 29)
            ->first();
        $this->assertEquals('Minute Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereMinute('created_at', '<', 31)
            ->first();
        $this->assertEquals('Minute Test', $result->title);

        // Between minutes
        $result = $this->temp()
            ->whereMinute('created_at', '>=', 30)
            ->whereMinute('created_at', '<=', 31)
            ->first();
        $this->assertEquals('Minute Test', $result->title);
    }

    public function testWhereSecond(): void
    {
        $this->temp()->insert([
            'title' => 'Second Test',
            'created_at' => '2026-05-20 15:30:45'
        ]);
        $this->temp()->insert([
            'title' => 'Not Used Second Test',
            'created_at' => '2026-05-20 15:30:46'
        ]);

        // Integer second
        $result = $this->temp()
            ->whereSecond('created_at', 45)
            ->first();
        $this->assertEquals('Second Test', $result->title);

        // String second
        $result = $this->temp()
            ->whereSecond('created_at', '45')
            ->first();
        $this->assertEquals('Second Test', $result->title);

        // DateTimeInterface
        $date = new \DateTime('2026-05-20 15:30:45');
        $result = $this->temp()
            ->whereSecond('created_at', $date)
            ->first();
        $this->assertEquals('Second Test', $result->title);

        // Greater than operator
        $result = $this->temp()
            ->whereSecond('created_at', '>', 44)
            ->first();
        $this->assertEquals('Second Test', $result->title);

        // Less than operator
        $result = $this->temp()
            ->whereSecond('created_at', '<', 46)
            ->first();
        $this->assertEquals('Second Test', $result->title);

        // Between seconds
        $result = $this->temp()
            ->whereSecond('created_at', '>=', 45)
            ->whereSecond('created_at', '<=', 46)
            ->first();
        $this->assertEquals('Second Test', $result->title);
    }

    public function testWhereExists(): void
    {
        $result = $this->users()
            ->whereExists(function($query) {
                $query->from('posts')
                    ->whereColumn('posts.user_id', 'users.id')
                    ->where('views', '>', 200);
            })
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Charlie Wilson', $result[0]->name);


        $result = $this->users()
            ->where('age', '<', 28)
            ->orWhereExists(function($query) {
                $query->from('posts')
                    ->whereColumn('posts.user_id', 'users.id')
                    ->where('views', '>', 150);
            })
            ->get();

        // Jane(age = 25) + John (views = 200) + Charlie (views = 250-300)
        $this->assertCount(3, $result);


        $result = $this->users()
            ->whereNotExists(function($query) {
                $query->from('posts')
                    ->whereColumn('posts.user_id', 'users.id');
            })
            ->get();

        // Diana - у нее нет постов
        $this->assertCount(1, $result);
        $this->assertEquals('Diana Prince', $result[0]->name);
    }

    public function testWhereRaw(): void
    {
        $result = $this->users()
            ->whereRaw('age >= ?', [30])
            ->get();

        $this->assertCount(3, $result); // Jane, Bob, Charlie
        $this->assertEquals(30, $result[0]->age);
        $this->assertEquals(35, $result[1]->age);
        $this->assertEquals(40, $result[2]->age);


        $result = $this->users()
            ->whereRaw('salary BETWEEN ? AND ?', [52000, 60000])
            ->get();

        $this->assertCount(3, $result); // Jane, Bob, Alice
        $this->assertEquals(60000, $result[0]->salary);
        $this->assertEquals(55000, $result[1]->salary);
        $this->assertEquals(52000, $result[2]->salary);


        $result = $this->users()
            ->whereRaw('LOWER(name) LIKE ?', ['%john%'])
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals('Bob Johnson', $result[1]->name);
    }
}

class TestModel extends Model
{

}