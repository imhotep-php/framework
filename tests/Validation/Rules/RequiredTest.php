<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Validation\Rules\Required;
use PHPUnit\Framework\TestCase;
use stdClass;

class RequiredTest extends TestCase
{
    protected Required $rule;

    protected function setUp(): void
    {
        $this->rule = new Required;
    }

    public function test_valids()
    {
        $this->assertTrue($this->rule->check('foo'));
        $this->assertTrue($this->rule->check([1]));
        $this->assertTrue($this->rule->check(1));
        $this->assertTrue($this->rule->check(true));
        $this->assertTrue($this->rule->check('0'));
        $this->assertTrue($this->rule->check(0));
        $this->assertTrue($this->rule->check(new stdClass));
    }

    public function test_invalids()
    {
        $this->assertFalse($this->rule->check(null));
        $this->assertFalse($this->rule->check(''));
        $this->assertFalse($this->rule->check([]));
    }
}