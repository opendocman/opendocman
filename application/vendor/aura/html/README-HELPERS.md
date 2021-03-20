# Aura.Html Tag Helpers

Use a helper by calling it as a method on the _HelperLocator_. The available helpers are:

- [a](#a) / anchor / aRaw / anchorRaw
- [base](#base)
- [img](#img) / image
- [label](#label)
- [links](#links)
- [metas](#metas)
- [ol](#ol)
- [scripts](#scripts) / scriptsFoot
- [styles](#styles)
- [tag](#tag)
- [title](#title)
- [ul](#ul)
- [void](#void)

There is also a series of [helpers for forms](https://github.com/auraphp/Aura.Html/blob/2.x/README-FORMS.md).

## a

Helper for `<a>` tags.

```html+php
<?php
echo $helper->a(
    'http://auraphp.com',       // (string) href
    'Aura Project',             // (string) text
    array('id' => 'aura-link')  // (array) optional attributes
);
?>
<a href="http://auraphp.com" id="aura-link">Aura Project</a>
```

Use `$helper->anchor()` as an alias for `$helper->a()`. All output will be escaped properly.

To output the anchor text without escaped, use `$helper->aRaw()` or `$helper->anchorRaw()`.

```html+php
<?php
echo $helper->anchorRaw(
    'http://auraphp.com',       // (string) href
    '<em>Aura Project</em>',    // (string) text (WILL NOT be escaped)
    array('id' => 'aura-link')  // (array) optional attributes
);
?>
<a href="http://auraphp.com" id="aura-link"><em>Aura Project</em></a>
```

(The href and attributes will still be escaped properly.)

## base

Helper for `<base>` tags.

```html+php
<?php
echo $helper->base(
    '/base' // (string) href
);
?>
<base href="/base" />
```

## img

Helper for `<img>` tags.

```html+php
<?php
echo $helper->img(
    '/images/hello.jpg',            // (string) image href src
    array('id' => 'image-id');      // (array) optional attributes
?>
<!-- if alt is not specified, uses the basename of the image href -->
<img src="/images/hello.jpg" alt="hello" id="image-id">

```

## label

Helper for `<label>` tags.

```html+php
<?php
echo $helper->label(
    'Label For Field',          // (string) label text
    array('for' => 'field'));   // (array) optional attributes
?>
<label for="field">Label For Field</label>

<?php
// wrap html with the label before the html
echo $helper->label('Foo: ')
            ->before($helper->input(array(
                'type' => 'text',
                'name' => 'foo',
            )));
?>
<label>Foo: <input type="text" name="foo" value="" /></label>

<?php
// wrap html with the label after the html
echo $helper->label(' (Foo)')
            ->after($helper->input(array(
                'type' => 'text',
                'name' => 'foo',
            )));
?>
<label><input type="text" name="foo" value="" /> (Foo)</label>
```

## links

Helper for a set of generic `<link>` tags. Build a set of links with `add()` then output them all at once.

```html+php
<?php
// build the array of links with add()
$helper->links()->add(array(
    'rel' => 'prev',                // (array) link attributes
    'href' => '/path/to/prev',
));

$helper->links()->add(array(        // (array) link attributes
    'rel' => 'next',
    'href' => '/path/to/next',
));

// output the links
echo $helper->links();
?>
<link rel="prev" href="/path/to/prev" />
<link ref="next" href="/path/to/next" />

<?php
// alternatively, echo a chain of add() calls
echo $helper->links()
    ->add(array(                    // (array) link attributes
        'rel' => 'prev',
        'href' => '/path/to/prev',
    ))
    ->add(array(                    // (array) link attributes
        'rel' => 'next',
        'href' => '/path/to/next',
    ));
?>

<link rel="prev" href="/path/to/prev" />
<link ref="next" href="/path/to/next" />
```

Finally, you can output a one-off `<link>` tag by echoing the helper directly
with an array of attributes:

```html+php
<?php
echo $helper->links(array(
    'rel' => 'prev',
    'href' => '/path/to/prev',
));
?>
<link rel="prev" href="/path/to/prev" />
```

## metas

Helper for a set of `<meta>` tags. Build a set of metas with `add*()` then output them all at once.

```html+php
<?php
// add an http-equivalent meta
$helper->metas()->addHttp(
    'Location',         // (string) header label
    '/redirect/to/here' // (string) header value
);

// add a name meta
$helper->metas()->addName(
    'foo',              // the meta name
    'bar'               // the meta content
);

// output the metas
echo $helper->metas();
?>
<meta http-equiv="Location" content="/redirect/to/here">
<meta name="foo" content="bar">

<?php
// alternatively, echo a chain of add calls
echo $helper->metas()
    ->addHttp(
        'Location',         // (string) header label
        '/redirect/to/here' // (string) header value
    )
    ->addName(
        'foo',              // the meta name
        'bar'               // the meta content
    );
?>
<meta http-equiv="Location" content="/redirect/to/here">
<meta name="foo" content="bar">
```

Finally, you can output a one-off `<meta>` tag by echoing the helper directly
with an array of attributes:

```html+php
<?php
echo $helper->metas(array(
    'name' => 'foo',
    'content' => 'bar',
));
?>
<meta name="foo" content="bar">
```

## ol

Helper for `<ol>` tags with `<li>` items.  Build the set of items (both raw and escaped) then output them all at once.

```html+php
<?php
// start the list of items
$helper->ol(array(                  // (array) optional attributes
    'id' => 'test',
));

// add a single item to be escaped
$helper->ol()->item(
    'foo',                          // (string) the item text
    array('id' => 'foo')            // (array) optional attributes
);

// add several items to be escaped
$helper->ol()->items(array(         // (array) the items to add
    'bar',                          // the item text, no item attributes
    'baz' => array('id' => 'baz'),  // item text with item attributes
));

// add a single raw item not to be escaped
$helper->ol()->rawItem(
    '<a href="/first">First</a>',   // (string) the raw item html
    array('id' => 'first')          // (array) optional attributes
);

// add several raw items not to be escaped
$helper->ol()->rawItems(array(         // (array) the raw items to add
    '<a href="/prev">Prev</a>',     // the item text, no item attributes
    '<a href="/next">Next</a>',
    '<a href="/last">Last</a>' => array('id' => 'last') // text and attributes
));

// output the list
echo $helper->ol();
?>
<ol id="test">
    <li id="foo">foo</li>
    <li>bar</li>
    <li id="baz">baz</li>
    <li><a href="/first">First</a></li>
    <li><a href="/pref">First</a></li>
    <li><a href="/next">First</a></li>
    <li><a href="/last">First</a></li>
</ol>
```

## scripts

Helper for a set of `<script>` tags. Build a set of script links, then output
them all at once.

```html+php
<?php
// add a single script
$helper->scripts()->add('/js/middle.js');

// add another script after that one
$helper->scripts()->add('/js/last.js');

// add another at a specific priority order
echo $helper->scripts()->add(
    '/js/first.js',     // (string) the script src
    50                  // (int) optional priority order (default 100)
);

// add a conditional script
$helper->scripts()->addCond(
    'ie6',              // (string) the condition
    '/js/ie6.js',       // (string) the script src
    25                  // (int) optional priority order (default 100)
));

// add an internal script
$helper->scripts()->addInternal(
    'alert("foo");',     // (string) script
    1000                // (int) optional priority order (default 100)
);

// add an internal conditional script
$helper->scripts()->addCondInternal(
    'ie6',                // (string) the condition
    'alert("ie6");',     // (string) script
    1000                // (int) optional priority order (default 100)
);

// capture an internal script
$helper->scripts()->beginInternal(
    1000                // (int) optional priority order (default 100)
);
echo 'alert("captured")';
$helper->scripts()->endInternal();

// capture an internal conditional script
$helper->scripts()->beginCondInternal(
    'ie6',                    // (string) the condition
    'alert("capture ie6");',  // (string) script
    1000                      // (int) optional priority order (default 100)
);
echo 'alert("capture ie6")';
$helper->scripts()->endInternal();

// add a script tag with additional attributes
// by passing an attribute array as the last parameter of any of the method
$helper->scripts()->add(
    'https://cdn.tld/foo.js',
    100,
    [
        'async' => true,
        'defer' => true,
        'crossorigin' => 'anonymous',
        'integrity' => 'sha256-a23g1Nt4dtEYOj7bR+vTu7+T8VP13humZFBJNIYoEJo='
    ]
);

?>
<!--[if ie6]><script src="/js/ie6.js" type="text/javascript"></script><![endif]-->
<script src="/js/first.js" type="text/javascript"></script>
<script src="/js/middle.js" type="text/javascript"></script>
<script src="/js/last.js" type="text/javascript"></script>
<script type="text/javascript">alert("foo");</script>
<!--[if ie6]><script type="text/javascript">alert("ie6");</script><![endif]-->
<script type="text/javascript">alert("captured");</script>
<!--[if ie6]><script type="text/javascript">alert("capture ie6");</script><![endif]-->
<script src="http://cdn.tld/foo.js" type="text/javascript" async defer crossorigin="anonymous" integrity="sha256-a23g1Nt4dtEYOj7bR+vTu7+T8VP13humZFBJNIYoEJo="></script>

```

> N.b.: The `scriptsFoot()` helper works the same way, but is intended for placing a separate set of scripts at the end of the HTML body.

Finally, you can output a one-off `<script>` tag by echoing the helper directly
with the source string:

```html+php
<?php
echo $helper->scripts('/js/script.js');
?>
<script src="/js/script.js" type="text/javascript"></script>
```

## styles

Helper for a set of `<link>` tags for stylesheets. Build a set of style links, then output them all at once. As with the `script` helper, you can optionally set the priority order for each stylesheet.

```html+php
<?php
// add a stylesheet link
$helper->styles()->add(
    '/css/middle.css',          // (string) the stylesheet href
    array('media' => 'print')   // (array) optional attributes
);

// add another one after that
$helper->styles()->add('/css/last.css');

// add one at a specific priority order
$helper->styles()->add(
    '/css/first.css',           // (string) the stylesheet href
    null,                       // (array) optional attributes
    50                          // (int) optional priority order (default 100)
);

// add a conditional stylesheet
$helper->styles()->addCond(
    'ie6',                      // (string) the condition
    '/css/ie6.css',             // (string) the stylesheet href
    array('media' => 'print'),  // (array) optional attributes
    25                          // (int) optional priority order (default 100)
);

// add an internal style
$helper->styles()->addInternal(
    '.foo{color:red;}', // (string) style
    null,              // (array) optional attributes
    1000              // (int) optional priority order (default 100)
);

// add an internal conditional style
$helper->styles()->addCondInternal(
    'ie6',                 // (string) the condition
    '.foo{color:pink;}',  // (string) style
    null,                // (array) optional attributes
    1000                // (int) optional priority order (default 100)
);

// capture an internal style
$helper->styles()->beginInternal(
    null,                // (array) optional attributes
    1000,               // (int) optional priority order (default 100)
);
echo '.bar{color:red;}';
$helper->styles()->endInternal();

// capture an internal conditional style
$helper->styles()->beginCondInternal(
    'ie6',                // (string) the condition
    null,                // (array) optional attributes
    1000                // (int) optional priority order (default 100)
);
echo '.bar{color:pink;}';
$helper->styles()->endInternal();

// output the stylesheet links
echo $helper->styles();
?>
<!--[if ie6]><link rel="stylesheet" href="/css/ie6.css" type="text/css" media="print" /><![endif]-->
<link rel="stylesheet" href="/css/first.css" type="text/css" media="screen" />
<link rel="stylesheet" href="/css/middle.css" type="text/css" media="print" />
<link rel="stylesheet" href="/css/last.css" type="text/css" media="screen" />
<style type="text/css" media="screen">.foo{color:red;}</style>
<!--[if ie6]><style type="text/css" media="screen">.foo{color:pink;}</style><![endif]-->
<style type="text/css" media="screen">.bar{color:red;}</style>
<!--[if ie6]><style type="text/css" media="screen">.bar{color:pink;}</style><![endif]-->
?>
```

Finally, you can output a one-off `<script>` tag by echoing the helper directly
with the href string and optional attributes:

```html+php
<?php
echo $helper->styles('/css/screen.css');
echo $helper->styles('/css/print.css', array(
    'media' => 'print',
))
?>
<link rel="stylesheet" href="/css/screen.css" type="text/css" media="screen" />
<link rel="stylesheet" href="/css/print.css" type="text/css" media="print" />
```

## tag

A generic tag helper.

```html+php
<?php
echo $helper->tag(
    'div',                  // (string) the tag name
    array('id' => 'foo')    // (array) optional array of attributes
);
echo $helper->tag('/div');
?>
<div id="foo"></div>
```

## title

Helper for the `<title>` tag.

```html+php
<?php
// escaped variations (can be intermixed with raw variations)

// set the title
$helper->title()->set('This & That');

// append the title
$helper->title()->append(' > Suf1');
$helper->title()->append(' > Suf2');

// prepend the title
$helper->title()->prepend('Pre1 > ');
$helper->title()->prepend('Pre2 > ');

echo $helper->title();
?>
<title>Pre2 &gt; Pre1 &gt; This &amp; That &gt; Suf1 &gt; Suf2</title>

<?php
// raw variations (can be intermixed with escaped variations):

// set the title
$helper->title()->set('This & That');

// append the title
$helper->title()->append(' > Suf1');
$helper->title()->append(' > Suf2');

// prepend the title
$helper->title()->prepend('Pre1 > ');
$helper->title()->prepend('Pre2 > ');

echo $helper->title();
?>
<title>Pre2 > Pre1 > This & That > Suf1 > Suf2</title>
```

## ul

Helper for `<ul>` tags with `<li>` items.  Build the set of items (both raw and escaped) then output them all at once.

```html+php
<?php
// start the list of items
$helper->ul(array(                  // (array) optional attributes
    'id' => 'test',
));

// add a single item to be escaped
$helper->ul()->item(
    'foo',                          // (string) the item text
    array('id' => 'foo')            // (array) optional attributes
);

// add several items to be escaped
$helper->ul()->items(array(         // (array) the items to add
    'bar',                          // the item text, no item attributes
    'baz' => array('id' => 'baz'),  // item text with item attributes
));

// add a single raw item not to be escaped
$helper->ul()->rawItem(
    '<a href="/first">First</a>',   // (string) the raw item html
    array('id' => 'first')          // (array) optional attributes
);

// add several raw items not to be escaped
$helper->ul()->rawItems(array(      // (array) the raw items to add
    '<a href="/prev">Prev</a>',     // the item text, no item attributes
    '<a href="/next">Next</a>',
    '<a href="/last">Last</a>' => array('id' => 'last') // text and attributes
));

// output the list
echo $helper->ul();
?>
<ul id="test">
    <li id="foo">foo</li>
    <li>bar</li>
    <li id="baz">baz</li>
    <li><a href="/first">First</a></li>
    <li><a href="/prev">Prev</a></li>
    <li><a href="/next">Next</a></li>
    <li><a href="/last">Last</a></li>
</ul>
```

## void

Helper for self closing void tags.

```html+php
<?php
echo $helper->void('meta', ['itemprop' => 'duration', 'content' => 'T1M33S']);
echo $helper->void('br');
?>
<meta itemprop="duration" content="T1M33S" />
<br  />
```
