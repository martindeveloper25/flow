<?php
/**
 *
 */
class App_Auth_Adapter_AptitusDbTable extends Zend_Auth_Adapter_DbTable
{
    
    protected $_identityColumn = 'email';
    protected $_credentialColumn = 'pswd';
    protected $_rolColumn = 'rol';
    protected $_where = 'activo = 1';
    protected $_tableName = 'usuario';
    protected $_rol = null;


    public function authenticate()
    {
        $this->_authenticateSetup();

        $sel = $this->_zendDb->select()->from($this->_tableName)
                ->where($this->_identityColumn . ' = ?', $this->_identity)
                ->where($this->_rolColumn . ' like ?', $this->_rol."%")
                ->where($this->_where);
        $row = $this->_zendDb->fetchRow($sel, array(), Zend_Db::FETCH_ASSOC);
        
        if ($row != false) {
            $row['zend_auth_credential_match'] = 0;
        }
        
        if ($row) {
            $encPass = $row[$this->_credentialColumn];
            
            if (self::checkPassword($this->_credential, $encPass)) {
                $row['zend_auth_credential_match'] = 1;
            }
        }

        return $this->_authenticateValidateResult($row);
    }
    
    /**
     * Genera contraseña
     * 
     * @param string $rawPassword
     * @param string $algo Algoritmo usado para generar la contraseña. md5, sha1
     * @return string
     */
    public static function generatePassword($rawPassword, $algo='sha1')
    {
        $salt = substr(md5(rand(0, 999999) + time()), 6, 5);
        $passw = '';
        
        if ($algo == 'sha1') {
            $passw = $algo . '$' . $salt . '$' . sha1($salt . $rawPassword);
        } else {
            $passw = $algo . '$' . $salt . '$' . md5($salt . $rawPassword);
        }
        
        return $passw;
    }
    
    /**
     * Retorna true si el password es correcto
     * 
     * @param string $rawPassword
     * @param string $encPassword
     * @return bool
     */
    public static function checkPassword($rawPassword, $encPassword)
    {
        $parts = explode('$', $encPassword);
        if (count($parts) != 3) {
            return false;
        }
        
        $algo = strtolower($parts[0]);
        $salt = $parts[1];
        $encPass = $parts[2];
        
        $credentialEnc = '';
        if ($algo == 'sha1') {
            $credentialEnc = sha1($salt . $rawPassword, false);
        } else {
            $credentialEnc = md5($salt . $rawPassword, false);
        }
        
        return $credentialEnc == $encPass;
    }
    
    public function setRol($rol)
    {
        $this->_rol = $rol;
        return $this;
    }



}

