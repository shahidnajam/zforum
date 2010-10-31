<?php 
function __($txt)
{
    return $txt;
}
class User_Form_Register extends Zend_Form
{
    public function __construct( $dbTable )   
    {
        
       parent::__construct();

        $this->setAction('/user/register')->setMethod('post')->setAttrib('id', 'register');

        $filterTrim = new Zend_Filter_StringTrim();
        $validatorNotEmpty = new Zend_Validate_NotEmpty();
        $validatorNotEmpty->setMessage(__('This field is required, you cannot leave it empty'));


        $username = new Zend_Form_Element_Text('username');
        $validatorAlnum = new Zend_Validate_Alnum();
        $validatorAlnum->setMessage(__('You can use only latin letters and numbers'));
        $validatorStringLength = new Zend_Validate_StringLength(3, 32);
        $validatorStringLength->setMessages(array(
                Zend_Validate_StringLength::TOO_SHORT => __('Your username have to be between 3 and 32 symbols long'),
                Zend_Validate_StringLength::TOO_LONG => __('Your username have to be between 3 and 32 symbols long'),
            )
        );
        $validatorUniqueUsername = new User_Validator_DbUnique($dbTable, 'username');
        $validatorUniqueUsername->setMessage(__('This username is already registered, please choose another one.'));
        $username->addValidator($validatorNotEmpty, true)->setRequired(true)->setLabel('Username')
            ->addFilter($filterTrim)
            ->addValidator($validatorAlnum)
            ->addValidator($validatorStringLength)
            ->addValidator($validatorUniqueUsername);
        $this->addElement($username);

        /**
         * @todo Change this wired error messages to something more user friendly, or even use simple email regex matching validator
         */
        $email = new Zend_Form_Element_Text('email');
        $validatorHostname = new Zend_Validate_Hostname();
        $validatorHostname->setMessages(
        array(
        Zend_Validate_Hostname::IP_ADDRESS_NOT_ALLOWED  => __("'%value%' appears to be an IP address, but IP addresses are not allowed"),
        Zend_Validate_Hostname::UNKNOWN_TLD             => __("'%value%' appears to be a DNS hostname but cannot match TLD against known list"),
        Zend_Validate_Hostname::INVALID_DASH            => __("'%value%' appears to be a DNS hostname but contains a dash (-) in an invalid position"),
        Zend_Validate_Hostname::INVALID_HOSTNAME_SCHEMA => __("'%value%' appears to be a DNS hostname but cannot match against hostname schema for TLD '%tld%'"),
        Zend_Validate_Hostname::UNDECIPHERABLE_TLD      => __("'%value%' appears to be a DNS hostname but cannot extract TLD part"),
        Zend_Validate_Hostname::INVALID_HOSTNAME        => __("'%value%' does not match the expected structure for a DNS hostname"),
        Zend_Validate_Hostname::INVALID_LOCAL_NAME      => __("'%value%' does not appear to be a valid local network name"),
        Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED  => __("'%value%' appears to be a local network name but local network names are not allowed")
        )
        );

        $validatorEmail = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS, false, $validatorHostname);
        $validatorEmail->setMessages(
        array(
        Zend_Validate_EmailAddress::INVALID            => __("'%value%' is not a valid email address"),
        Zend_Validate_EmailAddress::INVALID_HOSTNAME   => __("'%hostname%' is not a valid hostname for email address '%value%'"),
        Zend_Validate_EmailAddress::INVALID_MX_RECORD  => __("'%hostname%' does not appear to have a valid MX record for the email address '%value%'"),
        Zend_Validate_EmailAddress::DOT_ATOM           => __("'%localPart%' not matched against dot-atom format"),
        Zend_Validate_EmailAddress::QUOTED_STRING      => __("'%localPart%' not matched against quoted-string format"),
        Zend_Validate_EmailAddress::INVALID_LOCAL_PART => __("'%localPart%' is not a valid local part for email address '%value%'")
        )
        );
        $validatorUniqueEmail = new User_Validator_DbUnique($dbTable, 'email');
        $validatorUniqueEmail->setMessage(__('This email address is already registered, please choose another one.'));
        $email->addValidator($validatorNotEmpty, true)->setRequired(true)->setLabel('Email Address')
            ->addFilter($filterTrim)
            ->addValidator($validatorEmail)
            ->addValidator($validatorUniqueEmail);
        $this->addElement($email);

