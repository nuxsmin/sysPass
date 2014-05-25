<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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
class ImportException extends Exception
{
    private $type;
    private $hint;

    public function __construct($type, $message, $hint, $code = 0, Exception $previous = null)
    {
        $this->type = $type;
        $this->hint = $hint;
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
    }

    public function getHint()
    {
        return $this->hint;
    }

    public function getType()
    {
        return $this->type;
    }

}

/**
 * Esta clase es la encargada de importar cuentas.
 */
class SP_Import
{
    private static $result = array();
    private static $fileContent;
    private static $tmpFile;

    /**
     * @brief Iniciar la importación de cuentas
     * @param array $fileData con los datos del archivo
     * @return array resultado del proceso
     */
    public static function doImport(&$fileData)
    {
        try {
            self::readDataFromFile($fileData);
        } catch (ImportException $e) {
            $message['action'] = _('Importar Cuentas');
            $message['text'][] = $e->getMessage();

            SP_Log::wrLogInfo($message);
            self::$result['error'][] = array('type' => $e->getType(), 'description' => $e->getMessage(), 'hint' => $e->getHint());
            return (self::$result);
        }

        self::$result['ok'][] = _('Importación finalizada');
        self::$result['ok'][] = _('Revise el registro de eventos para más detalles');

        return (self::$result);
    }

    /**
     * @brief Leer los datos del archivo
     * @param array $fileData con los datos del archivo
     * @throws ImportException
     * @return bool
     */
    private static function readDataFromFile(&$fileData)
    {
        if (!is_array($fileData)) {
            throw new ImportException('critical', _('Archivo no subido correctamente'), _('Verifique los permisos del usuario del servidor web'));
        }

        if ($fileData['name']) {
            // Comprobamos la extensión del archivo
            $fileExtension = strtoupper(pathinfo($fileData['name'], PATHINFO_EXTENSION));

            if ($fileExtension != 'CSV' && $fileExtension != 'XML') {
                throw new ImportException('critical', _('Tipo de archivo no soportado'), _('Compruebe la extensión del archivo'));
            }
        }

        // Variables con información del archivo
        $tmpName = $fileData['tmp_name'];

        if (!file_exists($tmpName) || !is_readable($tmpName)) {
            // Registramos el máximo tamaño permitido por PHP
            SP_Util::getMaxUpload();

            throw new ImportException('critical', _('Error interno al leer el archivo'), _('Compruebe la configuración de PHP para subir archivos'));
        }

        if ($fileData['type'] === 'text/csv'){
            // Leemos el archivo a un array
            self::$fileContent = file($tmpName);

            if (!is_array(self::$fileContent)) {
                throw new ImportException('critical', _('Error interno al leer el archivo'), _('Compruebe los permisos del directorio temporal'));
            }
            // Obtenemos las cuentas desde el archivo CSV
            self::parseFileData();
        } elseif ($fileData['type'] === 'text/xml'){
            self::$tmpFile = $tmpName;
            // Analizamos el XML y seleccionamos el formato a importar
            self::detectXMLFormat();
        } else{
            throw new ImportException('critical', _('Tipo mime no soportado'), _('Compruebe el formato del archivo'));
        }

        return true;
    }

    /**
     * @brief Leer los datos importados y formatearlos
     * @throws ImportException
     * @return bool
     */
    private static function parseFileData()
    {
        foreach (self::$fileContent as $data) {
            $fields = explode(';', $data);

            if (count($fields) < 7) {
                throw new ImportException('critical', _('El número de campos es incorrecto'), _('Compruebe el formato del archivo CSV'));
            }

            if (!self::addAccountData($fields)){
                $message['action'] = _('Importar Cuentas');
                $message['text'][] = _('Error importando cuenta');
                $message['text'][] = $data;

                SP_Log::wrLogInfo($message);
            }
        }

        return true;
    }

