<?php

class Node_IndexController extends Zend_Controller_Action
{
    public function init()
    {
        Zend_Loader::loadClass('Zend_Http_Client');
        Zend_Loader::loadClass('Zend_Auth');
        $this->identities     = Zend_Auth::getInstance()->getIdentity();
        $this->client = new Zend_Http_Client;
        $this->view->setEncoding('UTF-8');
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
        $nodeTable          = new Node_Model_DbTable_Node();
        $query              = $nodeTable->getTree()->having('depth = 0');
        $result             = $nodeTable->fetchAll( $query );
        $this->view->result = $result;
    }
    public function createAction()
    {
        $nodeForm = new Node_Form_Node;
        //$redirect = $this->getRequest()->getParam('redirect', 'node/index');
        $nodeForm->setAttrib('redirect', $redirect );
        
        if ($this->getRequest()->isPost()) 
        {
            if ( $nodeForm->isValid($this->getRequest()->getPost()) ) 
            {
                $title          = $this->getRequest()->getPost('title');
                $content        = $this->getRequest()->getPost('content');
                $nodeTable      = new Node_Model_DbTable_Node();
                $parentId       = null;
                
                $userId         = $this->identities['id'];
                if( $this->_hasParam('parent_id') )
                {
                    $parentId = (int)$this->_getParam('parent_id');
                }
                $nodeData = array(
                    'title'      => $title,
                    'content'    => $content,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                    'created_by' => $userId
                );

                if( $noddeId = $nodeTable->insert($nodeData,$parentId,Node_Model_DbTable_Node::LAST_CHILD))
                {
                    $this->_redirect('node/view/id/'.$nodeId);
                }
                else
                {
                    $this->_redirect('error/error');
                }
            }
        }
        $this->view->nodeForm = $nodeForm;
        $this->_helper->layout->disableLayout();
    }
    public function editAction()
    {
        $this->client->setHeaders(array(
            'Content-type: text/html; charset=utf-8',
        ));
        $nodeForm = new Node_Form_Node;
        //$redirect = $this->getRequest()->getParam('redirect', 'node/index');
        //$nodeForm->setAttrib('redirect', $redirect );
        
        if( $this->_hasParam('id') )
        {
            $nodeId = (int)$this->_getParam('id');
        }
        else
        {
            $this->_redirect('error/error');
        }
        $nodeTable = new Node_Model_DbTable_Node();
        $nodeData = $nodeTable->fetchRow( 'id='.$nodeId )->toArray();
        Zend_Loader::loadClass('Zend_Auth');
        $identities = Zend_Auth::getInstance()->getIdentity();
        $userId = $identities['id'];
        if( (int)$nodeData['created_by'] != (int)$userId)
        {
            $this->_redirect('error/permission');
        }
        if ($this->getRequest()->isPost()) 
        {
            $formData = $this->getRequest()->getPost();
            if ( $nodeForm->isValid($this->getRequest()->getPost()) ) 
            {
                
                $title          = $this->getRequest()->getPost('title');
                $content        = $this->getRequest()->getPost('content');
                $nodeTable      = new Node_Model_DbTable_Node();
                
                $nodeData = array(
                    'title'      => $title,
                    'content'    => $content,
                    'updated_at' => date("Y-m-d H:i:s"),
                    'created_by' => $userId
                );

                if( $nodeTable->update($nodeData, 'id='.$nodeId) )
                {
                    $this->_redirect('node/view/id/'.$nodeId);
                }
                else
                {
                    $this->_redirect('error/error');
                }
            } 
            else
            {
                $nodeForm->populate($formData);
            }

        }
        else 
        {
            $nodeForm->populate( $nodeData );
        }
        $this->view->nodeForm = $nodeForm;
        //$this->_helper->layout->disableLayout();
    }
    public function viewAction()
    {    
        
        if( !$this->_hasParam('id') )
        {
          $this->_helper->redirector('index');
        }
        $this->view->user = $this->identities;
        $nodeId    = (int)$this->_getParam('id');
        $nodeTable = new Node_Model_DbTable_Node();
        $query     = $nodeTable->getTree()
          ->joinLeft('users','node.created_by=users.id',array('users.id as user_id','users.username'))
          ->where('node.id='.$nodeId);
        $node             = $nodeTable->fetchAll( $query );        
        $this->view->node = $node[0];
        $where            = sprintf('node.id != %d AND node.lft BETWEEN %d AND %d',$node[0]['id'], $node[0]['lft'],$node[0]['rgt']);             
        $query            = $nodeTable->getTree( $node[0]['depth']+1 )
          ->joinLeft('users','node.created_by=users.id',array('users.id as user_id','users.username'))         
          ->where($where);  
        $result    = $nodeTable->fetchAll( $query );
        $page      = $this->_getParam('page',1);
        $paginator = Zend_Paginator::factory($result);
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;   
        //$this->_helper->layout->disableLayout();
    }
}

