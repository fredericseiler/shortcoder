# Shortcoder

[![Latest Version][ico-version]][link-version]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-build]][link-build]
[![Coverage Status][ico-coverage]][link-coverage]
[![Quality Score][ico-code-quality]][link-code-quality]

Shortcoder helps you to build your own shortcodes system in your PHP application with ease.

This package is compliant with [PSR-1], [PSR-2] and [PSR-4]. If you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## Requirements

The following versions of PHP are supported by this version.

* PHP 5.5
* PHP 5.6
* PHP 7.0
* HHVM

## Installation

Via Composer

```bash
$ composer require seiler/shortcoder
```

## Usage

1. Create a new Shortcoder instance:

    ```php
    use Seiler\Shortcoder\Shortcoder;

    $shortcoder = new Shortcoder();
    ```

2. Add your shortcodes:

    ```php
    $shortcoder->add('[i]*[/i]', '<em>*</em>');

    $shortcoder->add('[b]*[/b]', '<strong>*</strong>');
    ```

3. Parse some text:

    ```php
    echo $shortcoder->parse('I [i]love it[/i] when a plan [b]comes together[/b].');

    // I <em>love it</em> when a plan <strong>comes together</strong>.
    ```

## Documentation

A shortcode is defined by a pattern, a replacement and an [optional regex flag](#regular-expressions). A shortcode pattern usually contains one or more wildcards to represent the content to be preserved between the pattern and the replacement.

Shortcoder allows you to `add()` shortcodes with the following syntaxes.

* Method arguments:

    ```php
    $pattern = '[b]*[/b]';
    $replacement = '<strong>*</strong>';

    $shortcoder->add($pattern, $replacement);
    ```

* Key/value array:

    ```php
    $shortcode = [
        '[b]*[/b]' => '<strong>*</strong>'
    ];

    $shortcoder->add($shortcode);
    ```

* Descriptive array:

    ```php
    $shortcode = [
        'pattern'     => '[b]*[/b]',
        'replacement' => '<strong>*</strong>'
    ];

    $shortcoder->add($shortcode);
    ```

* Multiple shortcodes at once:

    ```php
    $shortcodes = [
        [
            'pattern'     => '[b]*[/b]',
            'replacement' => '<strong>*</strong>'
        ],
        [
            'pattern'     => '[img *]',
            'replacement' => '<img class="img-responsive" src="*">'
        ],
        [
            '[i]*[/i]' => '<em>*</em>'
        ]
    ];

    $shortcoder->add($shortcodes);
    ```

The `flush()` and `set()` methods are useful for flushing the current shortcodes stack. The `set()` method also adds new shortcodes after a flush.

```php
$shortcoder->set('[i]*[/i]', '<em>*</em>');

// is equivalent to:

$shortcoder->flush();
$shortcoder->add('[i]*[/i]', '<em>*</em>');
```

Because `set()` is called when you create a new Shortcoder instance, all of the `add()` syntaxes are available in the constructor as well.

```php
$shortcoder = new Shortcoder($pattern, $replacement);

// is equivalent to:

$shortcoder = new Shortcoder();
$shortcoder->set($pattern, $replacement);
```

The `parse()` method takes any string as input and parses it with the stacked shortcodes.

```php
$string = 'Lorem ipsum dolor sit amet';

echo $shortcoder->parse($string);
```

### Method chaining

The `add()`, `set()` and `flush()` methods supports method chaining.

```php
echo $shortcoder->set('foo', 'bar')
                ->add($more)
                ->parse($text);
```

### Multiple wildcards

Wildcards are replaced by regular expression powered catch-alls when adding shortcodes to the stack. It means that when you add multiple wildcards, Shortcoder will match each wildcard in a pattern to its corresponding position in the replacement.

When a wildcard directly follows another wildcard in a pattern, only the first word of the matching expression will be assigned to the first wildcard, the remaining of the expression will be catched as the second wildcard.

```php
$pattern = '[alert * *]';
$replacement = '<div class="alert alert-*">*</div>';

$shortcoder->add($pattern, $replacement);

echo $shortcoder->parse('[alert danger This is important!]');
// <div class="alert alert-danger">This is important!</div>
```

### Regular expressions

When setting the third argument of the `add()` method (or the `regex` attribute of the shortcode) to any 'true' value, Shortcoder will handle pattern and replacement as raw regular expressions, which can be useful for more advanced usages.

```php
$pattern = '/(?<=^|\s)@(\w{1,15})/m'; // Twitter @handle
$replacement = '<a href="https://twitter.com/$1">@$1</a>';

// setting the third argument
$shortcoder->set($pattern, $replacement, true);

// or setting the 'regex' attribute
$shortcoder->set([
    'pattern' => $pattern,
    'replacement' => $replacement,
    'regex' => true
]);

echo $shortcoder->parse('Do you follow @php_net ?');
// Do you follow <a href="https://twitter.com/php_net">@php_net</a> ?
```

### Backreferences

When forcing position of some of the [backreferences](http://php.net/manual/en/regexp.reference.back-references.php) in a shortcode's replacement, Shortcoder will guess what to do with the remaining wildcards.

```php
$pattern = '* then *';
$replacement = '$2 then *';

$shortcoder->add($pattern, $replacement);

echo $shortcoder->parse('first then second');
// second then first
```

### Markdown compatibility

In case you're using Shortcoder to render some HTML blocks, just append `markdown=1` in your replacement attributes to support [Markdown Extra](https://michelf.ca/projects/php-markdown/extra). Here's an example with [Parsedown Extra](https://github.com/erusev/parsedown-extra):

```php
$shortcoder->add('[info *]', '<div class="info" markdown=1>*</div>');

$text = $shortcoder->parse('[info You can use *markdown* in HTML elements.]');

echo $parsedownExtra->text($text);
// <div class="info">
// <p>You can use <em>markdown</em> in HTML elements.</p>
// </div>
```

With [CommonMark](http://commonmark.org), it's a little bit [more tricky](http://spec.commonmark.org/0.24/#html-blocks). Rules are not the same for inline and block level elements. Example of an [inline level](http://spec.commonmark.org/0.24/#example-130) element with the [league/commonmark](http://commonmark.thephpleague.com) implementation:

```php
$shortcoder->add('[info *]', '<span class="info">*</span>');

$text = $shortcoder->parse('[info You can use *markdown* in inline HTML elements.]');

echo $commonMark->convertToHtml($text);
// <p><span class="info">You can use <em>markdown</em> in inline HTML elements.</span></p>
```

And here's the same example, but this time with a [block level](http://spec.commonmark.org/0.24/#example-149) element:

```php
$shortcoder->add('[info *]', '<div class="info">*</div>');

$text = $shortcoder->parse('[info

You can use *markdown* in block HTML elements.

]');

echo $commonMark->convertToHtml($text);
// <div class="info">
// <p>You can use <em>markdown</em> in block HTML elements.</p>
// </div>
```

## Testing

```bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email frederic@seiler.io instead of using the issue tracker.

## Credits

- [Frederic Seiler][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/seiler/shortcoder.svg?style=flat-square
[ico-license]: https://img.shields.io/packagist/l/seiler/shortcoder.svg?style=flat-square
[ico-build]: https://img.shields.io/travis/fredericseiler/shortcoder/master.svg?style=flat-square
[ico-coverage]: https://img.shields.io/scrutinizer/coverage/g/fredericseiler/shortcoder.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/fredericseiler/shortcoder.svg?style=flat-square

[link-version]: https://packagist.org/packages/seiler/shortcoder
[link-build]: https://travis-ci.org/fredericseiler/shortcoder
[link-coverage]: https://scrutinizer-ci.com/g/fredericseiler/shortcoder/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/fredericseiler/shortcoder
[link-downloads]: https://packagist.org/packages/seiler/shortcoder
[link-author]: https://github.com/fredericseiler
[link-contributors]: ../../contributors
