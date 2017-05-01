<?php
namespace Aura\Html\Helper;

class LinksTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $links = $this->helper;
        $actual = $links();
        $this->assertInstanceOf('Aura\Html\Helper\Links', $actual);

        $actual = $links(array(
            'rel' => 'prev',
            'href' => '/path/to/prev',
        ))->__toString();
        $expect = '    <link rel="prev" href="/path/to/prev" />' . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testAddAndGet()
    {
        $links = $this->helper;

        $data = (object) array(
            'prev' => array(
                'rel' => 'prev',
                'href' => '/path/to/prev',
            ),
            'next' => array(
                'rel' => 'next',
                'href' => '/path/to/next',
            )
        );

        $links->add($data->prev);
        $links->add($data->next);

        $actual = $links->__toString();
        $expect = '    <link rel="prev" href="/path/to/prev" />' . PHP_EOL
                . '    <link rel="next" href="/path/to/next" />' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }

    public function testSetIndent()
    {
        $links = $this->helper;
        $links->setIndent('  ');

        $data = (object) array(
            'prev' => array(
                'rel' => 'prev',
                'href' => '/path/to/prev',
            ),
            'next' => array(
                'rel' => 'next',
                'href' => '/path/to/next',
            )
        );

        $links->add($data->prev);
        $links->add($data->next);

        $actual = $links->__toString();
        $expect = '  <link rel="prev" href="/path/to/prev" />' . PHP_EOL
                . '  <link rel="next" href="/path/to/next" />' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }

    public function testAddAndGetChaining()
    {
        $links = $this->helper;

        $data = (object) array(
            'prev' => array(
                'rel' => 'prev',
                'href' => '/path/to/prev',
            ),
            'next' => array(
                'rel' => 'next',
                'href' => '/path/to/next',
            )
        );

        $actual = $links->add($data->prev)
            ->add($data->next)
            ->__toString();
        $expect = '    <link rel="prev" href="/path/to/prev" />' . PHP_EOL
                . '    <link rel="next" href="/path/to/next" />' . PHP_EOL;

        $this->assertSame($expect, $actual);
    }
}
