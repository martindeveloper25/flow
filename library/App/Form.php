<?php

/**
 * Description of Form
 *
 * @author Usuario
 */
class App_Form extends Zend_Form
{
    protected $_config;

    public function init()
    {
        //$this->setMethod('post');
        $this->_config = Zend_Registry::get("config");
    }

}
