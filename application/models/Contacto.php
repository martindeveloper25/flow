<?php

class Application_Model_Contacto extends Zend_Db_Table {

    protected $_name = 'contacto';
    protected $_primary = 'id';

    const ESTADO_INACTIVO = 0;
    const ESTADO_ACTIVO = 1;
    const ESTADO_ELIMINADO = 2;
    const TABLA = 'contacto';

    public function guardar($datos) {
        $id = 0;
        if (!empty($datos["id"])) {
            $id = (int) $datos["id"];
        }

        unset($datos["id"]);
        $datos = array_intersect_key($datos, array_flip($this->_getCols()));

        if ($id > 0) {
            $cantidad = $this->update($datos, 'id = ' . $id);
            $id = ($cantidad < 1) ? 0 : $id;
        } else {
            $datos['fecha_envio'] = date('Y-m-d h:i:s');
            $id = $this->insert($datos);
        }

        return $id;
    }

    //Order by estado de respondido
    public function listado() {
        return $this->getAdapter()->select()->from($this->_name)
                ->order('respondido asc')
                ->query()->fetchAll();
    }

}

