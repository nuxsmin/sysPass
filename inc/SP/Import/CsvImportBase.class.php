<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Import;

use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;

defined('APP_ROOT') || die();

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
    protected $mapFields = [];

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

        foreach ($this->file->getFileContent() as $data) {
            $line++;
            $fields = str_getcsv($data, $this->ImportParams->getCsvDelimiter(), '"');
            $numfields = count($fields);

            // Comprobar el número de campos de la línea
            if ($numfields !== $this->numFields) {
                throw new SPException(
                    SPException::SP_CRITICAL,
                    sprintf(__('El número de campos es incorrecto (%d)', false), $numfields),
                    sprintf(__('Compruebe el formato del archivo CSV en línea %s', false), $line)
                );
            }

            // Asignar los valores del array a variables
            list($accountName, $customerName, $categoryName, $url, $login, $password, $notes) = $fields;

            // Obtener los ids de cliente y categoría
            $CustomerData = new CustomerData(null, $customerName);
            $this->addCustomer($CustomerData);
            $CategoryData = new CategoryData(null, $categoryName);
            $this->addCategory($CategoryData);

            // Crear la nueva cuenta
            $AccountData = new AccountExtData();
            $AccountData->setAccountName($accountName);
            $AccountData->setAccountLogin($login);
            $AccountData->setAccountCustomerId($CustomerData->getCustomerId());
            $AccountData->setAccountCategoryId($CategoryData->getCategoryId());
            $AccountData->setAccountNotes($notes);
            $AccountData->setAccountUrl($url);
            $AccountData->setAccountPass($password);

            try {
                $this->addAccount($AccountData);
            } catch (SPException $e) {
                $this->LogMessage->addDetails(__('Error importando cuenta', false), $accountName);
                $this->LogMessage->addDetails(__('Error procesando línea', false), $line);
                $this->LogMessage->addDetails(__('Error', false), $e->getMessage());
            }
        }
    }
}