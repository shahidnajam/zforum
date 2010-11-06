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
                Zend_Loader::loadClass('Zend_Auth');
                $title          = $this->getRequest()->getPost('title');
                $content        = $this->getRequest()->getPost('content');
                $nodeTable      = new Node_Model_DbTable_Node();
                $parentId       = null;
                $identities     = Zend_Auth::getInstance()->getIdentity();
                $userId         = $identities['id'];
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

                $nodeTable->insert($nodeData,$parentId,Node_Model_DbTable_Node::LAST_CHILD);

                $this->_redirect('node/view/id/'.$parentId);
                return;
            }
        }
        $this->view->nodeForm = $nodeForm;
        $this->_helper->layout->disableLayout();
    }
    public function viewAction()
    {    
        
        if( !$this->_hasParam('id') )
        {
          $this->_helper->redirector('index');
        }
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

