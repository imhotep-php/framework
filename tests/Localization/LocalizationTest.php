<?php

namespace Imhotep\Tests\Localization;

use Imhotep\Filesystem\Filesystem;
use Imhotep\Localization\FileLoader;
use Imhotep\Localization\Localizator;
use Imhotep\Support\Str;
use PHPUnit\Framework\TestCase;

class LocalizationTest extends TestCase
{
    protected Localizator $lang;

    protected function lang(string $locale = 'en', string $fallback = 'en'): Localizator
    {
        $loader = new FileLoader(new Filesystem(), __DIR__.'/lang');

        $localizator = new Localizator($loader, $locale, $fallback);

        $localizator->addModifier('lcfirst', fn($value) => Str::lcfirst($value));
        $localizator->addModifier('ucfirst', fn($value) => Str::ucfirst($value));
        $localizator->addModifier('lower', fn($value) => Str::lower($value));
        $localizator->addModifier('upper', fn($value) => Str::upper($value));
        $localizator->addModifier('ucwords', fn($value) => Str::ucwords($value));
        $localizator->addModifier('slug', fn($value) => Str::slug($value));

        return $localizator;
    }

    public function test_has_and_missing()
    {
        $lang = $this->lang('en', 'en');

        $this->assertTrue($lang->has('root_key_1'));
        $this->assertTrue($lang->has('foo.key1'));
        $this->assertFalse($lang->has('nonexistent.key'));
        $this->assertFalse($lang->has('foo.nonexistent'));

        $this->assertFalse($lang->missing('root_key_1'));
        $this->assertFalse($lang->missing('foo.key1'));
        $this->assertTrue($lang->missing('nonexistent.key'));
        $this->assertTrue($lang->missing('foo.nonexistent'));
    }

    public function test_has_with_fallback()
    {
        $lang = $this->lang('ru', 'en');

        // Exists in ru
        $this->assertTrue($lang->has('root_key_1'));
        // Missing in ru, exists in en (fallback)
        $this->assertTrue($lang->has('root_key_3'));
        // Missing in both
        $this->assertFalse($lang->has('nonexistent'));
    }

    public function test_has_without_fallback()
    {
        $lang = $this->lang('ru', 'en');

        // Missing in ru, but exists in en - without fallback should return false
        $this->assertFalse($lang->has('root_key_3', fallback: false));
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
        $string = $this->lang('ru', 'en')->get('foo.nonexistent');
        $this->assertSame('foo.nonexistent', $string);
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

    public function test_handle_not_found()
    {
        $lang = $this->lang('ru', 'de');

        $lang->handleNotFound(function ($key, $locale, $fallback) {
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

    public function test_get_with_explicit_locale()
    {
        $lang = $this->lang('en', 'en');

        // Override locale parameter
        $this->assertSame('Корневой перевод 1', $lang->get('root_key_1', locale: 'ru'));
        $this->assertSame('Main Root 1', $lang->get('root_key_1', locale: 'en'));
    }

    public function test_get_without_fallback()
    {
        $lang = $this->lang('ru', 'en');

        // Without fallback should return key itself
        $this->assertSame('root_key_3', $lang->get('root_key_3', fallback: false));
    }

    public function test_fallback_methods()
    {
        $lang = $this->lang('en', 'de');

        $this->assertSame('de', $lang->fallback());

        $lang->setFallback('fr');
        $this->assertSame('fr', $lang->fallback());
    }

    public function test_loaded_methods()
    {
        $lang = $this->lang();

        // Initially empty
        $this->assertEmpty($lang->loaded());

        // Load some translations
        $lang->get('root_key_1');
        $loaded = $lang->loaded();
        $this->assertNotEmpty($loaded);

        // Test setLoaded
        $lang->setLoaded([]);
        $this->assertEmpty($lang->loaded());
    }

    /*
    public function test_main_not_found_callback()
    {
        $lang = $this->lang('ru', 'de');
        $called = false;

        $lang->addNotFoundKeyCallback(function ($key, $locale) use (&$called) {
            $called = true;
            $this->assertSame('foo.not_found', $key);
            $this->assertSame('ru', $locale);
        });

        $lang->get('foo.not_found');
        $this->assertTrue($called);
    }
    */

    public function test_add_modifier()
    {
        $lang = $this->lang();

        $lang->addModifier('reverse', fn($v) => strrev($v));

        // Add test key with reverse modifier
        $lang->add('test_reverse', 'Reversed: :reverse:value');


        /*
        $lang->setLoaded([
            'en' => [ // Locale
                '__app__' => [
                    'test_reverse' => 'Reversed: :reverse:value'
                ]
            ]
        ]);
        */

        $result = $lang->get('test_reverse', ['value' => 'abc']);
        $this->assertSame('Reversed: cba', $result);
    }


    public function test_nested_keys()
    {
        $lang = $this->lang();

        $this->assertSame('Deep value', $lang->get('nested.deep.key'));
    }

    /*
        public function test_plural_fallback_when_form_not_found()
        {
            $lang = $this->lang();

            // Add test with only 2 plural forms but requesting 3rd
            $lang->setLoaded([
                '*' => [
                    '*' => [
                        'en' => ['test_plural_short' => '{:count|one|other}']
                    ]
                ]
            ]);

            // Should fallback to last value when plural form not found
            $result = $lang->get('test_plural_short', ['count' => 5]);
            $this->assertSame('other', $result);
        }

        public function test_choice_fallback_when_range_not_found()
        {
            $lang = $this->lang();

            // Add test with limited ranges
            $lang->setLoaded([
                '*' => [
                    '*' => [
                        'en' => ['test_choice_short' => '{:num|[0]zero|[1]one}']
                    ]
                ]
            ]);

            // Should fallback to last value when range not matched
            $result = $lang->get('test_choice_short', ['num' => 5]);
            $this->assertSame('one', $result);
        }

        public function test_json_files()
        {
            // Create temporary JSON lang file
            $jsonPath = __DIR__.'/lang/en.json';
            file_put_contents($jsonPath, json_encode(['json_key' => 'JSON Value']));

            try {
                $lang = $this->lang();
                $this->assertSame('JSON Value', $lang->get('json_key'));
            } finally {
                @unlink($jsonPath);
            }
        }

        public function test_add_plural_custom_rule()
        {
            $lang = $this->lang();

            // Custom plural rule: always return form 0
            $lang->addPlural('custom', fn($n) => 0);

            $lang->setLoaded([
                '*' => [
                    '*' => [
                        'custom' => ['test_custom_plural' => '{:count|first|second|third}']
                    ]
                ]
            ]);

            // With custom rule always returning 0, should always get 'first'
            $this->assertSame('first', $lang->get('test_custom_plural', ['count' => 1], 'custom'));
            $this->assertSame('first', $lang->get('test_custom_plural', ['count' => 5], 'custom'));
            $this->assertSame('first', $lang->get('test_custom_plural', ['count' => 100], 'custom'));
        }

        public function test_locale_method()
        {
            $lang = $this->lang('en', 'de');

            $this->assertSame('en', $lang->locale());

            $lang->setLocale('fr');
            $this->assertSame('fr', $lang->locale());
        }
        */
}