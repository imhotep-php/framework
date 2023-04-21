<?php

namespace Imhotep\Tests\Dotenv;

use Imhotep\Dotenv\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    protected Parser $parser;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->parser = new Parser();
    }

    public function getEnvironment($name)
    {
        return file_get_contents(__DIR__."/env/.env.{$name}");
    }

    public function testDotenvParseBoolean()
    {
        $env = $this->parser->parse($this->getEnvironment('boolean'));

        $this->assertTrue($env['EXPLICIT_LOWERCASE_TRUE']);
        $this->assertTrue($env['EXPLICIT_UPPERCASE_TRUE']);
        $this->assertTrue($env['EXPLICIT_MIXEDCASE_TRUE']);
        $this->assertFalse($env['EXPLICIT_LOWERCASE_FALSE']);
        $this->assertFalse($env['EXPLICIT_UPPERCASE_FALSE']);
        $this->assertFalse($env['EXPLICIT_MIXEDCASE_FALSE']);

        $this->assertTrue($env['ONOFF_LOWERCASE_TRUE']);
        $this->assertTrue($env['ONOFF_UPPERCASE_TRUE']);
        $this->assertTrue($env['ONOFF_MIXEDCASE_TRUE']);
        $this->assertFalse($env['ONOFF_LOWERCASE_FALSE']);
        $this->assertFalse($env['ONOFF_UPPERCASE_FALSE']);
        $this->assertFalse($env['ONOFF_MIXEDCASE_FALSE']);

        $this->assertTrue($env['YESNO_LOWERCASE_TRUE']);
        $this->assertTrue($env['YESNO_UPPERCASE_TRUE']);
        $this->assertTrue($env['YESNO_MIXEDCASE_TRUE']);
        $this->assertFalse($env['YESNO_LOWERCASE_FALSE']);
        $this->assertFalse($env['YESNO_UPPERCASE_FALSE']);
        $this->assertFalse($env['YESNO_MIXEDCASE_FALSE']);
    }

    public function testDotenvParseNumeric()
    {
        $env = $this->parser->parse($this->getEnvironment('numeric'));

        $this->assertSame(0, $env['INT_ZERO']);
        $this->assertSame(1, $env['INT_ONE']);
        $this->assertSame(2, $env['INT_TWO']);
        $this->assertSame(99999999, $env['INT_LARGE']);
        $this->assertSame(-6, $env['INT_MINUS_SIX']);

        $this->assertSame(99999999999999999999999999999999, $env['FLOAT_HUGE']);
        $this->assertSame(1.0, $env['FLOAT_ONE']);
        $this->assertSame(2.0, $env['FLOAT_TWO']);
        $this->assertSame(-6.34364, $env['FLOAT_MINUS_SIX']);
        $this->assertSame(1e7, $env['FLOAT_SCIENT_1']);
        $this->assertSame(20e-7, $env['FLOAT_SCIENT_2']);
    }

    public function testDotenvParseMultiline()
    {
        $env = $this->parser->parse($this->getEnvironment('multiline'));

        $this->assertSame("Start\nmulti-line", $env['MULTI_LINE_VAR1']);
        $this->assertSame("\nline1\nline2\n", $env['MULTI_LINE_VAR2']);
        $this->assertSame("\nEnd multi-line", $env['MULTI_LINE_VAR3']);
    }

    public function testDotenvParseCommented()
    {
        $env = $this->parser->parse($this->getEnvironment('commented'));

        $this->assertSame('bar', $env['CFOO']);
        $this->assertFalse(isset($env['CBAR']));
        $this->assertFalse(isset($env['CZOO']));
        $this->assertSame('with spaces', $env['CSPACED']);
        $this->assertSame('a value with a # character', $env['CQUOTES']);
        $this->assertSame('a value with a # character & a quote " character inside quotes', $env['CQUOTESWITHQUOTE']);
        $this->assertEmpty($env['EMPTY']);
        $this->assertEmpty($env['EMPTY2']);
        $this->assertSame('foo', $env['FOOO']);
        $this->assertTrue($env['BOOLEAN']);
    }

    public function testDotenvParseQuotes()
    {
        $env = $this->parser->parse($this->getEnvironment('quotes'));

        $this->assertSame("with spaces", $env['QSPACED']);
        $this->assertSame("pgsql:host=localhost;dbname=test", $env['QEQUALS']);
        $this->assertSame("", $env['QNULL']);
        $this->assertSame("no space", $env['QWHITESPACE']);
        $this->assertSame('test some escaped characters like a quote (") or maybe a backslash (\)', $env['QESCAPED']);
        $this->assertSame("iiiiviiiixiiiiviiii\\n", $env['QSLASH']);
        $this->assertSame("iiiiviiiixiiiiviiii\\n", $env['SQSLASH']);
        $this->assertSame("iiiiviiiixiiiiviiii\\n", $env['DSLASH']);
    }

    public function testDotenvParseNull()
    {
        $env = $this->parser->parse($this->getEnvironment('nullable'));

        $this->assertNull($env['IS_NULL']);
        $this->assertNull($env['IS_NULL_EQUAL']);
    }

    public function testDotenvParseSpecialchars()
    {
        $env = $this->parser->parse($this->getEnvironment('specialchars'));

        $this->assertSame("\$a6^C7k%zs+e^.jvjXk", $env['SPVAR1']);
        $this->assertSame("?BUty3koaV3%GA*hMAwH}B", $env['SPVAR2']);
        $this->assertSame("jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m\$r", $env['SPVAR3']);
        $this->assertSame("22222:22#2^{", $env['SPVAR4']);
        $this->assertSame("test some escaped characters like a quote \" or maybe a backslash \\", $env['SPVAR5']);
        $this->assertSame("secret!@", $env['SPVAR6']);
        $this->assertSame("secret!@#", $env['SPVAR7']);
        $this->assertSame("secret!@#", $env['SPVAR8']);
    }

    public function testDotenvParseNested()
    {
        $env = $this->parser->parse($this->getEnvironment('nested'));

        $this->assertSame('{$NVAR1} {$NVAR2}', $env['NVAR3']); // not resolved
        $this->assertSame('Hellō World!', $env['NVAR4']);
        $this->assertSame('$NVAR1 {NVAR2}', $env['NVAR5']); // not resolved
        $this->assertSame('Special Value', $env['N.VAR6']); // new '.' (dot) in var name
        $this->assertSame('Special Value', $env['NVAR7']);  // nested '.' (dot) variable
        $this->assertSame('', $env['NVAR8']); // nested variable is empty string
        $this->assertSame('', $env['NVAR9']); // nested variable is empty string
        $this->assertSame('${NVAR888}', $env['NVAR10']); // nested variable is not set
        $this->assertSame('NVAR1', $env['NVAR11']);
        $this->assertSame('Hellō', $env['NVAR12']);
        $this->assertSame('${${NVAR11}}', $env['NVAR13']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $env['NVAR14']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $env['NVAR15']); // escaped
        $this->assertSame('Hellō ${NVAR2}', $env['NVAR16']); // escaped
    }
}