    /**
     * @brief Crear una cuenta con los datos obtenidos
     * @param array $data con los datos de la cuenta
     * @throws ImportException
     * @return bool
     */
    public static function addAccountData($data)
    {
        // Datos del Usuario
        $userId = SP_Common::parseParams('s', 'uid', 0);
        $groupId = SP_Common::parseParams('s', 'ugroup', 0);

        // Asignamos los valores del array a variables
        list($accountName, $customerName, $categoryName, $url, $username, $password, $notes) = $data;

        // Comprobamos si existe el cliente o lo creamos
        SP_Customer::$customerName = $customerName;
        if (!SP_Customer::checkDupCustomer()) {
            $customerId = SP_Customer::getCustomerByName();
        } else {
            SP_Customer::addCustomer();
            $customerId = SP_Customer::$customerLastId;
        }

        // Comprobamos si existe la categoría o la creamos
        $categoryId = SP_Category::getCategoryIdByName($categoryName);
        if ($categoryId == 0) {
            SP_Category::$categoryName = $categoryName;
            SP_Category::addCategory($categoryName);
            $categoryId = SP_Category::$categoryLastId;
        }

        $pass = self::encryptPass($password);

        $account = new SP_Account;
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

        // Creamos la cuenta
        return $account->createAccount();
    }

    /**
     * @brief Encriptar la clave de una cuenta
     * @param string $password con la clave de la cuenta
     * @throws ImportException
     * @return array con la clave y el IV
     */
    private static function encryptPass($password)
    {
        $crypt = new SP_Crypt;

        // Comprobar el módulo de encriptación
        if (!SP_Crypt::checkCryptModule()) {
            throw new ImportException('critical', _('Error interno'), _('No se puede usar el módulo de encriptación'));
        }

        // Encriptar clave
        $data['pass'] = $crypt->mkEncrypt($password);

        if ($data['pass'] === false || is_null($data['pass'])) {
            throw new ImportException('critical', _('Error interno'), _('Error al generar datos cifrados'));
        }

        $data['IV'] = $crypt->strInitialVector;

        return $data;
    }

    /**
     * @brief Leer el archivo de KeePass a un objeto XML
     * @throws ImportException
     * @return bool
     */
    private static function readXMLFile()
    {
        if ($xmlFile = simplexml_load_file(self::$tmpFile)){
            return $xmlFile;
        } else{
            throw new ImportException('critical', _('Error interno'), _('No es posible procesar el archivo XML'));
        }
    }

    /**
     * @brief Detectar la aplicación que generó el XML
     * @throws ImportException
     * @return bool
     */
    private static function detectXMLFormat()
    {
        $xml = self::readXMLFile();

        if ( $xml->Meta->Generator == 'KeePass' ){
            SP_KeePassImport::addKeepassAccounts($xml);
        } else if ($xmlApp = self::parseFileHeader()){
            switch ($xmlApp) {
                case 'keepassx_database':
                    SP_KeePassXImport::addKeepassXAccounts($xml);
                    break;
                case 'revelationdata':
                    error_log('REVELATION');
                    break;
                default:
                    break;
            }
        } else{
            throw new ImportException('critical', _('Archivo XML no soportado'), _('No es posible detectar la aplicación que exportó los datos'));
        }
    }

    /**
     * @brief Leer la cabecera del archivo XML y obtener patrones de aplicaciones conocidas
     * @return bool
     */
    private static function parseFileHeader()
    {
        $handle = @fopen(self::$tmpFile, "r");
        $headersRegex = '/(KEEPASSX_DATABASE|revelationdata)/i';

        if ( $handle ){
            // No. de líneas a leer como máximo
            $maxLines = 5;
            $count = 0;

            while (($buffer = fgets($handle, 4096)) !== false && $count <= $maxLines){
                if ( preg_match($headersRegex,$buffer,$app) ){
                    fclose($handle);
                    return strtolower($app[0]);
                }
                $count++;
            }

            fclose($handle);
        }

        return false;
    }
}