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

namespace SP\Import;

use SP\DataModel\AccountData;
use SP\Core\Crypt;
use SP\DataModel\CategoryData;
use SP\DataModel\CategoryData;
use SP\Mgmt\Customers\Customer;
use SP\Log\Log;
use SP\Mgmt\Categories\Category;
use SP\Core\Exceptions\SPException;

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
    protected $numFields = 7;
    /**
     * @var array
     */
    protected $mapFields = array();
    /**
     * @var string
     */
    protected $fieldDelimiter = ';';

    /**
     * Constructor
     *
     * @param $file FileImport Instancia de la clase FileImport
     * @throws SPException
     */
    public function __construct($file)
    {
        try {
            $this->file = $file;
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * @param string $fieldDelimiter
     */
    public function setFieldDelimiter($fieldDelimiter)
    {
        $this->fieldDelimiter = $fieldDelimiter;
    }

    /**
     * @param int $numFields
     */
    public function setNumFields($numFields)
    {
        $this->numFields = $numFields;
    }

    /**
     * @param array $mapFields
     */
    public function setMapFields($mapFields)
    {
        $this->mapFields = $mapFields;
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas
     *
     * @throws SPException
     */
    protected function processAccounts()
    {
        $line = 0;

        $lines = $this->file->getFileContent();

        foreach($lines as $data) {
            $line++;
            $fields = explode($this->fieldDelimiter, $data);
            $numfields = count($fields);

            // Comprobar el número de campos de la línea
            if ($numfields !== $this->numFields) {
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
            $customerId = Customer::getItem(new CategoryData(null, $customerName))->add()->getItemData()->getCustomerId();
            $categoryId = Category::getItem(new CategoryData(null, $categoryName))->add();
            $pass = Crypt::encryptData($password);

            // Crear la nueva cuenta
            $AccountData = new AccountData();
            $AccountData->setAccountName($accountName);
            $AccountData->setAccountLogin($login);
            $AccountData->setAccountCategoryId($categoryId);
            $AccountData->setAccountCustomerId($customerId);
            $AccountData->setAccountNotes($notes);
            $AccountData->setAccountUrl($url);
            $AccountData->setAccountPass($pass['data']);
            $AccountData->setAccountIV($pass['iv']);

            if (!$this->addAccount($AccountData)) {
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