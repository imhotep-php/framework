<?php

namespace Imhotep\Tests\Console;

use Imhotep\Console\Input\StringInput;
use PHPUnit\Framework\TestCase;

class StringInputTest extends TestCase
{
    /**
     * @dataProvider getTokenizeData
     */
    public function testTokinize($input, $tokens, $message)
    {
        $input = new StringInput($input);

        $this->assertSame($tokens, $input->argv(), $message);
    }

    public static function getTokenizeData()
    {
        return [
            ['', [], 'Parses an empty string'],
            ['foo', ['foo'], 'Parses arguments'],
            ['  foo  bar  ', ['foo', 'bar'], 'Ignores whitespaces between arguments'],
            ['"quoted"', ['quoted'], 'Parses quoted arguments'],
            ["'quoted'", ['quoted'], 'Parses quoted arguments'],
            ["'a\rb\nc\td'", ["a\rb\nc\td"], 'Parses whitespace chars in strings'],
            ["'a'\r'b'\n'c'\t'd'", ['a', 'b', 'c', 'd'], 'Parses whitespace chars between args as spaces'],
            ['\"quoted\"', ['"quoted"'], 'Parses escaped-quoted arguments'],
            ["\'quoted\'", ['\'quoted\''], 'Parses escaped-quoted arguments'],
            ['-a', ['-a'], 'Parses short options'],
            ['-azc', ['-azc'], 'Parses aggregated short options'],
            ['-awithavalue', ['-awithavalue'], 'Parses short options with a value'],
            ['-a"foo bar"', ['-afoo bar'], 'Parses short options with a value'],
            ['-a"foo bar""foo bar"', ['-afoo barfoo bar'], 'Parses short options with a value'],
            ['-a\'foo bar\'', ['-afoo bar'], 'Parses short options with a value'],
            ['-a\'foo bar\'\'foo bar\'', ['-afoo barfoo bar'], 'Parses short options with a value'],
            ['-a\'foo bar\'"foo bar"', ['-afoo barfoo bar'], 'Parses short options with a value'],
            ['--long-option', ['--long-option'], 'Parses long options'],
            ['--long-option=foo', ['--long-option=foo'], 'Parses long options with a value'],
            ['--long-option="foo bar"', ['--long-option=foo bar'], 'Parses long options with a value'],
            ['--long-option="foo bar""another"', ['--long-option=foo baranother'], 'Parses long options with a value'],
            ['--long-option=\'foo bar\'', ['--long-option=foo bar'], 'Parses long options with a value'],
            ["--long-option='foo bar''another'", ['--long-option=foo baranother'], 'Parses long options with a value'],
            ["--long-option='foo bar'\"another\"", ['--long-option=foo baranother'], 'Parses long options with a value'],
            ['foo -a -ffoo --long bar', ['foo', '-a', '-ffoo', '--long', 'bar'], 'Parses when several arguments and options'],
            ["--arg=\\\"'Jenny'\''s'\\\"", ["--arg=\"Jenny's\""], 'Parses quoted quotes'],
        ];
    }
}