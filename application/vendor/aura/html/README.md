# Aura.Html

Provides HTML escapers and helpers, including form input helpers, that can be used in any template, view, or presentation system.

## Foreword

### Installation

This library requires PHP 5.3 or later  with `mbstring` and/or `iconv` installed; we recommend using the latest available version of PHP as a matter of principle. It has no userland dependencies.

It is installable and autoloadable via Composer as [aura/html](https://packagist.org/packages/aura/html).

Alternatively, [download a release](https://github.com/auraphp/Aura.Html/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/auraphp/Aura.Html/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/auraphp/Aura.Html/)
[![codecov](https://codecov.io/gh/auraphp/Aura.Html/branch/2.x/graph/badge.svg?token=UASDouLxyc)](https://codecov.io/gh/auraphp/Aura.Html)
[![Continuous Integration](https://github.com/auraphp/Aura.Html/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/auraphp/Aura.Html/actions/workflows/continuous-integration.yml)

To run the unit tests at the command line, issue `composer install` and then `vendor/bin/phpunit` at the package root. This requires [Composer](http://getcomposer.org/) to be available as `composer`.

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.


## Getting Started

The easiest way to instantiate a _HelperLocator_ with all the available helpers is to use the _HelperLocatorFactory_:

```php
<?php
$factory = new \Aura\Html\HelperLocatorFactory;
$helper = $factory->newInstance();
?>
```

### Built-In Helpers

Once you have a _HelperLocator_, you can then use the helpers by calling them as methods on the _HelperLocator_ instance.  See the [tag helpers](https://github.com/auraphp/Aura.Html/blob/2.x/README-HELPERS.md) and [form helpers](https://github.com/auraphp/Aura.Html/blob/2.x/README-FORMS.md) pages for more information.

> N.b.: All built-in helpers escape values appropriately; see the various helper class internals for more information.

### Custom Helpers

There are two steps to adding your own custom helpers:

1. Write a helper class

2. Set a factory for that class into the _HelperLocator_ under a service name

A helper class needs only to implement the `__invoke()` method.  We suggest extending from _AbstractHelper_ to get access to indenting, escaping, etc., but it's not required.

The following example helper class applies ROT-13 to a string.

```php
<?php
namespace Vendor\Package;

use Aura\Html\Helper\AbstractHelper;

class Obfuscate extends AbstractHelper
{
    public function __invoke($string)
    {
        return $this->escaper->html(str_rot13($input));
    }
}
?>
```

Now that we have a helper class, we set a factory for it into the _HelperLocator_ under a service name. Therein, we create **and return** the helper class.

```php
<?php
$helper->set('obfuscate', function () {
    return new \Vendor\Package\Obfuscate;
});
?>
```

The service name in the _HelperLocator_ doubles as a method name. This means we can call the helper via `$this->obfuscate()`:

```php
<?= $helper->obfuscate('plain text') ?>
```

Note that we can use any service name for the helper, although it is generally
useful to name the service for the helper class, and for a word that can be called as a method.

Please examine the classes in `Aura\Html\Helper` for more complex and powerful
examples.

### Escaping

One of the important but menial tasks with PHP-based template systems is that of escaping output properly. Escaping output is **absolutely necessary** from a security perspective. This package comes with an `escape()` helper that has four escaping methods:

- `$this->escape()->html('foo')` to escape HTML values
- `$this->escape()->attr('foo')` to escape unquoted HTML attributes
- `$this->escape()->css('foo')` to escape CSS values
- `$this->escape()->js('foo')` to escape JavaScript values

Here is a contrived example of the various `escape()` helper methods:

```html+php
<head>

    <style>
        body {
            color: <?= $this->escape()->css($theme->color) ?>;
            font-size: <?= $this->escape()->css($theme->font_size) ?>;
        }
    </style>

    <script language="javascript">
        var foo = "<?= $this->escape()->js($js->foo); ?>";
    </script>

</head>

<body>

    <h1><?= $this->escape()->html($blog->title) ?></h1>

    <p class="byline">
        by <?= $this->escape()->html($blog->author) ?>
        on <?= $this->escape()->html($blog->date) ?>
    </p>

    <div id="<?php $this->escape()->attr($blog->div_id) ?>">
        <?= $blog->raw_html ?>
    </div>

</body>
```

Unfortunately, escaper functionality is verbose, and can make the template code look cluttered.  There are two ways to mitigate this.

The first is to assign the `escape()` helper to a variable, and then invoke it as a callable. Here is a contrived example of the various escaping methods as callables:


```html+php
<?php
// assign the escaper helper properties to callable variables
$h = $this->escape()->html;
$a = $this->escape()->attr;
$c = $this->escape()->css;
$j = $this->escape()->js;
?>

<head>

    <style>
        body {
            color: <?= $c($theme->color) ?>;
            font-size: <?= $c($theme->font_size) ?>;
        }
    </style>

    <script language="javascript">
        var foo = "<?= $j($js->foo); ?>";
    </script>

</head>

<body>

    <h1><?= $h($blog->title) ?></h1>

    <p class="byline">
        by <?= $h($blog->author) ?>
        on <?= $h($blog->date) ?>
    </p>

    <div id="<?php $a($blog->div_id) ?>">
        <?= $blog->raw_html ?>
    </div>

</body>
```

Alternatively, the _Escaper_ class used by the `escape()` helper comes with four static methods to reduce verbosity and clutter:  `h()`, `a()`, `c()`, `j()`, and. These escape values for HTML content values, unquoted HTML attribute values, CSS values, and JavaScript values, respectively.

> N.b.: In Aura, we generally avoid static methods. However, we feel the tradeoff of less-cluttered templates can be worth using static methods in this one case.

To call the static _Escaper_ methods in a PHP-based template, `use` the _Escaper_ as a short alias name, then call the static methods on the alias.  (If you did not instantiate a _HelperLocatorFactory_, you will need to prepare the static escaper methods by calling `Escaper::setStatic(new Escaper)`.)

Here is a contrived example of the various static methods:

```html+php
<?php use Aura\Html\Escaper as e; ?>

<head>

    <style>
        body {
            color: <?= e::c($theme->color) ?>;
            font-size: <?= e::c($theme->font_size) ?>;
        }
    </style>

    <script language="javascript">
        var foo = "<?= e::j($js->foo); ?>";
    </script>

</head>

<body>

    <h1><?= e::h($blog->title) ?></h1>

    <p class="byline">
        by <?= e::h($blog->author) ?>
        on <?= e::h($blog->date) ?>
    </p>

    <div id="<?php e::a($blog->div_id) ?>">
        <?= $blog->raw_html ?>
    </div>

</body>
```
