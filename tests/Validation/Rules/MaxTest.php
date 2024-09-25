<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Http\UploadedFile;
use Imhotep\Validation\Rules\Max;
use PHPUnit\Framework\TestCase;

class MaxTest extends TestCase
{
    protected Max $rule;

    protected function setUp(): void
    {
        $this->rule = new Max();
    }

    public function test_valids()
    {
        $this->assertTrue($this->rule->setParameters([200])->check(123));
        $this->assertTrue($this->rule->setParameters([6])->check('foobar'));
        $this->assertTrue($this->rule->setParameters([3])->check([1,2,3]));

        $this->assertTrue($this->rule->setParameters([3])->check('мин'));
        $this->assertTrue($this->rule->setParameters([4])->check('كلمة'));
        $this->assertTrue($this->rule->setParameters([3])->check('ワード'));
        $this->assertTrue($this->rule->setParameters([1])->check('字'));
    }

    public function test_invalids()
    {
        $this->assertFalse($this->rule->setParameters([5])->check('foobar'));
        $this->assertFalse($this->rule->setParameters([2])->check([1,2,3]));
        $this->assertFalse($this->rule->setParameters([100])->check(123));
    }

    public function test_uploaded_file()
    {
        $file = UploadedFile::createFrom([
            'name' => pathinfo(__FILE__, PATHINFO_BASENAME),
            'type' => 'text/plain',
            'size' => 1024, // 1kb
            'tmp_name' => __FILE__,
            'error' => 0
        ], true);

        $this->assertTrue($this->rule->setParameters([1024])->check($file));
        $this->assertTrue($this->rule->setParameters(['1K'])->check($file));

        $this->assertFalse($this->rule->setParameters([1023])->check($file));
        $this->assertFalse($this->rule->setParameters(['0.9kb'])->check($file));
    }
}