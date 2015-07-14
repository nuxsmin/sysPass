<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas.
 */
class Import
{
    /**
     * @var array Resultado de las operaciones
     */
    private static $_result = array();
    /**
     * @var string Contenido del archivo importado
     */
    private static $_fileContent;
    /**
     * @var string Nombre del archivo temporal
     */
    private static $_tmpFile;

    /**
     * Iniciar la importación de cuentas.
     *
     * @param array $fileData con los datos del archivo
     * @return array resultado del proceso
     */
    public static function doImport(&$fileData)
    {
        try {
            self::readDataFromFile($fileData);
        } catch (SPException $e) {
            Log::writeNewLog(_('Importar Cuentas'), $e->getMessage());

            self::$_result['error'][] = array('type' => $e->getType(), 'description' => $e->getMessage(), 'hint' => $e->getHint());
            return (self::$_result);
        }

        self::$_result['ok'][] = _('Importación finalizada');
        self::$_result['ok'][] = _('Revise el registro de eventos para más detalles');

        return (self::$_result);
    }

    /**
     * Leer los datos del archivo.
     *
     * @param array $fileData con los datos del archivo
     * @throws SPException
     * @return bool
     */
    private static function readDataFromFile(&$fileData)
    {
        if (!is_array($fileData)) {
            throw new SPException(SPException::SP_CRITICAL, _('Archivo no subido correctamente'), _('Verifique los permisos del usuario del servidor web'));
        }

        if ($fileData['name']) {
            // Comprobamos la extensión del archivo
            $fileExtension = strtoupper(pathinfo($fileData['name'], PATHINFO_EXTENSION));

            if ($fileExtension != 'CSV' && $fileExtension != 'XML') {
                throw new SPException(
                    SPException::SP_CRITICAL,
                    _('Tipo de archivo no soportado'),
                    _('Compruebe la extensión del archivo')
                );
            }
        }

        // Variables con información del archivo
        $tmpName = $fileData['tmp_name'];

        if (!file_exists($tmpName) || !is_readable($tmpName)) {
            // Registramos el máximo tamaño permitido por PHP
            Util::getMaxUpload();

            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno al leer el archivo'),
                _('Compruebe la configuración de PHP para subir archivos')
            );
        }

