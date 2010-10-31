<?php
class User_Model_DbTable_Users extends Zend_Db_Table_Abstract
{
    protected $_name = 'users';
    public function findCredentials($username, $pwd)
    {
        $select = $this->select()
                       ->where('username = ?', $username)
                       ->where('password = ?', md5($pwd))
                       ->where('active = ?', 1);
        $row = $this->fetchRow($select);
        if($row) 
        {
            return $row;
        }
        return false;
    }
    public function add($username, $password, $email, $activationCode)
    {   
        $newUser = array(
            'username' => $username,
            'password' => md5($password),
            'email' => $email,
            'active' => 0,
            'role' => 'user',
            'code' => $activationCode,
            'created_at' => new Zend_Db_Expr('NOW()')        
        );
        return $userId = $this->insert($newUser);               
    }
    public function editProfile($userId, $profileData)
    {
        $userRowset = $this->find($userId);
        $user = $userRowset->current();
        if (!$user)
        {
            throw new Zend_Db_Table_Exception('User with id '.$userId.' is not present in the database');
        }
        
        foreach ($profileData as $k => $v)
        {
            if (in_array($k, $this->_cols))
            {
                if ($k == $this->_primary)
                {
                    throw new Zend_Db_Table_Exception('Id of user cannot be changed');
                }
                    if ($k == 'password')
                {
                    $user->password = $this->computePasswordHash($v);
                }
                else
                {
                    $user->{$k} = $v;
                }
            }            
        }
        
        $user->save();
        
        return $this;              
    }
}
