<?php

class Admin_MvcController extends App_Controller_Action_Admin
{
    private $_model;
    private $_form;
    private $_clase;
    
    private $_recurso;
    
    const INACTIVO = 0;
    const ACTIVO = 1;
    const ELIMINADO = 2;
    
    public function init()
    {
        parent::init();
        
        $this->view->headScript()->appendFile(SITE_URL.'/js/web/mvc.js');
        Zend_Layout::getMvcInstance()->assign('btnNuevo','1');
        
        $sesionMvc  = new Zend_Session_Namespace('sesion_mvc');
        $this->_recurso = new Application_Model_Recurso;

        if ($this->_hasParam('model')) {

                $this->_model = $this->_getParam('model');
                $form = 'Application_Form_'.ucfirst($this->_model);
                $clase = 'Application_Model_'.ucfirst($this->_model);
                $sesionMvc->form = $form;
                $sesionMvc->clase = $clase;
                $sesionMvc->model = $this->_model;
        
        }
        
        if (!class_exists($sesionMvc->form)) {
            $sesionMvc->messages = 'Clase no existe: <b>"'.$sesionMvc->form.'"</b>';
            $this->_redirect(SITE_URL.'/admin/error/error-mvc');
        }
        
        if (!class_exists($sesionMvc->clase)) {
            $sesionMvc->messages = 'Clase no existe: <b>"'.$sesionMvc->clase.'"</b>';
            $this->_redirect(SITE_URL.'/admin/error/error-mvc');
        }
        

        $this->_form = new $sesionMvc->form;
        $this->_clase = new $sesionMvc->clase;
        
    }
    
    public function indexAction()
    {
        $dataRecurso = $this->_recurso->obtenerPadre($this->_model);
        
        $funcionListado = Application_Model_Recurso::FUNCION_LISTADO;
        $padre = 0;
        $estado = 0;
        $sesionMvc  = new Zend_Session_Namespace('sesion_mvc');
        
        if ($dataRecurso) {
            $padre = $dataRecurso[0];
            $funcionListado = $dataRecurso[1];
            $estado = $dataRecurso[2];
        }
        
        if ($estado == self::INACTIVO) {
            Zend_Layout::getMvcInstance()->assign('btnNuevo','0');
            $this->render('recurso-no-activo');
        }
        else if ($estado == self::ELIMINADO) {
            $this->render('recurso-eliminado');
            
        } else {
        
            //Cuando no debe aparecer el boton nuevo
            if ($this->_model != 'contacto') //poner todo las opc posibles
                Zend_Layout::getMvcInstance()->assign('btnNuevo','1');
            else
                Zend_Layout::getMvcInstance()->assign('btnNuevo','0');
            
            $model =  ucfirst($this->_model);
            Zend_Layout::getMvcInstance()->assign('link', $this->_model);
            Zend_Layout::getMvcInstance()->assign('active', $model);
            Zend_Layout::getMvcInstance()->assign('padre', $padre);
            
            //$funcionListado:Es dinamico si se usa inner join por defecto es fetchAll
            if ($funcionListado == 'fetchAll') {
                $this->view->data = $this->_clase->$funcionListado('estado != '.self::ELIMINADO);
            } else {
                //print_r($this->_clase->$funcionListado());
                $this->view->data = $this->_clase->$funcionListado();
                
            }
            
            $this->view->model = $model;
            $this->view->active = $model.'s';
            $this->view->messages = $sesionMvc->messages;
            $this->view->tipoMessages = $sesionMvc->tipoMessages;
            
            unset($sesionMvc->messages);
            unset($sesionMvc->tipoMessages);
            
            $this->render($this->_model);
        
        }
    }
    
    public function operacionAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $generator = new Generator_Modelo;
        $sesionMvc  = new Zend_Session_Namespace('sesion_mvc');
        $primaryKey = $generator->getPrimaryKey($sesionMvc->model);
        $data = $this->_getAllParams();
        

        //Previene vulnerabilidad XSS (Cross-site scripting)
        $filtro = new Zend_Filter_StripTags();
        foreach ($data as $key => $val) {
            $data[$key] = $filtro->filter(trim($val));
        }
        
        if ($this->_getParam('ajax') == 'form') {
            
            if ($this->_hasParam('id')) {
                
                $id = $this->_getParam('id');
                if ($id != 0) {
                    $data = $this->_clase->fetchRow(''.$primaryKey.' = '.$id);
                    $this->_form->populate($data->toArray());
                }
            }
            $sessionFoto = new Zend_Session_Namespace("foto");
            unset($sessionFoto->nombre);
            echo $this->_form;         
        }
        
        if ($this->_getParam('ajax') == 'validar') {
                echo $this->_form->processAjax($data);
        }
        
        if ($this->_getParam('ajax') == 'delete') {
            
            $where = $this->getAdapter()->quoteInto(''.$primaryKey.' = ?',$data['id']);
            $this->_clase->update(array('estado' => self::ELIMINADO),$where);
            
            $sesionMvc->messages = 'Registro eliminado';
            $sesionMvc->tipoMessages = self::SUCCESS;
                    
        }
        
        if ($this->_getParam('ajax') == 'save') {
            
            $sessionFoto = new Zend_Session_Namespace("foto");
            if (isset($sessionFoto->nombre)) {
                $utilfile =   $this->_helper->getHelper('UtilFiles');
                $utilfile->_generarImagenes($sessionFoto->nombre);
                $data['imagen'] = $sessionFoto->nombre;
                unset($sessionFoto->nombre);
            }
                    
            
      
            if ($this->_getParam('scrud') == 'nuevo') {
                $data['fecha_crea'] = date("Y-m-d H:i:s");
                $data['usuario_crea'] = Zend_Auth::getInstance()->getIdentity()->id;
                $sesionMvc->messages = 'Registro agregado satisfactoriamente';
            } else {
                $data['fecha_actu'] = date("Y-m-d H:i:s");
                $data['usuario_actu'] = Zend_Auth::getInstance()->getIdentity()->id;
                $sesionMvc->messages = 'Registro actualizado satisfactoriamente';
            }
            
            $sesionMvc->tipoMessages = self::SUCCESS;
            
            $this->_clase->guardar($data);
        }
    }


}



