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

use SP\Account\Account;
use SP\DataModel\AccountData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Categories\Category;
use SP\Core\Session;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ImportBase abstracta para manejo de archivos de importación
 *
 * @package SP
 */
abstract class ImportBase
{
    /**
     * El id de usuario propietario de la cuenta.
     *
     * @var int
     */
    public $userId = 0;
    /**
     * El id del grupo propietario de la cuenta.
     *
     * @var int
     */
    public $userGroupId = 0;
    /**
     * Nombre de la categoría.
     *
     * @var string
     */
    protected $categoryName = '';
    /**
     * Nombre del cliente.
     *
     * @var string
     */
    protected $customerName = '';
    /**
     * Descrición de la categoría.
     *
     * @var string
     */
    protected $categoryDescription = '';
    /**
     * Descripción del cliente.
     *
     * @var string
     */
    protected $customerDescription = '';
    /**
     * @var FileImport
     */
    protected $file;
    /**
     * La clave de importación
     *
     * @var string
     */
    protected $importPass;

    /**
     * @return string
     */
    public function getImportPass()
    {
        return $this->importPass;
    }

    /**
     * @param string $importPass
     */
    public function setImportPass($importPass)
    {
        $this->importPass = $importPass;
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @return bool
     */
    public abstract function doImport();

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @param string $categoryName
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * @return string
     */
    public function getCategoryDescription()
    {
        return $this->categoryDescription;
    }

    /**
     * @param string $categoryDescription
     */
    public function setCategoryDescription($categoryDescription)
    {
        $this->categoryDescription = $categoryDescription;
    }

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
     * @param \SP\DataModel\AccountData $AccountData
     * @return bool
     */
    protected function addAccount(AccountData $AccountData)
    {
        if (null === $this->getUserId() || $this->getUserId() === 0) {
            $this->setUserId(Session::getUserData()->getUserId());
        }

        if (null === $this->getUserGroupId() || $this->getUserGroupId() === 0) {
            $this->setUserGroupId(Session::getUserData()->getUserGroupId());
        }

        $Account = new Account($AccountData);

        return $Account->createAccount();
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId;
    }

    /**
     * @param int $userGroupId
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = $userGroupId;
    }

    /**
     * Añadir una categoría y devolver el Id
     *
     * @param $name
     * @param $description
     * @return int
     */
    protected function addCategory($name, $description = null)
    {
        $CategoryData = new CategoryData($name, $description);

        return Category::getItem($CategoryData)->add()->getItemData()->getCategoryId();
    }

    /**
     * Añadir un cliente y devolver el Id
     *
     * @param $name
     * @param $description
     * @return int
     */
    protected function addCustomer($name, $description = null)
    {
        $CustomerData = new CustomerData($name, $description);

        return Customer::getItem($CustomerData)->add()->getItemData()->getCustomerId();
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;
    }

    /**
     * @return string
     */
    public function getCustomerDescription()
    {
        return $this->customerDescription;
    }

    /**
     * @param string $customerDescription
     */
    public function setCustomerDescription($customerDescription)
    {
        $this->customerDescription = $customerDescription;
    }
}