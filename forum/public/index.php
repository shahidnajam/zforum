<?php
//session_destroy();
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library/'),
    get_include_path(),
)));
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Acl');
$acl = new Zend_Acl();
Zend_Loader::loadClass('Zend_Acl_Role');
$acl->addRole(new Zend_Acl_Role('guest'))
    ->addRole(new Zend_Acl_Role('member','guest'))
    ->addRole(new Zend_Acl_Role('redactor','member'))
    ->addRole(new Zend_Acl_Role('admin', 'redactor')); 
Zend_Loader::loadClass('Zend_Acl_Resource');
$acl->add(new Zend_Acl_Resource('node'))
    ->add(new Zend_Acl_Resource('user'))
    ->allow('guest', 'user','login')
    ->allow('guest', 'user','logout')
    ->allow('guest', 'user','register')
    ->allow('member', 'node','index')
    ->allow('member', 'node','list')
    ->allow('member', 'node','view')
    ->allow('member', 'node','edit')
    ->allow('member', 'node','create')
    ->allow('redactor', 'node','delete')
    ->allow('member', 'user','view');

Zend_Loader::loadClass('Zend_Controller_Front');
Zend_Loader::loadClass('Forum_Controller_Plugin_Acl'); 
Zend_Loader::loadClass('Zend_Registry'); 

Zend_Registry::set('acl', $acl);  
$frontController = Zend_Controller_Front::getInstance();   

Zend_Loader::loadClass('Zend_Auth');
if( $identities = Zend_Auth::getInstance()->getIdentity() )
{
  $role = $identities['role']; 
}
else
{
  $role = 'guest';
}
//$frontController->registerPlugin(new Forum_Controller_Plugin_Acl($acl, $role));

Zend_Loader::loadClass('Zend_Application');
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap()
            ->run();
