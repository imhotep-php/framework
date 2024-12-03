<?php

namespace Imhotep\Tests\Console;

use Imhotep\Console\Input\InputArgument;
use Imhotep\Console\Input\InputOption;
use Imhotep\Console\Utils\SignatureParser;
use PHPUnit\Framework\TestCase;

class SignatureParserTest extends TestCase
{
    public function test_parse_arguments()
    {
        $argument = InputArgument::fromString('user-arg1');
        $this->assertSame('user-arg1', $argument->getName());
        $this->assertFalse($argument->isOptional());
        $this->assertTrue($argument->isRequired());
        $this->assertFalse($argument->isArray());
        $this->assertSame('user-arg1', $argument->toString());
        $this->assertSame('user-arg1', (string)$argument);

        $argument = InputArgument::fromString('user?');
        $this->assertSame('user', $argument->getName());
        $this->assertTrue($argument->isOptional());
        $this->assertFalse($argument->isRequired());
        $this->assertFalse($argument->isArray());
        $this->assertSame('user?', (string)$argument);

        $argument = InputArgument::fromString('user*');
        $this->assertSame('user', $argument->getName());
        $this->assertFalse($argument->isOptional());
        $this->assertTrue($argument->isRequired());
        $this->assertTrue($argument->isArray());
        $this->assertSame('user*', (string)$argument);

        $argument = InputArgument::fromString('user?*');
        $this->assertSame('user', $argument->getName());
        $this->assertTrue($argument->isOptional());
        $this->assertFalse($argument->isRequired());
        $this->assertTrue($argument->isArray());
        $this->assertSame('user?*', (string)$argument);

        $argument = InputArgument::fromString('user?=imhotep');
        $this->assertSame('imhotep', $argument->getDefault());
        $this->assertTrue($argument->isOptional());
        $this->assertFalse($argument->isRequired());
        $this->assertFalse($argument->isArray());
        $this->assertSame('user?=imhotep', (string)$argument);

        $argument = InputArgument::fromString('user?*=imhotep : The argument with description');
        $this->assertSame('user', $argument->getName());
        $this->assertSame(['imhotep'], $argument->getDefault());
        $this->assertTrue($argument->isOptional());
        $this->assertFalse($argument->isRequired());
        $this->assertTrue($argument->isArray());
        $this->assertSame('user?*=imhotep : The argument with description', (string)$argument);
    }

    public function test_parse_options()
    {
        $option = InputOption::fromString('--flag-1');
        $this->assertSame('flag-1', $option->getName());
        $this->assertSame(null, $option->getDefault());
        $this->assertSame('', $option->getDescription());
        $this->assertTrue($option->isValueNone());
        $this->assertFalse($option->isValueOptional());
        $this->assertFalse($option->isValueRequired());
        $this->assertFalse($option->isArray());
        $this->assertSame('--flag-1', $option->toString());
        $this->assertSame('--flag-1', (string)$option);

        $option = InputOption::fromString('--f|flag');
        $this->assertSame('flag', $option->getName());
        $this->assertSame(['f'], $option->getShortcut());
        $this->assertTrue($option->isValueNone());
        $this->assertFalse($option->isValueOptional());
        $this->assertFalse($option->isValueRequired());
        $this->assertFalse($option->isArray());
        $this->assertSame('--f|flag', (string)$option);

        $option = InputOption::fromString('--flag=');
        $this->assertSame('flag', $option->getName());
        $this->assertFalse($option->isValueNone());
        $this->assertFalse($option->isValueOptional());
        $this->assertTrue($option->isValueRequired());
        $this->assertFalse($option->isArray());
        $this->assertSame('--flag=', (string)$option);

        $option = InputOption::fromString('--flag=?default');
        $this->assertSame('flag', $option->getName());
        $this->assertSame('default', $option->getDefault());
        $this->assertFalse($option->isValueNone());
        $this->assertTrue($option->isValueOptional());
        $this->assertFalse($option->isValueRequired());
        $this->assertFalse($option->isArray());
        $this->assertSame('--flag=?default', (string)$option);

        $option = InputOption::fromString('--flag=*');
        $this->assertSame('flag', $option->getName());
        $this->assertFalse($option->isValueNone());
        $this->assertFalse($option->isValueOptional());
        $this->assertTrue($option->isValueRequired());
        $this->assertTrue($option->isArray());
        $this->assertSame('--flag=*', (string)$option);

        $option = InputOption::fromString('--flag=?*');
        $this->assertSame('flag', $option->getName());
        $this->assertFalse($option->isValueNone());
        $this->assertTrue($option->isValueOptional());
        $this->assertFalse($option->isValueRequired());
        $this->assertTrue($option->isArray());
        $this->assertSame('--flag=?*', (string)$option);

        $option = InputOption::fromString('--flag=?*default : The option with description');
        $this->assertSame('flag', $option->getName());
        $this->assertSame(['default'], $option->getDefault());
        $this->assertSame('The option with description', $option->getDescription());
        $this->assertFalse($option->isValueNone());
        $this->assertTrue($option->isValueOptional());
        $this->assertFalse($option->isValueRequired());
        $this->assertTrue($option->isArray());
        $this->assertSame('--flag=?*default : The option with description', (string)$option);
    }
}