        if ($fileData['type'] === 'text/csv' || $fileData['type'] === 'application/vnd.ms-excel') {
            // Leemos el archivo a un array
            self::$_fileContent = file($tmpName);

            if (!is_array(self::$_fileContent)) {
                throw new SPException(
                    SPException::SP_CRITICAL,
                    _('Error interno al leer el archivo'),
                    _('Compruebe los permisos del directorio temporal')
                );
            }
            // Obtenemos las cuentas desde el archivo CSV
            self::parseFileData();
        } elseif ($fileData['type'] === 'text/xml') {
            self::$_tmpFile = $tmpName;
            // Analizamos el XML y seleccionamos el formato a importar
            self::detectXMLFormat();
        } else {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Tipo mime no soportado'),
                _('Compruebe el formato del archivo')
            );
        }

        return true;
    }

    /**
     * Leer los datos importados y formatearlos.
     *
     * @throws SPException
     * @return bool
     */
    private static function parseFileData()
    {
        foreach (self::$_fileContent as $data) {
            $fields = explode(';', $data);

            if (count($fields) < 7) {
                throw new SPException(
                    SPException::SP_CRITICAL,
                    _('El número de campos es incorrecto'),
                    _('Compruebe el formato del archivo CSV')
                );
            }

            if (!self::addAccountData($fields)) {
                $log = new Log(_('Importar Cuentas'));
                $log->addDescription(_('Error importando cuenta'));
                $log->addDescription($data);
                $log->writeLog();
            }
        }

        return true;
    }

    /**
     * Crear una cuenta con los datos obtenidos.
     *
     * @param array $data con los datos de la cuenta
     * @throws SPException
     * @return bool
     */
    public static function addAccountData($data)
    {
        // Datos del Usuario
        $userId = Session::getUserId();
        $groupId = Session::getUserGroupId();

        // Asignamos los valores del array a variables
        list($accountName, $customerName, $categoryName, $url, $username, $password, $notes) = $data;

        // Comprobamos si existe el cliente o lo creamos
        Customer::$customerName = $customerName;
        if (Customer::checkDupCustomer()) {
            $customerId = Customer::getCustomerByName();
        } else {
            Customer::addCustomer();
            $customerId = Customer::$customerLastId;
        }

        // Comprobamos si existe la categoría o la creamos
        $categoryId = Category::getCategoryIdByName($categoryName);
        if ($categoryId == 0) {
            Category::$categoryName = $categoryName;
            Category::addCategory($categoryName);
            $categoryId = Category::$categoryLastId;
        }

        $pass = self::encryptPass($password);

        $account = new Account;
        $account->setAccountName($accountName);
        $account->setAccountCustomerId($customerId);
        $account->setAccountCategoryId($categoryId);
        $account->setAccountLogin($username);
        $account->setAccountUrl($url);
        $account->setAccountPass($pass['pass']);
        $account->setAccountIV($pass['IV']);
        $account->setAccountNotes($notes);
        $account->setAccountUserId($userId);
        $account->setAccountUserGroupId($groupId);

        // Creamos la cuenta
        return $account->createAccount();
    }

    /**
     * Encriptar la clave de una cuenta.
     *
     * @param string $password con la clave de la cuenta
     * @throws SPException
     * @return array con la clave y el IV
     */
    private static function encryptPass($password)
    {
        if (empty($password)) {
            return array('pass' => '', 'IV' => '');
        }

        // Comprobar el módulo de encriptación
        if (!Crypt::checkCryptModule()) {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('No se puede usar el módulo de encriptación')
            );
        }

        // Encriptar clave
        $data['pass'] = Crypt::mkEncrypt($password);

        if (!empty($password) && ($data['pass'] === false || is_null($data['pass']))) {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('Error al generar datos cifrados')
            );
        }

        $data['IV'] = Crypt::$strInitialVector;

        return $data;
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @throws SPException
     */
    private static function detectXMLFormat()
    {
        $xml = self::readXMLFile();

        if ($xml->Meta->Generator == 'KeePass') {
            KeepassImport::addAccounts($xml);
        } else if($xml->Meta->Generator == 'sysPass') {
            $import = new SyspassImport();
            $import->addAccounts($xml);
        } else if ($xmlApp = self::parseFileHeader()) {
            switch ($xmlApp) {
                case 'keepassx_database':
                    KeepassXImport::addAccounts($xml);
                    break;
                case 'revelationdata':
                    error_log('REVELATION XML');
                    break;
                default:
                    break;
            }
        } else {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Archivo XML no soportado'),
                _('No es posible detectar la aplicación que exportó los datos')
            );
        }
    }

    /**
     * Leer el archivo de KeePass a un objeto XML.
     *
     * @throws SPException
     * @return \SimpleXMLElement Con los datos del archivo XML
     */
    private static function readXMLFile()
    {
        if ($xmlFile = simplexml_load_file(self::$_tmpFile)) {
            return $xmlFile;
        } else {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('No es posible procesar el archivo XML')
            );
        }
    }

    /**
     * Leer la cabecera del archivo XML y obtener patrones de aplicaciones conocidas.
     *
     * @return bool
     */
    private static function parseFileHeader()
    {
        $handle = @fopen(self::$_tmpFile, "r");
        $headersRegex = '/(KEEPASSX_DATABASE|revelationdata)/i';

        if ($handle) {
            // No. de líneas a leer como máximo
            $maxLines = 5;
            $count = 0;

            while (($buffer = fgets($handle, 4096)) !== false && $count <= $maxLines) {
                if (preg_match($headersRegex, $buffer, $app)) {
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