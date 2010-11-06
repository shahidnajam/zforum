<?php
class Node_Form_Node extends Zend_Form
{
    public function __construct( $options = null )
    {
        parent::__construct($options);
        $this->setName('Node');
        $title = new Zend_Form_Element_Text('title');
        $title->setLabel('Title')
                ->setRequired(true)
                ->addFilter('StripTags')
                ->addFilter('StringTrim')
                ->addValidator('NotEmpty');
        $content = new Zend_Form_Element_Textarea('content');
        $content->setLabel('Content')
                ->setRequired(false)
                ->addFilter('StripTags')
                ->addFilter('StringTrim')
                //->addValidator('NotEmpty')
                ;
        $submit = new Zend_Form_Element_Submit('submit');
        $redirect = new Zend_Form_Element_Hidden('redirect');
        $submit->setAttrib('id', 'submitbutton');
        $this->addElements( array ( $title, $content, $submit));
    }
}
