<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
 *  
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Extender la clase Exception para mostrar ayuda en los mensajes
 */
class ImportException extends Exception {

    private $type;
    private $hint;

    public function __construct($type, $message, $hint, $code = 0, Exception $previous = null) {
        $this->type = $type;
        $this->hint = $hint;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
    }

    public function getHint() {
        return $this->hint;
    }

    public function getType() {
        return $this->type;
    }

}

/**
 * Esta clase es la encargada de importar cuentas.
 */
class SP_Import {

    private static $result = array();
    private static $fileContent;

    /**
     * @brief Iniciar la importación de cuentas
     * @param array $fileData con los datos del archivo
     * @return array resultado del proceso
     */
    public static function doImport(&$fileData) {
        try {
            self::readDataFromFile($fileData);
            self::parseData();
        } catch (ImportException $e) {
            $message['action'] = _('Importar Cuentas');
            $message['text'][] = $e->getMessage();

            SP_Common::wrLogInfo($message);
            self::$result['error'][] = array('type' => $e->getType(), 'description' => $e->getMessage(), 'hint' => $e->getHint());
            return(self::$result);
        }

        self::$result['ok'][] = _('Importación finalizada');
        self::$result['ok'][] = _('Revise el registro de eventos para más detalles');

        return(self::$result);
    }

    /**
     * @brief Leer los datos del archivo
     * @param array $fileData con los datos del archivo
     * @return bool
     */
    private static function readDataFromFile(&$fileData) {

        if (!is_array($fileData)) {
            throw new ImportException('critical', _('Archivo no subido correctamente'), _('Verifique los permisos del usuario del servidor web'));
        }

        if ($fileData['inFile']['name']) {
            // Comprobamos la extensión del archivo
            $fileExtension = strtoupper(pathinfo($_FILES['inFile']['name'], PATHINFO_EXTENSION));

            if ($fileExtension != 'csv') {
                throw new ImportException('critical', _('Tipo de archivo no soportado'), _('Compruebe la extensión del archivo'));
            }
        }

        // Variables con información del archivo
        $tmpName = $_FILES['inFile']['tmp_name'];

        if (!file_exists($tmpName) || !is_readable($tmpName)) {
            // Registramos el máximo tamaño permitido por PHP
            SP_Util::getMaxUpload();

            throw new ImportException('critical', _('Error interno al leer el archivo'), _('Compruebe la configuración de PHP para subir archivos'));
        }


        // Leemos el archivo a una variable
        self::$fileContent = file($tmpName);

        if (!is_array(self::$fileContent)) {
            throw new ImportException('critical', _('Error interno al leer el archivo'), _('Compruebe los permisos del directorio temporal'));
        }

        return TRUE;
    }

    /**
     * @brief Leer los datos importados y formatearlos
     * @return bool
     */
    private static function parseData() {
        // Datos del Usuario
        $userId = SP_Common::parseParams('s', 'uid', 0);
        $groupId = SP_Common::parseParams('s', 'ugroup', 0);

        $account = new SP_Account;

        foreach (self::$fileContent as $data) {
            $fields = explode(';', $data);

            if (count($fields) < 7) {
                throw new ImportException('critical', _('El número de campos es incorrecto'), _('Compruebe el formato del archivo CSV'));
            }

            list($accountName, $customerName, $categoryName, $url, $username, $password, $notes) = $fields;
            
            SP_Customer::$customerName = $customerName;
            if ( !SP_Customer::checkDupCustomer() ){
                $customerId = SP_Customer::getCustomerByName();
            } else{
                SP_Customer::addCustomer();
                $customerId = SP_Customer::$customerLastId;
            }
            
            $categoryId = SP_Category::getCategoryIdByName($categoryName);
            if ( $categoryId == 0 ){
                SP_Category::$categoryName = $categoryName;
                SP_Category::addCategory($categoryName);
                $categoryId = SP_Category::$categoryLastId;
            }
            
            $pass = self::encryptPass($password);
            
            $account->accountName = $accountName;
            $account->accountCustomerId = $customerId;
            $account->accountCategoryId = $categoryId;
            $account->accountLogin = $username;
            $account->accountUrl = $url;
            $account->accountPass = $pass['pass'];
            $account->accountIV = $pass['IV'];
            $account->accountNotes = $notes;
            $account->accountUserId = $userId;
            $account->accountUserGroupId = $groupId;
            
            if ( ! $account->createAccount() ){
                $message['action'] = _('Importar Cuentas');
                $message['text'][] = _('Error importando cuenta');
                $message['text'][] = $data;

                SP_Common::wrLogInfo($message);
            }
        }
    }
    
    /**
     * @brief Encriptar la clave de una cuenta
     * @return array con la clave y el IV
     */    
    private static function encryptPass($password){
        $crypt = new SP_Crypt;
        
        // Comprobar el módulo de encriptación
        if (!SP_Crypt::checkCryptModule()) {
            throw new ImportException('critical', _('Error interno'), _('No se puede usar el módulo de encriptación'));
        }

        // Encriptar clave
        $data['pass'] = $crypt->mkEncrypt($password);

        if ($data['pass'] === FALSE || is_null($data['pass'])) {
            throw new ImportException('critical', _('Error interno'), _('Error al generar datos cifrados'));
        }

        $data['IV'] = $crypt->strInitialVector;
        
        return $data;
    }

}
