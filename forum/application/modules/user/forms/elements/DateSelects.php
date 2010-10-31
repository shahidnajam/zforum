<?php
require_once 'Zend/Form/Element.php';

class User_Form_Element_DateSelects extends Zend_Form_Element_Xhtml 
{
    /**
     * Use formSelect view helper by default
     * @var string
     */
    public $helper = 'formDateSelects';    
    
    /**
     * This array will hold options: 
     * showEmpty - bool, if true will show and allow empty date 
     * startYear, endYear - start and end year to show
     * reverseYears - if true - years will be print from most recent backwards
     *  
     * Zend_Form_Decorator_ViewHelper will pass this array as argument to the
     * view helper, responsible for rendering this element  
     * 
     * @var array
     */
    public $options = array();  
    
    public function init()
    {
        $this->options['showEmpty'] = true;
        $this->options['startYear'] = 1900;        
        $this->options['endYear'] = (int) date("Y");
        $this->options['reverseYears'] = false;
    }
    
    public function setShowEmptyValues($value)
    {
        $this->options['showEmpty'] = (bool) $value;
        return $this;        
    }
    
    public function setStartEndYear($start = null, $end = null)
    {
        if ($start)
        {
            $this->options['startYear'] = (int) $start;            
        }
        
        if ($end)
        {
            $this->options['endYear'] = (int) $end;            
        }
        return $this;        
    }
    
    public function setReverseYears($value)
    {
        $this->options['reverseYears'] = (bool) $value;
        return $this;
    }
    
    
    /**
     * We want to get the date from our auxiliary fields here
     *
     * @param mixed $value 
     * @param mixed $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $fieldName = $this->getName();
        $auxiliaryFieldsNames = $this->getDayMonthYearFieldNames($fieldName);
        if (isset($context[$auxiliaryFieldsNames['day']]) && isset($context[$auxiliaryFieldsNames['month']]) 
                && isset($context[$auxiliaryFieldsNames['year']]))
        {
            if ($context[$auxiliaryFieldsNames['year']] == '-' 
                || $context[$auxiliaryFieldsNames['month']] == '-' 
                || $context[$auxiliaryFieldsNames['day']] == '-')
            {
                $value = null;
            }
            else
            {
                $value = str_pad($context[$auxiliaryFieldsNames['year']], 4, '0', STR_PAD_LEFT) . '-'
                    . str_pad($context[$auxiliaryFieldsNames['month']], 2, '0', STR_PAD_LEFT) . '-'
                    . str_pad($context[$auxiliaryFieldsNames['day']], 2, '0', STR_PAD_LEFT);
            }
                   
            $this->setValue($value);
        }
        
        return parent::isValid($value, $context);
    }
    
    /**
     * Makes day, month and year names from given element name. Special case is array notation.
     *
     * Given a value such as foo[bar][baz], the generated names will be
     * foo[bar][baz_day], foo[bar][baz_month] and foo[bar][baz_year]
     * I know it is bad design to have this function here and in the View Helper, 
     * but I really can't think of other way 
     *
     * @param  string $value
     * @return array
     */
    protected function getDayMonthYearFieldNames($value)
    {
        if (empty($value) || !is_string($value)) {
            return $value;
        }

        $ret = array(
                'day' => $value . '_day',
                'month' => $value . '_month',
                'year' => $value . '_year'
                );
        
        if (strstr($value, '['))
        {
            $endPos = strlen($value) - 1;
            if (']' != $value[$endPos]) {
                return $ret;
            }

            $start = strrpos($value, '[') + 1;
            $name = substr($value, $start, $endPos - $start);
            $arrayName = substr($value, 0, $start-1);
            $ret = array(
                    'day' => $arrayName . '[' . $name . '_day' . ']',
                    'month' => $arrayName . '[' . $name . '_month'  . ']',
                    'year' => $arrayName . '[' . $name . '_year' . ']'
                    );
        }
        return $ret;
    }
}
