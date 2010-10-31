<?php
require_once 'Zend/Validate/Abstract.php';   

class User_Validator_DbUnique extends Zend_Validate_Abstract
{
    const NOT_UNIQUE = 'dbUniqueNotUnique';
    
    protected $_messageTemplates = array(
        self::NOT_UNIQUE => "'%column%' '%value' already exists"
    );
    
    /**
    * @var array
    */
    protected $_messageVariables = array(
        'column'  => '_column',
    );
    
    /**
     * The table where to check for unique value in column 
     *
     * @var Zend_Db_Table
     */
    protected $_dbTable = NULL;
    
    /**
     * The column name where to check for unique value 
     *
     * @var string
     */
    protected $_column = '';
    
    /**
     * The values of the primary key for this row if updating - to exclude the current row from the test 
     *
     * @var array
     */
    protected $_rowPrimaryKey = NULL;
    
    public function __construct(Zend_Db_Table_Abstract $table, $column, $rowPrimaryKey = NULL)
    {
        $this->_dbTable = $table;
        $this->_column = $column;
        $this->_rowPrimaryKey = $rowPrimaryKey;
    }
    public function isValid($value)
    {
        $this->_setValue($value);
        
        $select = $this->_dbTable->select();
        $select->where($this->_dbTable->getAdapter()->quoteInto($this->_column . ' = ?', $value));
        if (isset($this->_rowPrimaryKey))
        {
            $rowPrimaryKey = (array) $this->_rowPrimaryKey;
            $info = $this->_dbTable->info();
       
            foreach ($info['primary'] as $key => $column)
            {
                $select->where($this->_dbTable->getAdapter()->quoteInto($column . ' != ?', $rowPrimaryKey[$key - 1]));                
            }
        }

        $row = $this->_dbTable->fetchAll($select);
        if ($row->count())
        {
            $this->_error();
            return false;
        }
               
        return true;
    }
}
