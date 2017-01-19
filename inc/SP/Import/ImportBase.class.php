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

use SP\Account\Account;
use SP\Core\Crypt;
use SP\Core\Exceptions\SPException;
use SP\Core\Messages\LogMessage;
use SP\DataModel\AccountExtData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\DataModel\TagData;
use SP\Log\Log;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Tags\Tag;

defined('APP_ROOT') || die();

/**
 * Class ImportBase abstracta para manejo de archivos de importación
 *
 * @package SP
 */
abstract class ImportBase
{
    /**
     * @var ImportParams
     */
    protected $ImportParams;
    /**
     * @var FileImport
     */
    protected $file;
    /**
     * @var LogMessage
     */
    protected $LogMessage;

    /**
     * ImportBase constructor.
     *
     * @param FileImport   $File
     * @param ImportParams $ImportParams
     */
    public function __construct(FileImport $File, ImportParams $ImportParams)
    {
        $this->file = $File;
        $this->ImportParams = $ImportParams;
        $this->LogMessage = new LogMessage(__('Importar Cuentas', false));
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @return bool
     */
    public abstract function doImport();

    /**
     * Leer la cabecera del archivo XML y obtener patrones de aplicaciones conocidas.
     *
     * @return bool
     */
    protected function parseFileHeader()
    {
        $handle = @fopen($this->file->getTmpFile(), 'r');
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

    /**
     * Añadir una cuenta desde un archivo importado.
     *
     * @param \SP\DataModel\AccountExtData $AccountData
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function addAccount(AccountExtData $AccountData)
    {
        if ($AccountData->getAccountCategoryId() === 0) {
            Log::writeNewLog(__FUNCTION__, __('Id de categoría no definido. No es posible importar cuenta.', false), Log::INFO);
            return false;
        } elseif ($AccountData->getAccountCustomerId() === 0) {
            Log::writeNewLog(__FUNCTION__, __('Id de cliente no definido. No es posible importar cuenta.', false), Log::INFO);
            return false;
        }

        if ($this->ImportParams->getImportMasterPwd() !== '') {
            $pass = Crypt::getDecrypt($AccountData->getAccountPass(), $AccountData->getAccountIV(), $this->ImportParams->getImportMasterPwd());
            $AccountData->setAccountPass($pass);
        }

        $AccountData->setAccountUserId($this->ImportParams->getDefaultUser());
        $AccountData->setAccountUserGroupId($this->ImportParams->getDefaultGroup());

        try {
            $Account = new Account($AccountData);
            $Account->createAccount();

            $this->LogMessage->addDetails(__('Cuenta creada', false), $AccountData->getAccountName());
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }

        return true;
    }

    /**
     * Añadir una categoría y devolver el Id
     *
     * @param CategoryData $CategoryData
     * @return Category|null
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function addCategory(CategoryData $CategoryData)
    {
        try {
            $Category = Category::getItem($CategoryData)->add();

            $this->LogMessage->addDetails(__('Categoría creada', false), $CategoryData->getCategoryName());

            return $Category;
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }

        return null;
    }

    /**
     * Añadir un cliente y devolver el Id
     *
     * @param CustomerData $CustomerData
     * @return Customer|null
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function addCustomer(CustomerData $CustomerData)
    {
        try {
            $Customer = Customer::getItem($CustomerData)->add();

            $this->LogMessage->addDetails(__('Cliente creado', false), $CustomerData->getCustomerName());

            return $Customer;
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }

        return null;
    }

    /**
     * Añadir una etiqueta y devolver el Id
     *
     * @param TagData $TagData
     * @return Tag|null
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function addTag(TagData $TagData)
    {
        try {
            $Tag = Tag::getItem($TagData)->add();

            $this->LogMessage->addDetails(__('Etiqueta creada', false), $TagData->getTagName());

            return $Tag;
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }

        return null;
    }
}