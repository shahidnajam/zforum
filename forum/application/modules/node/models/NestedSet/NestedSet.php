<?php
include_once('Zend/Db/Table.php');
require_once('NestedSet/Exception.php');

abstract class NP_Db_Table_NestedSet extends Zend_Db_Table
{
	const FIRST_CHILD  = 'firstChild';
	const LAST_CHILD   = 'lastChild';
	const NEXT_SIBLING = 'nextSibling';
	const PREV_SIBLING = 'prevSibling';
	
	const LEFT_TBL_ALIAS  = 'node';
	const RIGHT_TBL_ALIAS = 'parent';
	
	protected $_validPositions = array(
		self::FIRST_CHILD, 
		self::LAST_CHILD, 
		self::NEXT_SIBLING, 
		self::PREV_SIBLING
	);
	
    /**
     * Left column name in nested table.
     *
     * @var string
     */
    protected $_left;
 
    /**
     * Right column name in nested table.
     *
     * @var string
     */
    protected $_right;
	
	/**
     * Constructor.
     *
	 * Supported params for $config are:
     *   left  - left column name,
     *   right - right column name
	 *
	 * @param array An array of user-specified config options.
     * @return Zend_Db_Table
     */
	public function __construct($config = array())
    {
		if (!empty($config)) {
            $this->setNestedOptions($config);
        }
		
        parent::__construct($config);
		
		$this->_setupPrimaryKey();
		
		$this->_setupLftRgt();
    }
	
	/**
	 * Sets config options.
	 *
	 * @param array Config options.
	 * @return void
	 */
	public function setNestedOptions($options)
	{
		foreach ($options as $key => $value) {
			switch ($key) {
                case 'left':
                    $this->_left = (string)$value;
                    break;
                case 'right':
                    $this->_right = (string)$value;
                    break;
                default:
                    break;
            }
		}
	}
	
	/**
	 * Defined by Zend_Db_Table_Abstract.
	 *
	 * @return void
	 */
	protected function _setupPrimaryKey()
	{
		parent::_setupPrimaryKey();
		
		if (count($this->_primary) > 1) { //Compound key?
			include_once('NP/Db/Table/NestedSet/Exception.php');
			throw new NP_Db_Table_NestedSet_Exception('Tables with compound primary key are not currently supported.');
		}
	}
	
	/**
	 * Validating supplied "left" and "right" columns.
	 *
	 * @return void
	 */
	protected function _setupLftRgt()
	{
		if (!$this->_left || !$this->_right) {
			include_once('NP/Db/Table/NestedSet/Exception.php');
			throw new NP_Db_Table_NestedSet_Exception('Both "left" and "right" column names must be supplied.');
		}
		
		$this->_setupMetadata();
		
		if (count(array_intersect(array($this->_left, $this->_right), array_keys($this->_metadata))) < 2) {
			include_once('NP/Db/Table/NestedSet/Exception.php');
			throw new NP_Db_Table_NestedSet_Exception('Supplied "left" and "right" were not found.');
		}
	}

	/**
     * Overriding fetchAll() method defined by Zend_Db_Table_Abstract.
     *
     * @param string|array|Zend_Db_Table_Select $where  	 OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
	 * @param bool 								$getAsTree   OPTIONAL Whether to retrieve nodes as tree.
	 * @param string                      		$parentAlias OPTIONAL If this argument is supplied, additional column, named after value of this argument, will be returned, containing id of a parent node will be included in result set.
     * @param string|array                      $order       OPTIONAL An SQL ORDER clause.
     * @param int                               $count  	 OPTIONAL An SQL LIMIT count.
     * @param int                               $offset 	 OPTIONAL An SQL LIMIT offset.
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function fetchAll($where = null, $getAsTree = false, $parentAlias = null, $order = null, $count = null, $offset = null)
	{
		if ($getAsTree == true) { //If geeting nodes as tree, other arguments are omitted.
			return $this->getTree($where);
		}
		elseif ($parentAlias != null) {
			return parent::fetchAll($this->getSelect($where, $parentAlias, $order, $count, $offset));
		}
		else {
			return parent::fetchAll($where, $order, $count, $offset);
		}
	}
	
	/**
     * Overriding fetchRow() method defined by Zend_Db_Table_Abstract.
     *
     * @param string|array|Zend_Db_Table_Select $where  	 OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
	 * @param string                      		$parentAlias OPTIONAL If this argument is supplied, additional column, named after value of this argument, will be returned, containing id of a parent node will be included in result set.
     * @param string|array                      $order       OPTIONAL An SQL ORDER clause.
     * @return Zend_Db_Table_Row_Abstract|null
     */
	public function fetchRow($where = null, $parentAlias = null, $order = null)
	{
		if ($parentAlias != null) {
			return parent::fetchRow($this->getSelect($where, $parentAlias, $order));
		}
		else {
			return parent::fetchRow($where, $order);
		}
	}
	
