public function define(Container $di)
{
    $di->params['Aura\View\TemplateRegistry']['paths'] = array(
        dirname(__DIR__) . '/templates/views',
        dirname(__DIR__) . '/templates/layouts',
    );
    $di->set('view', $di->lazyNew('Aura\View\View'));
}
