<?php

class Node_IndexController extends Zend_Controller_Action
{

    public function init()
    {
    }

    public function preDispatch()
    {
        if( !Zend_Auth::getInstance()->hasIdentity() ) 
        {
            $this->_redirect('user/login');
        }
    }

    public function indexAction()
    {
       
        $this->listAction();        
    }

    public function listAction()
    {
        //var_dump($this->user);
        $nodeTable = new Node_Model_DbTable_Node();
        $query = $nodeTable->getTree()->having('depth = 0');
        $result = $nodeTable->fetchAll( $query );
        $this->view->result = $result;
        return;
        $page = $this->_getParam('page',1);
        $paginator = Zend_Paginator::factory($result);
        $paginator->setItemCountPerPage(5);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;
    }

    public function viewAction()
    {    
        if( !$this->_hasParam('id') )
        {
          //$this->_forward('some-error', 'error');
          $this->_helper->redirector('index');
        }
        $nodeId = (int)$this->_getParam('id');
        $nodeTable = new Node_Model_DbTable_Node();
        $query = $nodeTable->getTree()
          ->joinLeft('users','node.created_by=users.id',array('users.id as user_id','users.username'))
          ->where('node.id='.$nodeId);
        $node = $nodeTable->fetchAll( $query );        
        $this->view->node = $node[0];
        
        $where = sprintf('node.id != %d AND node.lft BETWEEN %d AND %d',$node[0]['id'], $node[0]['lft'],$node[0]['rgt']);             
        $query = $nodeTable->getTree( $node[0]['depth']+1 )
          ->joinLeft('users','node.created_by=users.id',array('users.id as user_id','users.username'))         
          ->where($where);  
        $result = $nodeTable->fetchAll( $query );

        $page = $this->_getParam('page',1);
        $paginator = Zend_Paginator::factory($result);
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;   
        //$this->_helper->layout->disableLayout();
    }


}