	/**
     * Generates and returns select that is used for fetchAll() and fetchRow() methods.
     *
     * @param string|array|Zend_Db_Table_Select|null $where  	  An SQL WHERE clause or Zend_Db_Table_Select object.
	 * @param string|null                      		 $parentAlias Additional column, named after value of this argument, will be returned, containing id of a parent node will be included in result set.
     * @param string|array|null                      $order       An SQL ORDER clause.
	 * @param int|null                               $count  	  OPTIONAL An SQL LIMIT count.
     * @param int|null                           	 $offset 	  OPTIONAL An SQL LIMIT offset.
     * @return Zend_Db_Table_Select
     */
	protected function getSelect($where, $parentAlias, $order, $count = null, $offset = null)
	{
		$parentAlias = (string)$parentAlias;
		
		$leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
		$rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
		
		$parentSelect = $this->select()
			->from($this->_name, array($this->_primary[1]))
			->where(self::LEFT_TBL_ALIAS . '.' . $leftCol . ' BETWEEN ' . $leftCol . '+1 AND ' . $rightCol)
			->order("$this->_left DESC")
			->limit(1);
			
		$select = $this->select()->from(array(self::LEFT_TBL_ALIAS => $this->_name), array('*', $parentAlias => "($parentSelect)"));
		
		if ($where !== null) {
			$this->_where($select, $where);
		}

		if ($order !== null) {
			$this->_order($select, $order);
		}

		if ($count !== null || $offset !== null) {
			$select->limit($count, $offset);
		}
		
		return $select;
	}
	
	public function getTree( $depth = null )
	{
		$primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);
		$leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
		$rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
		
		$node = self::LEFT_TBL_ALIAS;
		$parent = self::RIGHT_TBL_ALIAS;
		
