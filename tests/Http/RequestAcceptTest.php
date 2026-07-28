<?php

namespace Imhotep\Tests\Http;

use Imhotep\Http\Request;
use Imhotep\Http\UploadedFile;

class RequestAcceptTest extends RequestTest
{
    public function testGetAcceptableTypes()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8'
        ]);

        $types = $request->getAcceptableTypes();
        $this->assertEquals(['text/html', 'application/xhtml+xml', 'application/xml', '*/*'], $types);

        // Test caching
        $types2 = $request->getAcceptableTypes();
        $this->assertSame($types, $types2);

        // Test empty accept header
        $request = $this->createRequest();
        $this->assertEquals([], $request->getAcceptableTypes());

        // Test with charset
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html; charset=utf-8'
        ]);
        $this->assertEquals(['text/html'], $request->getAcceptableTypes());
    }

    public function testAccepts()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html, application/json'
        ]);

        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('application/json'));
        $this->assertFalse($request->accepts('application/xml'));
        $this->assertTrue($request->accepts(['text/html', 'application/xml']));
        $this->assertTrue($request->accepts('text/html', 'application/xml'));

        // Test wildcard
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => '*/*'
        ]);
        $this->assertTrue($request->accepts('anything'));

        // Test type/* pattern
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/*'
        ]);
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/xml'));
        $this->assertFalse($request->accepts('text/html'));

        // Test +json pattern
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/ld+json'
        ]);
        $this->assertTrue($request->accepts('application/ld+json'));
    }

    public function testAcceptsAny()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => '*/*'
        ]);
        $this->assertTrue($request->acceptsAny());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => '*'
        ]);
        $this->assertTrue($request->acceptsAny());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html'
        ]);
        $this->assertFalse($request->acceptsAny());

        $request = $this->createRequest();
        $this->assertTrue($request->acceptsAny());
    }

    public function testAcceptsJson()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertTrue($request->acceptsJson());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html, application/json'
        ]);
        $this->assertTrue($request->acceptsJson());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html'
        ]);
        $this->assertFalse($request->acceptsJson());
    }

    public function testAcceptsHtml()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html'
        ]);
        $this->assertTrue($request->acceptsHtml());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertFalse($request->acceptsHtml());
    }

    public function testFormat()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertEquals('json', $request->format());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html'
        ]);
        $this->assertEquals('html', $request->format());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/xml'
        ]);
        $this->assertEquals('xml', $request->format());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'unknown/type'
        ]);
        $this->assertEquals('html', $request->format()); // Default

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'unknown/type'
        ]);
        $this->assertEquals('json', $request->format('json')); // Custom default
    }

    public function testExpectsJson()
    {
        // AJAX request without PJAX
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => '*/*'
        ]);
        $this->assertTrue($request->expectsJson());

        // PJAX request should not expect JSON
        $request = $this->createRequest([], [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_X_PJAX' => 'true',
            'HTTP_ACCEPT' => '*/*'
        ]);
        $this->assertFalse($request->expectsJson());

        // Wants JSON
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertTrue($request->expectsJson());

        // Not AJAX and doesn't want JSON
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html'
        ]);
        $this->assertFalse($request->expectsJson());
    }

    public function testWantsJson()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);
        $this->assertTrue($request->wantsJson());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'application/ld+json'
        ]);
        $this->assertTrue($request->wantsJson());

        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT' => 'text/html'
        ]);
        $this->assertFalse($request->wantsJson());
    }

    public function testGetAcceptedLanguages()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9,fr;q=0.8,de;q=0.7'
        ]);

        $languages = $request->getAcceptedLanguages();
        $this->assertEquals(['en_us', 'en', 'fr', 'de'], $languages);

        // Test caching
        $languages2 = $request->getAcceptedLanguages();
        $this->assertSame($languages, $languages2);

        // Test with specific languages filter
        $filtered = $request->getAcceptedLanguages(['en-US', 'fr']);
        $this->assertEquals(['en_us', 'fr'], $filtered);

        // Test without quality values
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'en, fr'
        ]);
        $languages = $request->getAcceptedLanguages();
        $this->assertEquals(['en', 'fr'], $languages);

        // Test empty
        $request = $this->createRequest();
        $this->assertEquals([], $request->getAcceptedLanguages());
    }

    public function testAcceptLanguage()
    {
        $request = $this->createRequest([], [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'en-US, fr;q=0.8'
        ]);

        $this->assertTrue($request->acceptLanguage('en-US'));
        $this->assertTrue($request->acceptLanguage('en_US'));
        $this->assertTrue($request->acceptLanguage('fr'));
        $this->assertFalse($request->acceptLanguage('de'));
    }
}