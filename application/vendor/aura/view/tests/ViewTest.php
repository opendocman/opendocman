<?php
namespace Aura\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    protected $view;

    protected function setUp()
    {
        $view_factory = new ViewFactory;
        $this->view = $view_factory->newInstance();

        $helpers = $this->view->getHelpers();
        $helpers->set('hello', function ($name) {
            return "Hello {$name}!";
        });

        $view_registry = $this->view->getViewRegistry();
        $view_registry->set('index', function () {
            echo $this->hello($this->name);
        });
        $view_registry->set('master', function () {
            foreach (array('bar', 'baz', 'dib') as $foo) {
                echo $this->render('_partial', array(
                    'foo' => $foo,
                ));
            }
        });
        $view_registry->set('_partial', function (array $vars) {
            extract($vars);
            echo "foo = {$foo}" . PHP_EOL;
        });
        $view_registry->set('sections', function () {
            $this->setSection('foo', 'foo bar baz');
            $this->beginSection('dib');
            echo "dib zim gir";
            $this->endSection();
        });

        $layout_registry = $this->view->getLayoutRegistry();
        $layout_registry->set('default', function () {
            echo "before -- "
               . $this->getContent()
               . " -- after";
        });
        $layout_registry->set('sections', function () {
            echo $this->getSection('foo') . ' ';
            echo $this->getSection('dib') . ' ';
            if (! $this->hasSection('doom')) {
                echo 'irk';
            }
        });
    }

    public function testInvalidHelpersObject()
    {
        $this->setExpectedException('Aura\View\Exception\InvalidHelpersObject');
        new View(new TemplateRegistry, new TemplateRegistry, 'invalid');
    }

    public function testMagicMethods()
    {
        $this->assertFalse(isset($this->view->foo));

        $this->view->foo = 'bar';
        $this->assertTrue(isset($this->view->foo));
        $this->assertSame('bar', $this->view->foo);

        unset($this->view->foo);
        $this->assertFalse(isset($this->view->foo));

        $actual = $this->view->hello('Helper');
        $this->assertSame('Hello Helper!', $actual);
    }

    public function testGetters()
    {
        $this->assertInstanceOf('Aura\View\TemplateRegistry', $this->view->getViewRegistry());
        $this->assertInstanceOf('Aura\View\TemplateRegistry', $this->view->getLayoutRegistry());
        $this->assertInstanceOf('Aura\View\HelperRegistry', $this->view->getHelpers());
    }

    public function testSetAddAndGetData()
    {
        $data = array('foo' => 'bar');
        $this->view->setData($data);
        $this->assertSame('bar', $this->view->foo);

        $data = array('baz' => 'dib');
        $this->view->addData($data);
        $this->assertSame('dib', $this->view->baz);

        $expect = array('foo' => 'bar', 'baz' => 'dib');
        $actual = (array) $this->view->getData();
        $this->assertSame($expect, $actual);
    }

    public function testInvokeOneStep()
    {
        $this->view->setData(array('name' => 'Index'));
        $this->view->setView('index');
        $actual = $this->view->__invoke();
        $expect = "Hello Index!";
        $this->assertSame($expect, $actual);
    }

    public function testInvokeTwoStep()
    {
        $this->view->setData(array('name' => 'Index'));
        $this->view->setView('index');
        $this->view->setLayout('default');
        $actual = $this->view->__invoke();
        $expect = "before -- Hello Index! -- after";
        $this->assertSame($expect, $actual);
    }

    public function testPartial()
    {
        $this->view->setView('master');
        $actual = $this->view->__invoke();
        $expect = "foo = bar" . PHP_EOL
                . "foo = baz" . PHP_EOL
                . "foo = dib" . PHP_EOL;
        $this->assertSame($expect, $actual);
    }

    public function testSections()
    {
        $this->view->setView('sections');
        $this->view->setLayout('sections');
        $actual = $this->view->__invoke();
        $expect = 'foo bar baz dib zim gir irk';
        $this->assertSame($expect, $actual);
    }

    public function testMapFilesToTemplateRegistryOnConstruct()
    {
        $view = new View(
            new TemplateRegistry(array('foo' => __DIR__ . '/foo_template.php')),
            new TemplateRegistry,
            new HelperRegistry
        );
        $view->setView('foo');
        $actual = $view->__invoke();
        $expect = 'Hello Foo!';
        $this->assertSame($expect, $actual);
    }
}
