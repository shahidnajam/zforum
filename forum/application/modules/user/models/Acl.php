<?php
class Model_Acl extends Zend_Acl 
{
    public function __construct() 
    {
        $this->addRole(new Zend_Acl_Role('guest'));
        $this->addRole(new Zend_Acl_Role('user'), 'guest');
        $this->addRole(new Zend_Acl_Role('admin'), 'user');
        $this->add(new Zend_Acl_Resource('node'));
        $this->allow('user', 'node', 'view');
        $this->allow('user', 'node', 'add');
        $this->allow('user', 'node', 'edit');
    }
}
