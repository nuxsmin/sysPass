<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\Repositories\CustomField\CustomFieldRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class CustomFieldService
 *
 * @package SP\Services\CustomField
 */
final class CustomFieldService extends Service
{
    /**
     * @var CustomFieldRepository
     */
    protected $customFieldRepository;
    /**
     * @var CustomFieldDefService
     */
    protected $customFieldDefService;

    /**
     * Returns the form Id for a given name
     *
     * @param $name
     *
     * @return string
     */
    public static function getFormIdForName($name)
    {
        return 'cf_' . strtolower(preg_replace('/\W*/', '', $name));
    }

    /**
     * Desencriptar y formatear los datos del campo
     *
     * @param string $data
     * @param string $key
     *
     * @return string
     * @throws CryptoException
     * @throws ServiceException
     */
    public function decryptData($data, $key)
    {
        if (!empty($data) && !empty($key)) {
            return self::formatValue(Crypt::decrypt($data, $key, $this->getMasterKeyFromContext()));
        }

        return '';
    }

    /**
     * Formatear el valor del campo
     *
     * @param $value string El valor del campo
     *
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
     *
     * @return array
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getForModuleAndItemId($moduleId, $itemId)
    {
        return $this->customFieldRepository->getForModuleAndItemId($moduleId, $itemId)->getDataAsArray();
    }

    /**
     * Updates an item
     *
     * @param CustomFieldData $customFieldData
     *
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function updateOrCreateData(CustomFieldData $customFieldData)
    {
        $exists = $this->customFieldRepository->checkExists($customFieldData);

        // Deletes item's custom field data if value is left blank
        if ($exists && empty($customFieldData->getData())) {
            return $this->deleteCustomFieldData($customFieldData->getId(), $customFieldData->getModuleId(), $customFieldData->getDefinitionId()) === 1;
        }

        // Create item's custom field data if value is set
        if (!$exists) {
            return $this->create($customFieldData);
        }

        if ($this->customFieldDefService->getById($customFieldData->getDefinitionId())->getisEncrypted()) {
            $this->setSecureData($customFieldData);
        }

        return $this->customFieldRepository->update($customFieldData) === 1;
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int $itemId
     * @param int $moduleId
     * @param int $definitionId
     *
     * @return int
     * @throws SPException
     */
    public function deleteCustomFieldData($itemId, $moduleId, $definitionId = null)
    {
        if ($definitionId === null) {
            return $this->customFieldRepository->deleteCustomFieldData($itemId, $moduleId);
        } else {
            return $this->customFieldRepository->deleteCustomFieldDataForDefinition($itemId, $moduleId, $definitionId);
        }
    }

    /**
     * Creates an item
     *
     * @param CustomFieldData $customFieldData
     *
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function create(CustomFieldData $customFieldData)
    {
        if (empty($customFieldData->getData())) {
            return true;
        }

        if ($this->customFieldDefService->getById($customFieldData->getDefinitionId())->getisEncrypted()) {
            $this->setSecureData($customFieldData);
        }

        return $this->customFieldRepository->create($customFieldData) > 0;
    }

    /**
     * @param CustomFieldData $customFieldData
     * @param null            $key
     *
     * @throws CryptoException
     * @throws ServiceException
     */
    protected function setSecureData(CustomFieldData $customFieldData, $key = null)
    {
        $key = $key ?: $this->getMasterKeyFromContext();
        $securedKey = Crypt::makeSecuredKey($key);

        if (strlen($securedKey) > 1000) {
            throw new ServiceException(__u('Internal error'), SPException::ERROR);
        }

        $customFieldData->setData(Crypt::encrypt($customFieldData->getData(), $securedKey, $key));
        $customFieldData->setKey($securedKey);
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int $definitionId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDefinitionData($definitionId)
    {
        return $this->customFieldRepository->deleteCustomFieldDefinitionData($definitionId);
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int[] $ids
     * @param int   $moduleId
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataBatch(array $ids, $moduleId)
    {
        return $this->customFieldRepository->deleteCustomFieldDataBatch($ids, $moduleId);
    }

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param array $definitionIds
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDefinitionDataBatch(array $definitionIds)
    {
        return $this->customFieldRepository->deleteCustomFieldDefinitionDataBatch($definitionIds);
    }

    /**
     * Updates an item
     *
     * @param CustomFieldData $customFieldData
     * @param string          $masterPass
     *
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     */
    public function updateMasterPass(CustomFieldData $customFieldData, $masterPass)
    {
        $this->setSecureData($customFieldData, $masterPass);

        return $this->customFieldRepository->update($customFieldData);
    }

    /**
     * @return CustomFieldData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll()
    {
        return $this->customFieldRepository->getAll()->getDataAsArray();
    }

    /**
     * @return CustomFieldData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllEncrypted()
    {
        return $this->customFieldRepository->getAllEncrypted()->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->customFieldRepository = $this->dic->get(CustomFieldRepository::class);
        $this->customFieldDefService = $this->dic->get(CustomFieldDefService::class);
    }
}