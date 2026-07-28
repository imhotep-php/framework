<?php

namespace Imhotep\Tests\Http;

use Imhotep\Http\Request;
use Imhotep\Http\UploadedFile;

class RequestDataAccessTest extends RequestTest
{
    public function testServer()
    {
        $server = ['SERVER_NAME' => 'test', 'SERVER_PORT' => 80];
        $request = $this->createRequest(server: $server);

        $this->assertEquals($server, $request->server());
        $this->assertEquals('test', $request->server('SERVER_NAME'));
        $this->assertEquals(80, $request->server('SERVER_PORT'));
        $this->assertNull($request->server('NON_EXISTENT'));
        $this->assertEquals('default', $request->server('NON_EXISTENT', 'default'));
    }

    public function testHeaders()
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'TestAgent',
            'SERVER_NAME' => 'test' // Not a header
        ];

        $request = $this->createRequest(server: $server);

        $headers = $request->headers();
        $this->assertEquals(['accept' => 'application/json', 'user-agent' => 'TestAgent'], $headers);
        $this->assertEquals('application/json', $headers['accept']);
        $this->assertEquals('TestAgent', $headers['user-agent']);

        $this->assertEquals(['accept' => 'application/json'], $request->headers('ACCEPT'));
        $this->assertEquals(
            ['user-agent' => 'TestAgent', 'non-existent' => null],
            $request->headers('USER-Agent', 'NON_EXISTENT')
        );

        $this->assertEquals('TestAgent', $request->header('USER_AGENT'));
        $this->assertEquals(null, $request->header('NON_EXISTENT'));
    }

    public function testHasHeader()
    {
        $request = $this->createRequest(server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertTrue($request->hasHeader('ACCEPT'));
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertFalse($request->hasHeader('NON_EXISTENT'));
    }

    public function testHasHeaders()
    {
        $request = $this->createRequest(server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_USER_AGENT' => 'Test'
        ]);

        $this->assertTrue($request->hasHeaders(['ACCEPT', 'CONTENT_TYPE']));
        $this->assertTrue($request->hasHeaders('Accept', 'Content-Type'));
        $this->assertFalse($request->hasHeaders(['ACCEPT', 'NON_EXISTENT']));
        $this->assertFalse($request->hasHeaders('Accept', 'Non-Existent'));
    }

    public function testQuery()
    {
        $query = ['page' => 1, 'search' => 'test'];
        $request = $this->createRequest($query);

        $this->assertEquals($query, $request->query());
        $this->assertEquals(1, $request->query('page'));
        $this->assertEquals('test', $request->query('search'));
        $this->assertNull($request->query('non_existent'));
        $this->assertEquals('default', $request->query('non_existent', 'default'));
    }

    public function testPost()
    {
        $post = ['title' => 'Post Title', 'content' => 'Post Content'];
        $request = $this->createRequest([], $post);

        $this->assertEquals($post, $request->post());
        $this->assertEquals('Post Title', $request->post('title'));
        $this->assertEquals('Post Content', $request->post('content'));
    }

    public function testJson()
    {
        $jsonData = ['name' => 'John', 'age' => 30, 'card' => ['number' => 2026]];
        $request = $this->createRequest(content: json_encode($jsonData));

        $this->assertEquals($jsonData, $request->json());
        $this->assertEquals('John', $request->json('name'));
        $this->assertEquals(30, $request->json('age'));
        $this->assertEquals(2026, $request->json('card.number'));
        $this->assertNull($request->json('non_existent'));
    }

    public function testCookies()
    {
        $cookies = ['session' => 'abc123', 'theme' => 'dark'];
        $request = $this->createRequest([], [], $cookies);

        $this->assertEquals($cookies, $request->cookies());
        $this->assertEquals(['session' => 'abc123'], $request->cookies('session'));
        $this->assertEquals('abc123', $request->cookie('session'));
        $this->assertEquals(['theme' => 'dark', 'not-existent' => null], $request->cookies('theme', 'not-existent'));
        $this->assertNull($request->cookie('non_existent'));
    }

    public function testFiles()
    {
        $files = [
            'document' => $this->createMockFile('doc.pdf', 'application/pdf', tmpName: '/files/doc.pdf'),
            'avatar' => $this->createMockFile('avatar.jpg', 'image/jpeg', tmpName: 'avatar.jpg')
        ];

        $request = $this->createRequest([], [], [], $files);

        $this->assertTrue($request->hasFile('document'));
        $this->assertTrue($request->hasFile('avatar'));
        $this->assertFalse($request->hasFile('non_existent'));

        $document = $request->file('document');

        $this->assertInstanceOf(UploadedFile::class, $document);

        $this->assertEquals('doc.pdf', $document->originalName());
        $this->assertEquals('application/pdf', $document->originalMimeType());
        $this->assertEquals(1024, $document->originalSize());
        $this->assertEquals('pdf', $document->originalExtension());
        $this->assertEquals('/files/doc.pdf', $document->originalPath());

        $this->assertNull($request->file('non_existent'));
        $this->assertEquals('default', $request->file('non_existent', 'default'));
    }

    public function testAll()
    {
        $query = ['foo' => 'bar'];
        $post = ['baz' => 'qux'];
        $cookies = ['session' => 'abc123'];
        $files = ['file' => $this->createMockFile()];
        $json = ['name' => 'John', 'age' => 30];

        $request = $this->createRequest(
            query: $query,
            post: $post,
            cookies: $cookies,
            files: $files,
            content: json_encode($json)
        );

        $all = $request->all();

        $this->assertInstanceOf(UploadedFile::class, $all['file']);

        unset($all['file']);
        $this->assertEquals(array_merge($query, $post, $json), $all);
    }

    public function testInput()
    {
        $request = $this->createRequest(['q' => 'search'], ['name' => 'John']);

        $this->assertEquals(['q' => 'search', 'name' => 'John'], $request->input());
        $this->assertEquals('search', $request->input('q'));
        $this->assertEquals('John', $request->input('name'));
        $this->assertNull($request->input('non_existent'));
        $this->assertEquals('default', $request->input('non_existent', 'default'));

        // Test nested access
        $request = $this->createRequest([], ['user' => ['name' => 'John', 'age' => 30]]);
        $this->assertEquals('John', $request->input('user.name'));
        $this->assertEquals(30, $request->input('user.age'));
    }

    public function testOnly()
    {
        $request = $this->createRequest(['page' => 1], ['name' => 'John', 'age' => 30, 'email' => 'john@test.com']);

        $this->assertEquals(['name' => 'John'], $request->only('name'));
        $this->assertEquals(['name' => 'John', 'email' => 'john@test.com'], $request->only(['name', 'email']));
        $this->assertEquals(['name' => 'John', 'email' => 'john@test.com'], $request->only('name', 'email'));

        $request = $this->createRequest([], [
            'user' => ['profile' => ['name' => 'John', 'age' => 30]],
            'settings' => ['theme' => 'dark']
        ]);

        $result = $request->only('user.profile.name');
        $this->assertEquals(['user' => ['profile' => ['name' => 'John']]], $result);
    }

    public function testExcept()
    {
        $request = $this->createRequest(['page' => 1], ['name' => 'John', 'age' => 30, 'email' => 'john@test.com']);

        $result = $request->except('age');
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayNotHasKey('age', $result);

        $result = $request->except(['age', 'email']);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayNotHasKey('age', $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    public function testHas()
    {
        $request = $this->createRequest(['page' => 1], ['name' => 'John', 'age' => 30]);

        $this->assertTrue($request->has('page'));
        $this->assertTrue($request->has('name'));
        $this->assertTrue($request->has('age'));
        $this->assertTrue($request->has(['page', 'name']));
        $this->assertTrue($request->has('page', 'name'));
        $this->assertFalse($request->has('non_existent'));
        $this->assertFalse($request->has(['page', 'non_existent']));

        // Test nested
        $request = $this->createRequest([], ['user' => ['profile' => ['name' => 'John']]]);
        $this->assertTrue($request->has('user.profile.name'));
        $this->assertFalse($request->has('user.profile.age'));
    }

    public function testHasAny()
    {
        $request = $this->createRequest(['page' => 1], ['name' => 'John']);

        $this->assertTrue($request->hasAny('page'));
        $this->assertTrue($request->hasAny(['page', 'non_existent']));
        $this->assertTrue($request->hasAny('page', 'non_existent'));
        $this->assertFalse($request->hasAny('non_existent1', 'non_existent2'));
    }

    public function testWhenHas()
    {
        $request = $this->createRequest([], ['name' => 'John']);

        $callbackCalled = false;
        $defaultCalled = false;

        $request->whenHas('name', function ($value) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('John', $value);
        });

        $this->assertTrue($callbackCalled);

        $request->whenHas('non_existent', function () {}, function () use (&$defaultCalled) {
            $defaultCalled = true;
        });

        $this->assertTrue($defaultCalled);
    }

    public function testFilled()
    {
        $request = $this->createRequest([], [
            'name' => 'John',
            'empty_string' => '',
            'null_value' => null,
            'zero' => 0,
            'false_value' => false,
            'empty_array' => []
        ]);

        $this->assertTrue($request->filled('name'));
        $this->assertTrue($request->filled('zero'));
        $this->assertTrue($request->filled('false_value'));

        $this->assertFalse($request->filled('empty_string'));
        $this->assertFalse($request->filled('null_value'));
        $this->assertFalse($request->filled('empty_array'));

        $this->assertTrue($request->filled(['name', 'zero']));
        $this->assertFalse($request->filled(['name', 'empty_string']));
    }

    public function testNotFilled()
    {
        $request = $this->createRequest([], [
            'name' => 'John',
            'empty' => ''
        ]);

        $this->assertFalse($request->notFilled('name'));
        $this->assertTrue($request->notFilled('empty'));
        $this->assertTrue($request->notFilled('non_existent'));
    }

    public function testAnyFilled()
    {
        $request = $this->createRequest([], [
            'name' => 'John',
            'empty' => '',
            'another' => 'value'
        ]);

        $this->assertTrue($request->anyFilled('name'));
        $this->assertTrue($request->anyFilled(['name', 'empty']));
        $this->assertTrue($request->anyFilled('empty', 'another'));
        $this->assertFalse($request->anyFilled('empty', 'non_existent'));
    }

    public function testWhenFilled()
    {
        $request = $this->createRequest([], ['name' => 'John', 'empty' => '']);

        $filledCalled = false;
        $notFilledCalled = false;

        $request->whenFilled('name', function ($value) use (&$filledCalled) {
            $filledCalled = true;
            $this->assertEquals('John', $value);
        });

        $this->assertTrue($filledCalled);

        $request->whenFilled('empty', function () {}, function () use (&$notFilledCalled) {
            $notFilledCalled = true;
        });

        $this->assertTrue($notFilledCalled);
    }

    public function testMissing()
    {
        $request = $this->createRequest([], ['name' => 'John']);

        $this->assertFalse($request->missing('name'));
        $this->assertTrue($request->missing(['name', 'non_existent']));
        $this->assertTrue($request->missing('non_existent'));
        $this->assertTrue($request->missing(['non_existent1', 'non_existent2']));
    }

    public function testWhenMissing()
    {
        $request = $this->createRequest([], ['name' => 'John']);

        $missingCalled = false;
        $presentCalled = false;

        $request->whenMissing('non_existent', function () use (&$missingCalled) {
            $missingCalled = true;
        });

        $this->assertTrue($missingCalled);

        $request->whenMissing('name', function () {}, function () use (&$presentCalled) {
            $presentCalled = true;
        });

        $this->assertTrue($presentCalled);
    }

    public function testStringInput()
    {
        $request = $this->createRequest([], [
            'text' => 'Hello World',
            'number' => 123,
            'float' => 123.45,
            'bool' => true,
            'array' => ['a', 'b'],
            'whitespace' => '  trimmed  '
        ]);

        $this->assertEquals('Hello World', $request->string('text'));
        $this->assertEquals('123', $request->string('number'));
        $this->assertEquals('123.45', $request->string('float'));
        $this->assertEquals('1', $request->string('bool')); // true becomes '1'
        $this->assertEquals('', $request->string('array')); // array returns default
        $this->assertEquals('trimmed', $request->string('whitespace'));
        $this->assertEquals('', $request->string('non_existent'));
        $this->assertEquals('default', $request->string('non_existent', 'default'));
        $this->assertEquals('default', $request->string('non_existent', fn() => 'default'));
        $this->assertNull($request->string('non_existent', null));
    }

    public function testIntegerInput()
    {
        $request = $this->createRequest([], [
            'int' => '123',
            'negative' => '-456',
            'float' => '123.45',
            'invalid' => 'abc',
            'true' => true,
            'false' => false,
            'array' => [1, 2],
        ]);

        $this->assertEquals(123, $request->integer('int'));
        $this->assertEquals(-456, $request->integer('negative'));
        $this->assertEquals(0, $request->integer('float')); // float string returns default
        $this->assertEquals(0, $request->integer('invalid'));
        $this->assertEquals(1, $request->integer('true')); // true becomes 1
        $this->assertEquals(0, $request->integer('false')); // false becomes 0
        $this->assertEquals(0, $request->integer('array')); // array returns default
        $this->assertEquals(999, $request->integer('non_existent', 999));
        $this->assertEquals(456, $request->integer('non_existent', fn() => 456));
        $this->assertNull($request->integer('non_existent', null));
    }

    public function testFloatInput()
    {
        $request = $this->createRequest([], [
            'float' => '123.45',
            'int' => '123',
            'negative' => '-456.78',
            'invalid' => 'abc',
            'scientific' => '1.23e4',
            'true' => true,
            'false' => false,
            'array' => [1, 2],
        ]);

        $this->assertEquals(123.45, $request->float('float'));
        $this->assertEquals(123.0, $request->float('int'));
        $this->assertEquals(-456.78, $request->float('negative'));
        $this->assertEquals(0.0, $request->float('invalid'));
        $this->assertEquals(12300.0, $request->float('scientific'));
        $this->assertEquals(1.0, $request->float('true'));
        $this->assertEquals(0.0, $request->float('false'));
        $this->assertEquals(0.0, $request->float('array'));
        $this->assertEquals(999.99, $request->float('non_existent', 999.99));
        $this->assertEquals(324.34, $request->float('non_existent', fn() => 324.34));
        $this->assertNull($request->float('non_existent', null));
    }

    public function testBooleanInput()
    {
        $request = $this->createRequest([], [
            'true' => true,
            'false' => false,
            'one' => 1,
            'zero' => 0,
            'yes' => 'yes',
            'no' => 'no',
            'on' => 'on',
            'off' => 'off',
            'string_true' => 'true',
            'string_false' => 'false',
            'empty_string' => '',
            'null_value' => null
        ]);

        $this->assertTrue($request->boolean('true'));
        $this->assertFalse($request->boolean('false'));
        $this->assertTrue($request->boolean('one'));
        $this->assertFalse($request->boolean('zero'));
        $this->assertTrue($request->boolean('yes'));
        $this->assertFalse($request->boolean('no'));
        $this->assertTrue($request->boolean('on'));
        $this->assertFalse($request->boolean('off'));
        $this->assertTrue($request->boolean('string_true'));
        $this->assertFalse($request->boolean('string_false'));
        $this->assertFalse($request->boolean('empty_string'));
        $this->assertFalse($request->boolean('null_value'));
        $this->assertTrue($request->boolean('non_existent', true));
        $this->assertFalse($request->boolean('non_existent'));
        $this->assertTrue($request->boolean('non_existent', fn() => true));
        $this->assertNull($request->boolean('non_existent', null));
    }
}