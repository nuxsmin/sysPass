<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\CustomField;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\Repositories\CustomField\CustomFieldRepository;
use SP\Services\Service;

/**
 * Class CustomFieldService
 *
 * @package SP\Services\CustomField
 */
class CustomFieldService extends Service
{
    /**
     * @var CustomFieldRepository
     */
    protected $customFieldRepository;

    /**
     * Returns the form Id for a given name
     *
     * @param $name
     * @return string
     */
    public static function getFormIdForName($name)
    {
        return 'cf_' . strtolower(preg_replace('/\W*/', '', $name));
    }

    /**
     * Desencriptar y formatear los datos del campo
     *
     * @param CustomFieldData $CustomFieldData
     * @return string
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public static function decryptData(CustomFieldData $CustomFieldData)
    {
        if ($CustomFieldData->getData() !== '') {
            $securedKey = Crypt::unlockSecuredKey($CustomFieldData->getKey(), CryptSession::getSessionKey());

            return self::formatValue(Crypt::decrypt($CustomFieldData->getData(), $securedKey));
        }

        return '';
    }

    /**
     * Formatear el valor del campo
     *
     * @param $value string El valor del campo
     * @return string
     */
    public static function formatValue($value)
    {
        if (preg_match('#https?://#', $value)) {
            return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        return $value;
    }

    /**
     * Returns the module's item for given id
     *
     * @param $moduleId
     * @param $itemId
     * @return array
     */
    public function getForModuleById($moduleId, $itemId)
    {
        return $this->customFieldRepository->getForModuleById($moduleId, $itemId);
    }

    /**
     * Updates an item
     *
     * @param CustomFieldData $customFieldData
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws SPException
     */
    public function update(CustomFieldData $customFieldData)
    {
        $exists = $this->customFieldRepository->checkExists($customFieldData);

        // Deletes item's custom field data if value is left blank
        if ($exists && $customFieldData->getData() === '') {
            return $this->deleteCustomFieldData($customFieldData->getId(), $customFieldData->getModuleId());
        }

        // Create item's custom field data if value is set
        if (!$exists && $customFieldData->getData() !== '') {
            return $this->create($customFieldData);
        }

        $this->setSecureData($customFieldData);

        return $this->customFieldRepository->update($customFieldData);
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int $id
     * @param int $moduleId
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteCustomFieldData($id, $moduleId)
    {
        return $this->customFieldRepository->deleteCustomFieldData($id, $moduleId);
    }

    /**
     * Creates an item
     *
     * @param CustomFieldData $customFieldData
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create(CustomFieldData $customFieldData)
    {
        if ($customFieldData->getData() === '') {
            return true;
        }

        $this->setSecureData($customFieldData);

        return $this->customFieldRepository->create($customFieldData);
    }

    /**
     * @param CustomFieldData $customFieldData
     * @throws CryptoException
     * @throws QueryException
     */
    protected function setSecureData(CustomFieldData $customFieldData)
    {
        $sessionKey = CryptSession::getSessionKey();
        $securedKey = Crypt::makeSecuredKey($sessionKey);

        if (strlen($securedKey) > 1000) {
            throw new QueryException(__u('Error interno'), SPException::ERROR);
        }

        $customFieldData->setData(Crypt::encrypt($customFieldData->getData(), $securedKey, $sessionKey));
        $customFieldData->setKey($securedKey);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->customFieldRepository = $this->dic->get(CustomFieldRepository::class);
    }
}