        $password = new Zend_Form_Element_Password('password');
        $password->addValidator($validatorNotEmpty, true)->setRequired(true)->setLabel('Password')
            ->addValidator(new Zend_Validate_StringLength(3));
        $this->addElement($password);

        $password2 = new Zend_Form_Element_Password('password2');
        $validatorPassword = new User_Validator_PasswordConfirmation('password');
        $validatorPassword->setMessage(__('Passwords do not match'));
        $password2->setLabel('Confirm Password')->addValidator($validatorPassword);
        $this->addElement($password2);

        $gender = new Zend_Form_Element_Select('gender');
        $gender->setLabel('Gender')
        ->addMultiOption('',' ')->addMultiOption('male',__('male'))->addMultiOption('female',__('female'));
        $this->addElement($gender);

        /*$date = new User_Form_Element_DateSelects('birthday');
        $validatorDate = new Zend_Validate_Date();
        $validatorDate->setMessages(
            array(
                Zend_Validate_Date::INVALID => __("'%value%' is not of the format YYYY-MM-DD"),
                Zend_Validate_Date::INVALID        => __("'%value%' does not appear to be a valid date"),
                Zend_Validate_Date::FALSEFORMAT    => __("'%value%' does not fit given date format")
            )
        );
        $date->setLabel('Birthdate')->addValidator($validatorDate);
        $date->setShowEmptyValues(true)->setStartEndYear(1900, date("Y")-7)->setReverseYears(true);

        $this->addElement($date);*/

        $realName = new Zend_Form_Element_Text('realname');
        $realName->setLabel('Real Name')->addFilter($filterTrim);
        $this->addElement($realName);

        $validatorNotEmptyAgreement = new Zend_Validate_NotEmpty();
        $validatorNotEmptyAgreement->setMessage(__('You have to accept our terms and conditions before you register'));
        $agreement = new Zend_Form_Element_Checkbox('agreement');
        $agreement->setLabel('I agree to terms and conditions')
        ->addValidator($validatorNotEmptyAgreement, true)->setRequired(true);
        $this->addElement($agreement);


        /*if (!isset($this->session->passedRegisterCaptcha) || !$this->session->passedRegisterCaptcha)
        {
            //if we have set captcha in the session for this request - use it, else generate new one
            if (isset($this->session->registerCaptcha))
            {
                $captchaCode = $this->session->registerCaptcha;
            }
            else
            {
                $md5Hash = md5($_SERVER['REQUEST_TIME']);
                $captchaCode = substr($md5Hash, rand(0, 25), 5);
                $this->session->registerCaptcha = $captchaCode ;
            }

            $captcha = new Zend_Form_Element_Text('captcha');
            $validatorIdentical = new Zend_Validate_Identical($captchaCode);
            $validatorIdentical->setMessage('The text entered is not the same as the shown one, please try again.');
            $captcha->setLabel('Enter the text')
                ->addValidator($validatorIdentical, true)
                ->addValidator($validatorNotEmpty, true)->setRequired(true);
            $captchaDecorator = new User_Form_Decorator_Captcha();
            $captchaDecorator->setOption('namespace', 'User')->setOption('captchaId', 'registerCaptcha');
            $captchaDecorator->setTag('div');
            $captcha->addDecorator($captchaDecorator);
            $this->addElement($captcha);

        }*/


        $submit = new Zend_Form_Element_Submit('register');
        $submit->setLabel('Submit');
        $this->addElement($submit);

        return $form;
    }
}
