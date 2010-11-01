<?php
require_once(dirname(__FILE__) . '/../NestedSet/NestedSet.php');
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
}
