<?php

class Node_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    /*public function preDispatch()
    {
        if( !Zend_Auth::getInstance()->hasIdentity() ) 
        {
            $this->_redirect('user/login');
        }
    }*/

    public function indexAction()
    {
       
        $this->listAction();        
    }

    public function listAction()
    {
        $posts = new Node_Model_DbTable_Node();
        $result = $posts->getTree(null,0);
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
          $this->_helper->redirector('index');
        }
        //$node = new Node_Model_DbTable_Node();
        //$result = $node->fetchAll('id = '.$nodeId,true);
        $nodeId = (int)$this->_getParam('id');
        $nodeTable = new Node_Model_DbTable_Node();
        $node = $nodeTable->getTree('node.id='.$nodeId);
        //var_dump($node);
        $this->view->node = $node[0];
        $where = sprintf('node.lft BETWEEN %d AND %d',$node[0]['lft'],$node[0]['rgt']);        
        //$childTable = new Node_Model_DbTable_Node();        
        $result = $nodeTable->getTree($where,$node[0]['depth']+1);  
        $page = $this->_getParam('page',1);
        $paginator = Zend_Paginator::factory($result);
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;   
        //$this->_helper->layout->disableLayout();
    }


}

