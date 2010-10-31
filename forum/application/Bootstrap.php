<?php
$configuration = new Zend_Config_Ini(dirname(__FILE__) . '/configs/application.ini', 'main');
Zend_Registry::set('configuration', $configuration);
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAppAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'App',
            'basePath' => dirname(__FILE__),
        ));
        return $autoloader;
    }
    protected function _initRoutes()
    {

        $frontController = Zend_Controller_Front::getInstance();
        $frontController->setControllerDirectory(array('node'=>'modules/node', 'user'=>'modules/user'));
        $route = new Zend_Controller_Router_Route(
            ':module/:action/*',
            array('controller'=>'index')
        );
        $router = $frontController->getRouter();
        $router->addRoute('modules', $route);
    }
}

