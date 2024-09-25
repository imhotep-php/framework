<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Validation\Rules\Uppercase;
use PHPUnit\Framework\TestCase;

class UppercaseTest extends TestCase
{
    protected Uppercase $rule;

    protected function setUp(): void
    {
        $this->rule = new Uppercase();
    }

    public function test_valids()
    {
        $this->assertTrue($this->rule->check('USERNAME'));
        $this->assertTrue($this->rule->check('FULL NAME'));
        $this->assertTrue($this->rule->check('FULL_NAME'));
    }

    public function test_invalids()
    {
        $this->assertFalse($this->rule->check('username'));
        $this->assertFalse($this->rule->check('Username'));
        $this->assertFalse($this->rule->check('userName'));
    }
}