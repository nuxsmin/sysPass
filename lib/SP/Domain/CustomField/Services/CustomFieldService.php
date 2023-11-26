<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\CustomField\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\DataModel\CustomFieldData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDefRepositoryInterface;
use SP\Domain\CustomField\Ports\CustomFieldRepositoryInterface;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class CustomFieldService
 *
 * @package SP\Domain\CustomField\Services
 */
final class CustomFieldService extends Service implements CustomFieldServiceInterface
{
    protected CustomFieldRepositoryInterface    $customFieldRepository;
    protected CustomFieldDefRepositoryInterface $customFieldDefRepository;

    public function __construct(
        Application $application,
        CustomFieldRepositoryInterface $customFieldRepository,
        CustomFieldDefRepositoryInterface $customFieldDefRepository
    ) {
        parent::__construct($application);

        $this->customFieldRepository = $customFieldRepository;
        $this->customFieldDefRepository = $customFieldDefRepository;
    }

    /**
     * Returns the form Id for a given name
     */
    public static function getFormIdForName(string $name): string
    {
        return sprintf('cf_%s', strtolower(preg_replace('/\W*/', '', $name)));
    }

    /**
     * Desencriptar y formatear los datos del campo
     *
     * @throws CryptoException
     * @throws ServiceException
     */
    public function decryptData(string $data, string $key): string
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
    public static function formatValue(string $value): string
    {
        if (preg_match('#https?://#', $value)) {
            return sprintf('<a href="%s" target="_blank">%s</a>', $value, $value);
        }

        return $value;
    }

    /**
     * Returns the module's item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getForModuleAndItemId(int $moduleId, ?int $itemId): array
    {
        return $this->customFieldRepository->getForModuleAndItemId($moduleId, $itemId)->getDataAsArray();
    }

    /**
     * Updates an item
     *
     * @throws CryptoException
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function updateOrCreateData(CustomFieldData $customFieldData): bool
    {
        $exists = $this->customFieldRepository->checkExists($customFieldData);

        // Deletes item's custom field data if value is left blank
        if ($exists && empty($customFieldData->getData())) {
            return $this->deleteCustomFieldData(
                    $customFieldData->getId(),
                    $customFieldData->getModuleId(),
                    $customFieldData->getDefinitionId()
                ) === 1;
        }

        // Create item's custom field data if value is set
        if (!$exists) {
            return $this->create($customFieldData);
        }

        if ($this->customFieldDefRepository->getById($customFieldData->getDefinitionId())->getisEncrypted()) {
            $this->setSecureData($customFieldData);
        }

        return $this->customFieldRepository->update($customFieldData) === 1;
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @throws SPException
     */
    public function deleteCustomFieldData(int $itemId, int $moduleId, ?int $definitionId = null): int
    {
        if ($definitionId === null) {
            return $this->customFieldRepository->deleteCustomFieldData($itemId, $moduleId);
        }

        return $this->customFieldRepository->deleteCustomFieldDataForDefinition($itemId, $moduleId, $definitionId);
    }

    /**
     * Creates an item
     *
     * @throws CryptoException
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function create(CustomFieldData $customFieldData): bool
    {
        if (empty($customFieldData->getData())) {
            return true;
        }

        if ($this->customFieldDefRepository->getById($customFieldData->getDefinitionId())->getisEncrypted()) {
            $this->setSecureData($customFieldData);
        }

        return $this->customFieldRepository->create($customFieldData) > 0;
    }

    /**
     * @throws CryptoException
     * @throws ServiceException
     */
    protected function setSecureData(CustomFieldData $customFieldData, ?string $key = null): void
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
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDefinitionData(int $definitionId): int
    {
        return $this->customFieldRepository->deleteCustomFieldDefinitionData($definitionId);
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int[]  $ids
     * @param  int  $moduleId
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataBatch(array $ids, int $moduleId): bool
    {
        return $this->customFieldRepository->deleteCustomFieldDataBatch($ids, $moduleId);
    }

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param  int[]  $definitionIds
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDefinitionDataBatch(array $definitionIds): int
    {
        return $this->customFieldRepository->deleteCustomFieldDefinitionDataBatch($definitionIds);
    }

    /**
     * Updates an item
     *
     * @param CustomFieldData $customFieldData
     * @param  string  $masterPass
     *
     * @return int
     * @throws CryptoException
     * @throws ServiceException
     */
    public function updateMasterPass(CustomFieldData $customFieldData, string $masterPass): int
    {
        $this->setSecureData($customFieldData, $masterPass);

        return $this->customFieldRepository->update($customFieldData);
    }

    /**
     * @return CustomFieldData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll(): array
    {
        return $this->customFieldRepository->getAll()->getDataAsArray();
    }

    /**
     * @return CustomFieldData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllEncrypted(): array
    {
        return $this->customFieldRepository->getAllEncrypted()->getDataAsArray();
    }
}