		$select = $this->select()->setIntegrityCheck(false)
			->from(array(self::LEFT_TBL_ALIAS => $this->_name), array(self::LEFT_TBL_ALIAS . '.*', 'depth' => new Zend_Db_Expr('(COUNT(' . $parent . '.' . $primary . ') - 1)')))
			->join(array(self::RIGHT_TBL_ALIAS => $this->_name), '(' . self::LEFT_TBL_ALIAS . '.' . $leftCol . ' BETWEEN ' . self::RIGHT_TBL_ALIAS . '.' . $leftCol . ' AND ' . self::RIGHT_TBL_ALIAS . '.' . $rightCol . ')', array())		
      ->group(self::LEFT_TBL_ALIAS . '.' . $this->_primary[1])
			->order(self::LEFT_TBL_ALIAS . '.' . $this->_left);
    if($depth !== null)
    {
      $select->having('depth = '.$depth);
    }
    return $select;
	}
	
	public function insert($data, $objectiveNodeId = null, $position = self::LAST_CHILD)
	{
		if (!$this->checkNodePosition($position)) {
			include_once('NP/Db/Table/NestedSet/Exception.php');
			throw new NP_Db_Table_NestedSet_Exception('Invalid node position is supplied.');
		}
		$result = false;
		$this->getAdapter()->query("LOCK TABLE $this->_name WRITE")->_execute();
		if(parent::fetchRow('id = ' . $objectiveNodeId))
		{
		    $data = array_merge($data, $this->getLftRgt($objectiveNodeId, $position));
		    try
		    {
		        $result = parent::insert($data);
		        
		    }
		    catch(Zend_Db_Statement_Exception $e)
            {
                throw $e;
            }
        }
        $this->getAdapter()->query("UNLOCK TABLES")->_execute();
	    return $result;
	}

	public function updateNode($data, $id, $objectiveNodeId, $position = self::LAST_CHILD)
	{
		$id = (int)$id;
		$objectiveNodeId = (int)$objectiveNodeId;
		
		if (!$this->checkNodePosition($position)) {
			include_once('NP/Db/Table/NestedSet/Exception.php');
			throw new NP_Db_Table_NestedSet_Exception('Invalid node position is supplied.');
		}
		
		if ($objectiveNodeId !== $this->getCurrentObjectiveId($id, $position)) { //Objective node differs?
			$this->reduceWidth($id);
			
			$data = array_merge($data, $this->getLftRgt($objectiveNodeId, $position, $id));
		}
		
		$primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);
		$where = $this->getAdapter()->quoteInto($primary . ' = ?', $id, Zend_Db::INT_TYPE);
		
		return $this->update($data, $where);
	}
	
	/**
	 * Checks whether valid node position is supplied.
	 *
	 * @param string Position regarding on objective node.
	 * @return bool
	 */
	private function checkNodePosition($position)
	{
		if (!in_array($position, $this->_validPositions)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Deletes some node(s) and returns ids of deleted nodes.
	 *
	 * @param mixed Id of a node.
	 * @param bool Whether to delete all child nodes.
	 * @return array
	 */
	public function deleteNode($id, $recursive = false)
	{
		$retval = array();
		
		$id = (int)$id;
		
		$primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);

		if ($recursive == false) {
			$this->reduceWidth($id);
			
			//Deleting node.
			$where = $this->getAdapter()->quoteInto($primary . ' = ?', $id, Zend_Db::INT_TYPE);
			$affected = $this->delete($where);
			
			if ((int)$affected > 0) { //Only if we really deleted some nodes.	
				$retval = array($id);
			}
		}
		else {
			$tableName = $this->getAdapter()->quoteIdentifier($this->_name);
			$leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
			$rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
			
			$sql = "SELECT $leftCol, $rightCol, ($rightCol - $leftCol + 1) AS 'width' FROM $tableName WHERE $primary = " . $this->getAdapter()->quote($id, Zend_Db::INT_TYPE);
			$result = $this->getAdapter()->fetchRow($sql);
		
			$lft = $result[$this->_left];
			$rgt = $result[$this->_right];
			$width = $result['width'];
			
			$result = $this->fetchAll("$leftCol BETWEEN $lft AND $rgt"); //Getting ids of nodes that will be deleted, as those will be return value of this method.
			if ($result) {
				foreach ($result as $row) {
					$retval[] = $row[$this->_primary[1]];
				}
				
				//Deleting items.
				$this->delete("$leftCol BETWEEN $lft AND $rgt");
				
				$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol - $width")), "$leftCol > $lft");
				
				$this->update(array($this->_right=>new Zend_Db_Expr("$rightCol - $width")), "$rightCol > $rgt");
			}	
		}
		
		return $retval;
	}
	
	/**
	 * Generates left and right column value, based on id of a 
	 * objective node.
	 *
	 * @param mixed Id of a objective node.
	 * @param string Position in tree.
	 * @param int|null Id of a node for which left and right column values are being generated (optional).
	 * @return array
	 */
	protected function getLftRgt($objectiveNodeId, $position, $id = null)
	{
		$lftRgt = array();
		
		$primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);
		$leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
		$rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
		
		if ($objectiveNodeId) { //User selected some objective node?
			$result = $this->fetchRow($this->getAdapter()->quoteInto("$primary = ?", $objectiveNodeId, Zend_Db::INT_TYPE));
			$left = $result[$this->_left];
			$right = $result[$this->_right];
			
			$sql1 = '';
			$sql2 = '';
			switch ($position) {
				case self::FIRST_CHILD :
					$lftRgt[$this->_left] = $left + 1;
					$lftRgt[$this->_right] = $left + 2;
					
					$this->update(array($this->_right=>new Zend_Db_Expr("$rightCol + 2")), "$rightCol > $left");
					$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol + 2")), "$leftCol > $left");
					
					break;
				case self::LAST_CHILD :
					$lftRgt[$this->_left] = $right;
					$lftRgt[$this->_right] = $right + 1;
					
					$this->update(array($this->_right=>new Zend_Db_Expr("$rightCol + 2")), "$rightCol >= $right");
					$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol + 2")), "$leftCol > $right");
			
					break;
				case self::NEXT_SIBLING :
					$lftRgt[$this->_left] = $right + 1;
					$lftRgt[$this->_right] = $right + 2;
					
					$this->update(array($this->_right=>new Zend_Db_Expr("$rightCol + 2")), "$rightCol > $right");
					$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol + 2")), "$leftCol > $right");
					
					break;
				case self::PREV_SIBLING :
					$lftRgt[$this->_left] = $left;
					$lftRgt[$this->_right] = $left + 1;
					
					$this->update(array($this->_right=>new Zend_Db_Expr("$rightCol + 2")), "$rightCol > $left");
					$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol + 2")), "$leftCol >= $left");
					
					break;
			}
		}
		else {
			$id = (int)$id;
			$sql = "SELECT MAX($rightCol) AS 'max_rgt' FROM " . $this->getAdapter()->quoteIdentifier($this->_name) . " WHERE $primary != $id";
			$result = $this->getAdapter()->fetchRow($sql);
			
			if ($result == null) { //No data? First node...
				$lftRgt[$this->_left] = 1;
			}
			else {
				$lftRgt[$this->_left] = (int)$result['max_rgt'] + 1;
			}
			
			$lftRgt[$this->_right] = $lftRgt[$this->_left] + 1;
		}
		
		return $lftRgt;
	}
	
	/**
	 * Reduces lft and rgt values of some nodes, on which some 
	 * node that is changing position in tree, or being deleted, 
	 * has effect.
	 *
	 * @param mixed Id of a node.
	 * @return void
	 */
	protected function reduceWidth($id)
	{
		$primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);
		$leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
		$rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
		
		$sql = "SELECT $leftCol, $rightCol, ($rightCol - $leftCol + 1) AS 'width' 
			FROM " . $this->getAdapter()->quoteIdentifier($this->_name) . " 
			WHERE $primary = " . $this->getAdapter()->quote($id, Zend_Db::INT_TYPE);
		$result = $this->getAdapter()->fetchRow($sql);
		
		$left = $result[$this->_left];
		$right = $result[$this->_right];
		$width = $result['width'];
		
		if ((int)$width > 2) { //Some node that has childs.
			//Updating child nodes.
			$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol - 1"), $this->_right=>new Zend_Db_Expr("$rightCol - 1")), "$leftCol BETWEEN $left AND $right");
		}
		
		//Updating parent nodes and nodes on higher levels.
		
		$this->update(array($this->_left=>new Zend_Db_Expr("$leftCol - 2")), "$leftCol > $left AND $rightCol > $right");
		
		$this->update(array($this->_right=>new Zend_Db_Expr("$rightCol - 2")), "$rightCol > $right");
	}
	
	/**
	 * Gets id of some node's current objective node.
	 *
	 * @param mixed Node id.
	 * @param string Position in tree.
	 * @return string|null
	 */
	protected function getCurrentObjectiveId($nodeId, $position)
	{	
		$sql = '';
		
		$tableName = $this->getAdapter()->quoteIdentifier($this->_name);
		$primary = $this->getAdapter()->quoteIdentifier($this->_primary[1]);
		$leftCol = $this->getAdapter()->quoteIdentifier($this->_left);
		$rightCol = $this->getAdapter()->quoteIdentifier($this->_right);
		
		switch ($position) {
			case self::FIRST_CHILD :
				$sql = $this->getAdapter()->quoteInto("SELECT node.$primary 
				FROM $tableName node, (SELECT $leftCol, $rightCol FROM $tableName WHERE $primary = ?) AS current 
				WHERE current.$leftCol BETWEEN node.$leftCol+1 AND node.$rightCol AND current.$leftCol - node.$leftCol = 1
				ORDER BY node.$leftCol DESC", $nodeId, Zend_Db::INT_TYPE);
				
				break;
			case self::LAST_CHILD :
				$sql = $this->getAdapter()->quoteInto("SELECT node.$primary 
				FROM $tableName node, (SELECT $leftCol, $rightCol FROM $tableName WHERE $primary = ?) AS current 
				WHERE current.$leftCol BETWEEN node.$leftCol+1 AND node.$rightCol AND node.$rightCol - current.$rightCol = 1
				ORDER BY node.$leftCol DESC", $nodeId, Zend_Db::INT_TYPE);
				
				break;
			case self::NEXT_SIBLING :
				$sql = $this->getAdapter()->quoteInto("SELECT node.$primary
				FROM $tableName node, (SELECT $leftCol FROM $tableName WHERE $primary = ?) AS current 
				WHERE current.$leftCol - node.$rightCol = 1", $nodeId, Zend_Db::INT_TYPE);
				
				break;
			case self::PREV_SIBLING :
				$sql = $this->getAdapter()->quoteInto("SELECT node.$primary
				FROM $tableName node, (SELECT $rightCol FROM $tableName WHERE $primary = ?) AS current 
				WHERE node.$leftCol - current.$rightCol = 1", $nodeId, Zend_Db::INT_TYPE);
				
				break;
		}
		
		$result = $this->getAdapter()->fetchRow($sql);
		
		return $result[$this->_primary[1]];
	}
}
?>
