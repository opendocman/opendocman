<?php
namespace Aura\View;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class TemplateRegistryTest extends TestCase
{
    protected function set_up()
    {
        $this->template_registry = new TemplateRegistry;
    }

    public function testSetHasGet()
    {
        $foo = function () {
            return "Foo!";
        };

        $this->assertFalse($this->template_registry->has('foo'));

        $this->template_registry->set('foo', $foo);
        $this->assertTrue($this->template_registry->has('foo'));

        $this->template = $this->template_registry->get('foo');
        $this->assertSame($foo, $this->template);

        $this->expectException('Aura\View\Exception\TemplateNotFound');
        $this->template_registry->get('bar');
    }

    public function testSetString()
    {
        $this->template_registry->set('foo', __DIR__ . '/foo_template.php');
        $template = $this->template_registry->get('foo');
        $this->assertInstanceOf('Closure', $template);

        ob_start();
        $template();
        $actual = ob_get_clean();
        $expect = 'Hello Foo!';
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetPaths()
    {
        // should be no paths yet
        $expect = array();
        $actual = $this->template_registry->getPaths();
        $this->assertSame($expect, $actual);

        // set the paths
        $expect = array('/foo', '/bar', '/baz');
        $this->template_registry->setPaths($expect);
        $actual = $this->template_registry->getPaths();
        $this->assertSame($expect, $actual);
    }

    public function testPrependPath()
    {
        $this->template_registry->prependPath('/foo');
        $this->template_registry->prependPath('/bar');
        $this->template_registry->prependPath('/baz');

        $expect = array('/baz', '/bar', '/foo');
        $actual = $this->template_registry->getPaths();
        $this->assertSame($expect, $actual);
    }

    public function testAppendPath()
    {
        $this->template_registry->appendPath('/foo');
        $this->template_registry->appendPath('/bar');
        $this->template_registry->appendPath('/baz');

        $expect = array('/foo', '/bar', '/baz');
        $actual = $this->template_registry->getPaths();
        $this->assertSame($expect, $actual);
    }

    public function testSearch()
    {
        $this->template_registry = new FakeTemplateRegistry;
        $this->template_registry->appendPath('/foo');
        $this->template_registry->appendPath('/bar');
        $this->template_registry->appendPath('/baz');

        // place a file in one of the paths at random
        $paths = array('/foo', '/bar', '/baz');
        $key = array_rand($paths);
        $path = $paths[$key];
        $file = $path . DIRECTORY_SEPARATOR . 'zim.php';
        $this->template_registry->fakefs[$file] = 'fake';

        // now get it
        $expect = $file;
        $actual = $this->template_registry->get('zim');
        $this->assertSame($expect, $actual);

        // get it again for code coverage
        $actual = $this->template_registry->get('zim');
        $this->assertSame($expect, $actual);


        // test searching with a non-default template file extension
        $this->template_registry = new FakeTemplateRegistry;
        $this->template_registry->appendPath('/foo');
        $this->template_registry->setTemplateFileExtension('.phtml');
        $file = "/foo" . DIRECTORY_SEPARATOR . 'test.phtml';
        $this->template_registry->fakefs[$file] = 'fake';

        $expect = $file;
        $actual = $this->template_registry->get('test');
        $this->assertSame($expect, $actual);

        // look for a file that doesn't exist
        $this->expectException('Aura\View\Exception\TemplateNotFound');
        $actual = $this->template_registry->get('no-such-template');
    }

    public function testFindNamespaced()
    {
        $this->template_registry = new FakeTemplateRegistry;
        $this->template_registry->appendPath('/foo');
        $this->template_registry->appendPath('/bar', 'ns');

        $file = '/bar' . DIRECTORY_SEPARATOR . 'zim.php';
        $this->template_registry->fakefs[$file] = 'fake';

        $expect = $file;
        $actual = $this->template_registry->get('ns::zim');
        $this->assertSame($expect, $actual);

        // prepend
        $this->template_registry->prependPath('/bar', 'ns2');
        $this->template_registry->prependPath('/baz', 'ns2');

        $wrong = '/bar' . DIRECTORY_SEPARATOR . 'zim.php';
        $this->template_registry->fakefs[$wrong] = 'wrong';

        $file = '/baz' . DIRECTORY_SEPARATOR . 'zim.php';
        $this->template_registry->fakefs[$file] = 'new';

        $expect = $file;
        $actual = $this->template_registry->get('ns2::zim');
        $this->assertSame($expect, $actual);


        // doesnt exist
        $this->expectException('Aura\View\Exception\TemplateNotFound');
        $actual = $this->template_registry->get('ns::zam');
    }

    public function testUnregisteredNs()
    {
        $this->template_registry = new FakeTemplateRegistry;
        $this->expectException('Aura\View\Exception\TemplateNotFound');
        $actual = $this->template_registry->get('ns::no-exist');
    }

    public function testBadNamespace()
    {
        $this->template_registry = new FakeTemplateRegistry;
        $this->expectException('InvalidArgumentException');
        $actual = $this->template_registry->get('ns::wrong::format');
    }

}
