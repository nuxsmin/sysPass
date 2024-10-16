<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use Exception;
use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Domain\CustomField\Ports\CustomFieldDataRepository;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionRepository;

use function SP\__u;

/**
 * Class CustomFieldService
 *
 * @template T of CustomFieldDataModel
 */
final class CustomFieldData extends Service implements CustomFieldDataService
{

    public function __construct(
        Application                                      $application,
        private readonly CustomFieldDataRepository       $customFieldDataRepository,
        private readonly CustomFieldDefinitionRepository $customFieldDefinitionRepository,
        private readonly CryptInterface                  $crypt
    ) {
        parent::__construct($application);
    }

    /**
     * Decrypt custom field's data
     *
     * @throws ServiceException
     */
    public function decrypt(string $data, string $key): ?string
    {
        if (!empty($data) && !empty($key)) {
            try {
                return $this->crypt->decrypt($data, $key, $this->getMasterKeyFromContext());
            } catch (CryptException $e) {
                throw ServiceException::from($e);
            }
        }

        return null;
    }


    /**
     * Returns the data given module and item's id
     *
     * @throws ServiceException
     */
    public function getBy(int $moduleId, ?int $itemId): array
    {
        try {
            return $this->customFieldDataRepository->getForModuleAndItemId($moduleId, $itemId)->getDataAsArray();
        } catch (Exception $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * Updates an item
     *
     * @throws ServiceException
     */
    public function updateOrCreate(CustomFieldDataModel $customFieldData): void
    {
        try {
            $exists = $this->customFieldDataRepository->checkExists(
                $customFieldData->getItemId(),
                $customFieldData->getModuleId(),
                $customFieldData->getDefinitionId()
            );

            if (!$exists) {
                $this->create($customFieldData);
                return;
            }

            $data = $this->isEncrypted($customFieldData) ? $this->buildSecureData($customFieldData) : $customFieldData;

            $this->customFieldDataRepository->update($data);
        } catch (SPException $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * Creates an item
     *
     * @throws ServiceException
     */
    public function create(CustomFieldDataModel $customFieldData): void
    {
        try {
            $data = $this->isEncrypted($customFieldData) ? $this->buildSecureData($customFieldData) : $customFieldData;

            $this->customFieldDataRepository->create($data)->getLastId();
        } catch (SPException $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * @param CustomFieldDataModel $customFieldData
     * @return int|null
     */
    private function isEncrypted(CustomFieldDataModel $customFieldData): ?int
    {
        return $this->customFieldDefinitionRepository
            ->getById($customFieldData->getDefinitionId())
            ->getData(CustomFieldDefinitionModel::class)
            ->getIsEncrypted();
    }

    /**
     * @param CustomFieldDataModel $customFieldData
     * @param string|null $key
     * @return CustomFieldDataModel
     * @throws CryptException
     * @throws ServiceException
     */
    private function buildSecureData(CustomFieldDataModel $customFieldData, ?string $key = null): CustomFieldDataModel
    {
        $key = $key ?: $this->getMasterKeyFromContext();
        $securedKey = $this->crypt->makeSecuredKey($key);

        if (strlen($securedKey) > 1000) {
            throw ServiceException::error(__u('Internal error'));
        }

        return $customFieldData->mutate(
            [
                'data' => $this->crypt->encrypt($customFieldData->getData(), $securedKey, $key),
                'key' => $securedKey
            ]
        );
    }

    /**
     * Delete custom field's data
     *
     * @param array $itemsId
     * @param int $moduleId
     * @throws ServiceException
     */
    public function delete(array $itemsId, int $moduleId): void
    {
        try {
            $this->customFieldDataRepository->deleteBatch($itemsId, $moduleId);
        } catch (SPException $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * Updates an item
     *
     * @param CustomFieldDataModel $customFieldData
     * @param string $masterPass
     *
     * @return int
     * @throws ServiceException
     */
    public function updateMasterPass(CustomFieldDataModel $customFieldData, string $masterPass): int
    {
        try {
            return $this->customFieldDataRepository->update($this->buildSecureData($customFieldData, $masterPass));
        } catch (SPException $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * @return array<int, T>
     */
    public function getAll(): array
    {
        return $this->customFieldDataRepository->getAll()->getDataAsArray(CustomFieldDataModel::class);
    }

    /**
     * @return array<int, T>
     */
    public function getAllEncrypted(): array
    {
        return $this->customFieldDataRepository->getAllEncrypted()->getDataAsArray(CustomFieldDataModel::class);
    }
}
