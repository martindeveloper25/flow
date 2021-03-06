<?php

class Application_Model_Usuario extends Zend_Db_Table {

    protected $_name = 'usuario';
    protected $_primary = 'id';

    const ESTADO_INACTIVO = 0;
    const ESTADO_ACTIVO = 1;
    const ESTADO_ELIMINADO = 2;
    const TABLA = 'usuario';

    public function guardar($datos) {
        
        $fechaBD = new App_Db_Table_FechaBD;
        
        $id = 0;
        if (!empty($datos['id'])) {
            $id = (int) $datos['id'];
        }
        unset($datos['id']);

        $datos = array_intersect_key($datos, array_flip($this->_getCols()));

        if ($datos['fecha_nac'] != '')
        $datos['fecha_nac'] = $fechaBD->FechaBD($datos['fecha_nac']);
        
        if ($id > 0) {
            $cantidad = $this->update($datos, 'id = ' . $id);
            $id = ($cantidad < 1) ? 0 : $id;
        } else {
            $id = $this->insert($datos);
        }
        return $id;
    }

    public function listado() {

        return $this->getAdapter()->select()->from(array("a" => $this->_name))
                        ->joinInner(array('b' => Application_Model_Rol::TABLA), 'b.id = a.id_rol', array('nom_rol' => 'nombre'))
                        ->where('a.estado != ?', self::ESTADO_ELIMINADO)
                        ->query()->fetchAll();
    }

    public function validaLogin($user, $email) {
        
    }

    public function recursosPorUsuario($id) {

        $sql = $this->getAdapter();
        return $sql->select()->from(array('u' => $this->_name), null)
                        ->joinInner(array('r' => 'rol'), 'r.id = u.id_rol', null)
                        ->joinInner(array('rr' => 'rol_recurso'), 'r.id = rr.id_rol', null)
                        ->joinInner(array('re' => 'recurso'), 're.id = rr.id_recurso', array('nombre', 'info' => new Zend_Db_Expr("IF(re.`orden` = 1,'Lista','Página')"),
                                    'accion', 'url')
                        )->where('u.id = ?', $id)->where('re.orden <> 1')
                        ->order(array('re.padre asc', 're.orden asc'))
                        ->query()->fetchAll();
    }

}

