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
     * @var array Contenido del archivo importado
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
            $file = new FileImport($fileData);

            if ($file->getFileType() === 'text/csv' || $file->getFileType() === 'application/vnd.ms-excel') {
                // Leemos el archivo a un array
                self::$_fileContent = file($file->getTmpFile());

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
                self::$_tmpFile = $file->getTmpFile();

                // Analizamos el XML y seleccionamos el formato a importar
                $xml = new XmlImport($file);
                $xml->doImport();
            } else {
                throw new SPException(
                    SPException::SP_WARNING,
                    _('Tipo mime no soportado'),
                    _('Compruebe el formato del archivo')
                );
            }
        } catch (SPException $e) {
            Log::writeNewLog(_('Importar Cuentas'), $e->getMessage());

            self::$_result['error'][] = array('type' => $e->getType(), 'description' => $e->getMessage(), 'hint' => $e->getHint());
            return (self::$_result);
        }

        self::$_result['ok'][] = _('Importación finalizada');
        self::$_result['ok'][] = _('Revise el registro de eventos para más detalles');

        return self::$_result;
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

        $pass = Crypt::encryptData($password);

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
}