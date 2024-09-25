<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Validation\Rules\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_valids()
    {
        $rule = (new Email())->setParameters(['filter','idn']);

        $this->assertTrue($rule->check('imhotep@тест.рф'));
        $this->assertTrue($rule->check('imhotep@gmail.com'));
        $this->assertTrue($rule->check('imhotep@foo.bar'));
        $this->assertTrue($rule->check('imhotep1213@foo.bar.baz'));
    }

    public function test_invalids()
    {
        $rule = (new Email())->setParameters(['filter']);

        $this->assertFalse($rule->check(1));
        $this->assertFalse($rule->check('имхотеп@тест.рф')); // idn is false
        $this->assertFalse($rule->check('imho tep@gmail.com'));
        $this->assertFalse($rule->check('imhotep@gmail'));
        $this->assertFalse($rule->check('imhotep.gmail.com'));
    }

    public function test_lowercase()
    {
        $rule = (new Email())->setParameters(['lower']);

        $this->assertSame("imhotep@gmail.com", $rule->modifyValue("ImhoTep@gmail.com"));
    }

    public function test_mx()
    {
        $rule = (new Email())->setParameters(['idn','dns']);

        $this->assertTrue($rule->check('imhotep@объясняем.рф'));
        $this->assertTrue($rule->check('imhotep@gmail.com'));
        $this->assertFalse($rule->check('imhotep@пример.рф'));
        $this->assertFalse($rule->check('imhotep@foo.bar'));
    }
}