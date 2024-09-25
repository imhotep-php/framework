<?php

namespace Imhotep\Tests\Localization;

use Imhotep\Filesystem\Filesystem;
use Imhotep\Localization\FileLoader;
use Imhotep\Localization\Localizator;
use PHPUnit\Framework\TestCase;

class LocalizationTest extends TestCase
{
    protected Localizator $lang;

    protected function lang(string $locale = 'en', string $fallback = 'en'): Localizator
    {
        $loader = new FileLoader(new Filesystem(), __DIR__.'/lang');

        return new Localizator($loader, $locale, $fallback);
    }

    public function test_root_lang()
    {
        $string = $this->lang('en', 'en')->get('root_key_1');
        $this->assertSame('Main Root 1', $string);

        $string = $this->lang('ru', 'en')->get('root_key_1');
        $this->assertSame('Корневой перевод 1', $string);

        // Test fallback
        $string = $this->lang('ru', 'en')->get('root_key_3');
        $this->assertSame('Main Root 3', $string);

        // Test no lang
        $string = $this->lang('ru', 'en')->get('root_key_4');
        $this->assertSame('root_key_4', $string);
    }

    public function test_group_lang()
    {
        $string = $this->lang('en', 'en')->get('foo.key1');
        $this->assertSame('value 1', $string);

        $string = $this->lang('ru', 'en')->get('foo.key1');
        $this->assertSame('значение 1', $string);

        // Test fallback
        $string = $this->lang('ru', 'en')->get('foo.key3');
        $this->assertSame('value 3', $string);

        // Test no lang
        $string = $this->lang('ru', 'en')->get('foo.key4');
        $this->assertSame('foo.key4', $string);
    }

    public function test_namespace_root_lang()
    {
        $lang = $this->lang('en', 'en')
            ->addNamespace('xyz', __DIR__.'/lang_xyz/');

        $this->assertSame('Vendor Root 1', $lang->get('xyz::ns_root_1'));
        $this->assertSame('Расширенный корень 1', $lang->setLocale('ru')->get('xyz::ns_root_1'));

        // Test fallback
        $this->assertSame('Vendor Root 3', $lang->setLocale('ru')->get('xyz::ns_root_3'));

        // Test no lang
        $this->assertSame('xyz::ns_root_4', $lang->setLocale('ru')->get('xyz::ns_root_4'));
    }

    public function test_namespace_group_lang()
    {
        $lang = $this->lang('en', 'en')
            ->addNamespace('xyz', __DIR__.'/lang_xyz/');

        $this->assertSame('Vendor Value 1', $lang->get('xyz::foo.ns_key_1'));
        $this->assertSame('Расширенное значение 1', $lang->setLocale('ru')->get('xyz::foo.ns_key_1'));

        // Test fallback
        $this->assertSame('Vendor Value 3', $lang->setLocale('ru')->get('xyz::foo.ns_key_3'));

        // Test no lang
        $this->assertSame('xyz::foo.ns_key_4', $lang->setLocale('ru')->get('xyz::foo.ns_key_4'));
    }

    public function test_namespace_root_replace_lang()
    {
        $lang = $this->lang('en', 'en')
            ->addNamespace('xyz', __DIR__.'/lang_xyz/');

        $this->assertSame('Replaced Root 2', $lang->get('xyz::ns_root_2'));
        $this->assertSame('Измененное корневое 2', $lang->setLocale('ru')->get('xyz::ns_root_2'));
    }

    public function test_not_found_callback()
    {
        $lang = $this->lang('ru', 'de');

        $lang->addNotFoundKeyCallback(function ($key, $locale, $fallback) {
           $this->assertSame(['foo.not_found', 'ru', 'de'], [$key, $locale, $fallback]);
        });

        $lang->get('foo.not_found');
    }

    public function test_replace()
    {
        $string = $this->lang()->get('test_replace', [
            'name' => 'User',
            'framework' => 'Imhotep'
        ]);

        $this->assertSame('Hello, User! Welcome to Imhotep!', $string);

        $string = $this->lang('ru')->get('test_replace', [
            'name' => 'User',
            'framework' => 'Imhotep'
        ]);

        $this->assertSame('Привет, User! Добро пожаловать в Imhotep!', $string);
    }

    public function test_replace_cases()
    {
        $string = $this->lang()->get('test_replace_case', [
            'value1' => 'foo',
            'value2' => 'BaR',
            'value3' => 'xyz'
        ]);

        $this->assertSame('Upper: FOO, Lower: bar, Ucfirst: Xyz', $string);
    }

    public function test_plural()
    {
        $lang = $this->lang();
        $this->assertSame('1 book', $lang->get('test_plural', ['count' => 1]));
        $this->assertSame('2 books', $lang->get('test_plural', ['count' => 2]));
        $this->assertSame('240 books', $lang->get('test_plural', ['count' => 240]));

        $lang = $this->lang('ru');
        $this->assertSame('1 книга', $lang->get('test_plural', ['count' => 1]));
        $this->assertSame('2 книги', $lang->get('test_plural', ['count' => 2]));
        $this->assertSame('240 книг', $lang->get('test_plural', ['count' => 240]));
    }

    public function test_choice()
    {
        $lang = $this->lang();
        $this->assertSame('zero', $lang->get('test_choice', ['num' => 0]));
        $this->assertSame('one', $lang->get('test_choice', ['num' => 1]));
        $this->assertSame('two', $lang->get('test_choice', ['num' => 2]));
        $this->assertSame('from three to five', $lang->get('test_choice', ['num' => 4]));
        $this->assertSame('other', $lang->get('test_choice', ['num' => 10]));
    }

    public function test_choice_multiline()
    {
        $lang = $this->lang();
        $this->assertSame('zero', $lang->get('test_choice_multi', ['num' => 0]));
        $this->assertSame('one', $lang->get('test_choice_multi', ['num' => 1]));
        $this->assertSame('two', $lang->get('test_choice_multi', ['num' => 2]));
        $this->assertSame('from three to five', $lang->get('test_choice_multi', ['num' => 4]));
        $this->assertSame('other', $lang->get('test_choice_multi', ['num' => 10]));
    }
}