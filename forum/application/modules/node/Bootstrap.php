<?php
class Node_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initApplication()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Node',
            'basePath' => dirname(__FILE__),
        ));
        $autoloader->addResourceType('validator', 'validators/', 'Validator')
                   ->addResourceType('form', 'forms/', 'Form')
                   ->addResourceType('model', 'models/', 'Model')
                   ->addResourceType('element', 'forms/elements/', 'Form_Element')
                   ->addResourceType('decorator', 'forms/decorators/', 'Form_Decorator');
        return $autoloader;

    }
}

