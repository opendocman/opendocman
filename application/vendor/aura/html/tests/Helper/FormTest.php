<?php
namespace Aura\Html\Helper;

class FormTest extends AbstractHelperTest
{
    public function test__invoke()
    {
        $form = $this->helper;
        $actual = $form(array(
            'method' => 'post',
            'action' => 'http://example.com/',
        ));
        $expect = '<form method="post" action="http://example.com/" enctype="multipart/form-data">';
        $this->assertSame($actual, $expect);
    }
}
