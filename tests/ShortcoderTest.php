<?php

namespace Seiler\Shortcoder;

class ShortcoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $propertyName
     * @param $instance
     * @return mixed
     */
    private function get($propertyName, $instance)
    {
        $className = get_class($instance);

        $reflector = new \ReflectionClass($className);

        $property = $reflector->getProperty($propertyName);

        $property->setAccessible(true);

        return $property->getValue($instance);
    }

    public function testAddMultipleShortcodesAtOnce()
    {
        $shortcoder = new Shortcoder([
            ['pattern' => '{alert * *}', 'replacement' => '<div class="alert alert-*">*</div>'],
            ['[i]*[/i]' => '<i>*</i>'],
        ]);

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(2, $shortcodes);

        $this->assertEquals('/\{alert\s(.*?)\s(.*?)\}/s', reset($shortcodes)['pattern']);
        $this->assertEquals('<div class="alert alert-$1">$2</div>', reset($shortcodes)['replacement']);

        $this->assertEquals('/\[i\](.*?)\[\/i\]/s', end($shortcodes)['pattern']);
        $this->assertEquals('<i>$1</i>', end($shortcodes)['replacement']);
    }

    public function testAddShortcodeAsArguments()
    {
        $shortcoder = new Shortcoder('{alert * *}', '<div class="alert alert-*">*</div>');

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);

        $this->assertEquals('/\{alert\s(.*?)\s(.*?)\}/s', end($shortcodes)['pattern']);
        $this->assertEquals('<div class="alert alert-$1">$2</div>', end($shortcodes)['replacement']);
    }

    public function testAddShortcodeAsArgumentsWithRegEx()
    {
        $shortcoder = new Shortcoder('{alert * *}', '<div class="alert alert-*">*</div>', true);

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);

        $this->assertEquals('{alert * *}', end($shortcodes)['pattern']);
        $this->assertEquals('<div class="alert alert-*">*</div>', end($shortcodes)['replacement']);
    }

    public function testAddShortcodeAsArray()
    {
        $shortcoder = new Shortcoder([
            'pattern'     => '{alert * *}',
            'replacement' => '<div class="alert alert-*">*</div>',
        ]);

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);

        $this->assertEquals('/\{alert\s(.*?)\s(.*?)\}/s', end($shortcodes)['pattern']);
        $this->assertEquals('<div class="alert alert-$1">$2</div>', end($shortcodes)['replacement']);
    }

    public function testAddShortcodeAsArrayWithRegEx()
    {
        $shortcoder = new Shortcoder([
            'pattern'     => '{alert * *}',
            'replacement' => '<div class="alert alert-*">*</div>',
            'regex'       => true,
        ]);

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);

        $this->assertEquals('{alert * *}', end($shortcodes)['pattern']);
        $this->assertEquals('<div class="alert alert-*">*</div>', end($shortcodes)['replacement']);
    }

    public function testAddShortcodeAsKeyValueArray()
    {
        $shortcoder = new Shortcoder([
            '{alert * *}' => '<div class="alert alert-*">*</div>',
        ]);

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);

        $this->assertEquals('/\{alert\s(.*?)\s(.*?)\}/s', end($shortcodes)['pattern']);
        $this->assertEquals('<div class="alert alert-$1">$2</div>', end($shortcodes)['replacement']);
    }

    public function testDontAddShortcodeIfAlreadyInStack()
    {
        $shortcoder = new Shortcoder('[b]*[/b]', '<strong>*</strong>');

        $shortcoder->add('[b]*[/b]', '<strong>*</strong>');

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);
    }

    public function testDontAddShortcodeIfEmptyPattern()
    {
        $shortcoder = new Shortcoder();

        $shortcoder->add()->add('')->add([])->add(['' => ''])->add(['pattern' => '']);

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(0, $shortcodes);
    }

    public function testFlushShortcodes()
    {
        $shortcoder = new Shortcoder([
            ['foo'],
            ['bar'],
        ]);

        $shortcoder->flush();

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(0, $shortcodes);
    }

    public function testForceBackReferencesInReplacement()
    {
        $shortcoder = new Shortcoder('* * * *', '$2 * $3 *');

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);

        $this->assertEquals('$2 $1 $3 $4', end($shortcodes)['replacement']);
    }

    public function testSwitchClosingCatchAll()
    {
        $shortcoder = new Shortcoder('* then *', '$2 then *');

        $text = $shortcoder->parse('first then second');

        $this->assertEquals('second then first', $text);
    }

    public function testParseText()
    {
        $shortcoder = new Shortcoder('{alert * *}', '<div class="alert alert-*">*</div>');

        $text = $shortcoder->parse('{alert danger This is dangerous !}');

        $this->assertEquals('<div class="alert alert-danger">This is dangerous !</div>', $text);
    }

    public function testReplaceShortcodes()
    {
        $shortcoder = new Shortcoder([
            ['foo'],
            ['bar'],
        ]);

        $shortcoder->set('baz');

        $shortcodes = $this->get('shortcodes', $shortcoder);

        $this->assertCount(1, $shortcodes);
    }
}
