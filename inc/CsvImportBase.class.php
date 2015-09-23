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
 * Clase CsvImportBase para base de clases de importación desde archivos CSV
 *
 * @package SP
 */
abstract class CsvImportBase extends ImportBase
{
    /**
     * @var int
     */
    protected $_numFields = 7;
    /**
     * @var array
     */
    protected $_mapFields = array();
    /**
     * @var string
     */
    protected $_fieldDelimiter = ';';

    /**
     * Constructor
     *
     * @param $file FileImport Instancia de la clase FileImport
     * @throws SPException
     */
    public function __construct($file)
    {
        try {
            $this->_file = $file;
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * @param string $fieldDelimiter
     */
    public function setFieldDelimiter($fieldDelimiter)
    {
        $this->_fieldDelimiter = $fieldDelimiter;
    }

    /**
     * @param int $numFields
     */
    public function setNumFields($numFields)
    {
        $this->_numFields = $numFields;
    }

    /**
     * @param array $mapFields
     */
    public function setMapFields($mapFields)
    {
        $this->_mapFields = $mapFields;
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas
     *
     * @throws SPException
     */
    protected function processAccounts()
    {
        $line = 0;

        $lines = $this->_file->getFileContent();

        foreach($lines as $data) {
            $line++;
            $fields = explode($this->_fieldDelimiter, $data);
            $numfields = count($fields);

            // Comprobar el número de campos de la línea
            if ($numfields !== $this->_numFields) {
                throw new SPException(
                    SPException::SP_CRITICAL,
                    sprintf(_('El número de campos es incorrecto (%d)'), $numfields),
                    sprintf(_('Compruebe el formato del archivo CSV en línea %s'), $line)
                );
            }

            // Eliminar las " del principio/fin de los campos
            array_walk($fields,
                function(&$value, $key){
                    $value = trim($value, '"');
                }
            );

            // Asignar los valores del array a variables
            list($accountName, $customerName, $categoryName, $url, $login, $password, $notes) = $fields;

            // Obtener los ids de cliente, categoría y la clave encriptada
            $customerId = Customer::addCustomerReturnId($customerName);
            $categoryId = Category::addCategoryReturnId($categoryName);
            $pass = Crypt::encryptData($password);

            // Crear la nueva cuenta
            $this->setAccountName($accountName);
            $this->setAccountLogin($login);
            $this->setCategoryId($categoryId);
            $this->setCustomerId($customerId);
            $this->setAccountNotes($notes);
            $this->setAccountUrl($url);
            $this->setAccountPass($pass['data']);
            $this->setAccountPass($pass['iv']);

            if (!$this->addAccount()) {
                $log = new Log(_('Importar Cuentas'));
                $log->addDescription(_('Error importando cuenta'));
                $log->addDescription(sprintf(_('Error procesando línea %s'), $line));
                $log->writeLog();
            } else {
                Log::writeNewLog(_('Importar Cuentas'), sprintf(_('Cuenta importada: %s'), $accountName));
            }
        }
    }
}