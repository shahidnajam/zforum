<?php

class User_IndexController extends Zend_Controller_Action
{
    public function init()
    {
    }
    public function loginAction()
    {
        $loginForm = new User_Form_Login;
        $redirect = $this->getRequest()->getParam('redirect', 'node/index');
        $loginForm->setAttrib('redirect', $redirect );
        //$group = new Zend_Form_Element_Text('group');
         //$loginForm->addElement($group);
         //$group->setLabel('Group');
         //$loginForm->addDisplayGroup(array('group'), 'groups', array("legend" => "Group Add"));
        $auth = Zend_Auth::getInstance();
        if(Zend_Auth::getInstance()->hasIdentity()) 
        {
            $this->_redirect('/node/index');
        } 
        else if ($this->getRequest()->isPost()) 
        {
            if ( $loginForm->isValid($this->getRequest()->getPost()) ) 
            {
                $username = $this->getRequest()->getPost('username');
                $pwd = $this->getRequest()->getPost('pass');
                $authAdapter = new User_Model_AuthAdapter($username, $pwd);
                $result = $auth->authenticate($authAdapter);
                
                if(!$result->isValid()) 
                {
                    switch ($result->getCode()) 
                    {
                       case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                            $this->view->errors = 'user credentials not found';
                    }
                } 
                else 
                {
                    $this->_redirect( $redirect );
                }
            }
        }
        $this->view->loginForm = $loginForm;
        //$this->_redirect($redirectUrl);
    }
    public function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        $this->_redirect('user/login');
    }
    public function registerAction()
    {
        $usersTable = new User_Model_DbTable_Users();
        $this->view->registerForm = new User_Form_Register($usersTable);
        if ($this->getRequest()->isPost())
        {
            if ($this->view->registerForm->isValid($_POST))
            {
                $values = $this->view->registerForm->getValues();
                $activationCode = sha1(uniqid('', true)); 
                $badRegistration = false;
                try
                {
                    $newUserId = $usersTable->add($values['username'], $values['password'], $values['email'], $activationCode);
                    /*if($newUserId)
                    {
                        $tr = new Zend_Mail_Transport_Smtp('mail.example.com');
                        Zend_Mail::setDefaultTransport($tr); 
                        $mail = new Zend_Mail();
                        $mail->addTo('studio@example.com', 'Test');
                        $mail->setFrom('studio@example.com', 'Test');
                        $mail->setSubject('Demonstration - Sending Multiple Mails per SMTP Connection');
                        
                        $activationLink = Zend_Registry::get('configuration')->general->url . '/user/activate/user_id' . $userId . '/activation_code/' . $activationCode;
                        $html = new Zend_View();
                        $html->setScriptPath(APPLICATION_PATH . '/modules/user/views/emails/');
                        $html->assign('username', $values['username']);
                        $html->assign('activation_link', $activationLink);
                        $bodyText = $html->render('register.phtml');

                 
                        $mail->setBodyText($bodyText);
                        $mail->send($tr);
                    }*/
                }
                catch (Zend_Db_Statement_Exception $e)
                {
                    $message = $e->getMessage();
                    if (strpos($message, $values['email']) !== false)
                    {
                        $badRegistration = true;
                        $this->view->globalPageError = 'email';
                    }
                    else if (strpos($message, $values['username']) !== false)
                    {
                        $badRegistration = true;
                        $this->view->globalPageError = 'username';
                    }
                    else
                    {
                        throw $e;
                    }

                }
                if (!$badRegistration)
                {
                    $profileFields = array(
                        'gender' => $values['gender'],
                        'birthday' => $values['birthday'],
                        'real_name' => $values['realname']
                    );
                    $usersTable->editProfile($newUserId, $profileFields);
                    $this->session->userJustRegistered = $values['username'];
                    $this->_redirect('user/activate');
                }
            }
        }
    }
    public function activateAction()
    {        
        $parameters = $this->_getAllParams();
        if (isset($parameters['user_id']) && isset($parameters['activation_code']))
        {
            $usersTable = new User_Model_DbTable_Users();
            $rows = $usersTable->find($parameters['userId']);
            $user = $rows->current();
            if ($user)
            {                
                if (!$user->active && $user->code == $parameters['activationCode'])
                {
                    // activate the user
                    $user->active = 1; 
                    $user->save();
                    $this->view->activatedOK = true;
                }
                else if ($user->active)
                {
                    $this->view->userAlreadyActive = true;
                }
            }                        
        }
        
        if (isset($this->session->userJustRegistered))
        {
            $this->view->username = $this->session->userJustRegistered;

            // use 'user/justRegistered.phtml' template.
            $this->renderScript('user/justRegistered.phtml');
        }
    }
    public function viewAction()
    {
      if( !$this->_hasParam('id') )
      {
        $this->_helper->redirector('index');
      }
      $userId = (int)$this->_getParam('id');
      $userTable = new User_Model_DbTable_Users();
      $this->view->user = $userTable->fetchRow( $userId );
      
    }


}

