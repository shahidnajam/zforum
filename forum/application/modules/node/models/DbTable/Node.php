<?php
require_once(dirname(__FILE__) . '/../../NP/Db/Table/NestedSet.php');
class Node_Model_DbTable_Node extends NP_Db_Table_NestedSet
{
    protected $_left = 'lft';
    protected $_right = 'rgt';
    protected $_name = 'node';
    public function getNodes()
    {
        $orderby = array('id DESC');
        $result = $this->fetchAll('1', $orderby );
        return $result->toArray();
    }
    public function getNode( $id )
    {
        $id = (int)$id;
        $row = $this->fetchRow('id = ' . $id);
        if (!$row) {
            throw new Exception("Count not find row $id");
        }
        return $row->toArray();
    }
    /*
     * Add new posts
     */
    public function saveNode( $post )
    {
        $data = array( 'Title'=> $post['Title'],
                'Description'=> $post['Description']);
        $this->insert($data);
    }
    /*
     * Update old posts
     */
    public function updateNode( $post )
    {
        $data = array(
                'id'=> $post['id'],
                'Title'=> $post['Title'],
                'Description'=> $post['Description']);
        $where = 'id = '.$post['id'];
        $this->update($data , $where );
    }
    public function getTree($where = null, $havingDepth = null)
	  {
		  $primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);
		  $leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
		  $rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
		
		  $node = self::LEFT_TBL_ALIAS;
		  $parent = self::RIGHT_TBL_ALIAS;
		
		  $select = $this->select()->setIntegrityCheck(false)
			  ->from(array(self::LEFT_TBL_ALIAS => $this->_name), array(self::LEFT_TBL_ALIAS . '.*', 'depth' => new Zend_Db_Expr('(COUNT(' . $parent . '.' . $primary . ') - 1)')))
			  ->join(array(self::RIGHT_TBL_ALIAS => $this->_name), '(' . self::LEFT_TBL_ALIAS . '.' . $leftCol . ' BETWEEN ' . self::RIGHT_TBL_ALIAS . '.' . $leftCol . ' AND ' . self::RIGHT_TBL_ALIAS . '.' . $rightCol . ')', array())
        ->joinLeft('users','node.created_by=users.id',array('users.id as user_id','users.username'))				  
        ->group(self::LEFT_TBL_ALIAS . '.' . $this->_primary[1])
			  ->order(self::LEFT_TBL_ALIAS . '.' . $this->_left);
      if($havingDepth !== null)
      {
        $select->having('depth = '.$havingDepth);
      }
		
		  if ($where !== null) {
			  $this->_where($select, $where);
		  }
		  return parent::fetchAll($select);
	  }
}
