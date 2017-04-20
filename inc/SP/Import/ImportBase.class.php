<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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
use SP\Core\Crypt\Crypt;
use SP\Core\OldCrypt;
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
abstract class ImportBase implements ImportInterface
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
     * @var int
     */
    protected $counter = 0;
    /**
     * @var int
     */
    protected $version = 0;
    /**
     * @var bool Indica si el hash de la clave suministrada es igual a la actual
     */
    protected $mPassValidHash = false;

    /**
     * ImportBase constructor.
     *
     * @param FileImport   $File
     * @param ImportParams $ImportParams
     * @param LogMessage   $LogMessage
     */
    public function __construct(FileImport $File = null, ImportParams $ImportParams = null, LogMessage $LogMessage = null)
    {
        $this->file = $File;
        $this->ImportParams = $ImportParams;
        $this->LogMessage = null !== $LogMessage ? $LogMessage : new LogMessage(__('Importar Cuentas', false));
    }

    /**
     * @return LogMessage
     */
    public function getLogMessage()
    {
        return $this->LogMessage;
    }

    /**
     * @param LogMessage $LogMessage
     */
    public function setLogMessage($LogMessage)
    {
        $this->LogMessage = $LogMessage;
    }

    /**
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * @param ImportParams $ImportParams
     */
    public function setImportParams($ImportParams)
    {
        $this->ImportParams = $ImportParams;
    }

    /**
     * Añadir una cuenta desde un archivo importado.
     *
     * @param \SP\DataModel\AccountExtData $AccountData
     * @return bool
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

        try {
            $AccountData->setAccountUserId($this->ImportParams->getDefaultUser());
            $AccountData->setAccountUserGroupId($this->ImportParams->getDefaultGroup());

            if ($this->mPassValidHash === false && $this->ImportParams->getImportMasterPwd() !== '') {
                if ($this->version >= 210) {
                    $securedKey = Crypt::unlockSecuredKey($AccountData->getAccountKey(), $this->ImportParams->getImportMasterPwd());
                    $pass = Crypt::decrypt($AccountData->getAccountPass(), $securedKey, $this->ImportParams->getImportMasterPwd());
                } else {
                    $pass = OldCrypt::getDecrypt($AccountData->getAccountPass(), $AccountData->getAccountKey(), $this->ImportParams->getImportMasterPwd());
                }

                $AccountData->setAccountPass($pass);
                $AccountData->setAccountKey('');
            }

            $encrypt = $AccountData->getAccountKey() === '';

            $Account = new Account($AccountData);
            $Account->createAccount($encrypt);

            $this->LogMessage->addDetails(__('Cuenta creada', false), $AccountData->getAccountName());
            $this->counter++;
        } catch (SPException $e) {
            $this->LogMessage->addDetails($e->getMessage(), $AccountData->getAccountName());
            $this->LogMessage->addDetails(__('Error', false), $e->getHint());
        } catch (\Exception $e) {
            $this->LogMessage->addDetails(__('Error', false), $e->getMessage());
            $this->LogMessage->addDetails(__('Cuenta', false), $AccountData->getAccountName());
        }

        return true;
    }

    /**
     * Añadir una categoría y devolver el Id
     *
     * @param CategoryData $CategoryData
     * @return Category|null
     */
    protected function addCategory(CategoryData $CategoryData)
    {
        try {
            $Category = Category::getItem($CategoryData)->add();

            $this->LogMessage->addDetails(__('Categoría creada', false), $CategoryData->getCategoryName());

            return $Category;
        } catch (SPException $e) {
            $this->LogMessage->addDetails($e->getMessage(), $CategoryData->category_name);
            $this->LogMessage->addDetails(__('Error', false), $e->getHint());
        }

        return null;
    }

    /**
     * Añadir un cliente y devolver el Id
     *
     * @param CustomerData $CustomerData
     * @return Customer|null
     */
    protected function addCustomer(CustomerData $CustomerData)
    {
        try {
            $Customer = Customer::getItem($CustomerData)->add();

            $this->LogMessage->addDetails(__('Cliente creado', false), $CustomerData->getCustomerName());

            return $Customer;
        } catch (SPException $e) {
            $this->LogMessage->addDetails($e->getMessage(), $CustomerData->getCustomerName());
            $this->LogMessage->addDetails(__('Error', false), $e->getHint());
        }

        return null;
    }

    /**
     * Añadir una etiqueta y devolver el Id
     *
     * @param TagData $TagData
     * @return Tag|null
     */
    protected function addTag(TagData $TagData)
    {
        try {
            $Tag = Tag::getItem($TagData)->add();

            $this->LogMessage->addDetails(__('Etiqueta creada', false), $TagData->getTagName());

            return $Tag;
        } catch (SPException $e) {
            $this->LogMessage->addDetails($e->getMessage(), $TagData->getTagName());
            $this->LogMessage->addDetails(__('Error', false), $e->getHint());
        }

        return null;
    }
}