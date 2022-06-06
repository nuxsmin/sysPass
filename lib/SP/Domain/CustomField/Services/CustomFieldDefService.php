<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\CustomField\CustomFieldDefServiceInterface;
use SP\Domain\CustomField\In\CustomFieldDefRepositoryInterface;
use SP\Domain\CustomField\In\CustomFieldRepositoryInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldDefService
 *
 * @package SP\Domain\CustomField\Services
 */
final class CustomFieldDefService extends Service implements CustomFieldDefServiceInterface
{
    use ServiceItemTrait;

    protected CustomFieldDefRepositoryInterface $customFieldDefRepository;
    protected CustomFieldRepositoryInterface    $customFieldRepository;
    private DatabaseInterface                   $database;

    public function __construct(
        Application $application,
        CustomFieldDefRepositoryInterface $customFieldDefRepository,
        CustomFieldRepositoryInterface $customFieldRepository,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->customFieldDefRepository = $customFieldDefRepository;
        $this->customFieldRepository = $customFieldRepository;
        $this->database = $database;
    }


    /**
     * @param $id
     *
     * @return mixed
     */
    public static function getFieldModuleById($id)
    {
        $modules = self::getFieldModules();

        return $modules[$id] ?? $id;
    }

    /**
     * Devuelve los módulos disponibles para los campos personalizados
     */
    public static function getFieldModules(): array
    {
        return [
            ActionsInterface::ACCOUNT  => __('Accounts'),
            ActionsInterface::CATEGORY => __('Categories'),
            ActionsInterface::CLIENT   => __('Clients'),
            ActionsInterface::USER     => __('Users'),
            ActionsInterface::GROUP    => __('Groups'),
        ];
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->customFieldDefRepository->search($itemSearchData);
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function delete(int $id): CustomFieldDefService
    {
        $this->transactionAware(
            function () use ($id) {
                $this->customFieldRepository->deleteCustomFieldDefinitionData($id);

                if ($this->customFieldDefRepository->delete($id) === 0) {
                    throw new NoSuchItemException(__u('Field not found'), SPException::INFO);
                }
            },
            $this->database
        );

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void
    {
        $this->transactionAware(
            function () use ($ids) {
                $this->customFieldRepository->deleteCustomFieldDefinitionDataBatch($ids);

                if ($this->customFieldDefRepository->deleteByIdBatch($ids) !== count($ids)) {
                    throw new ServiceException(
                        __u('Error while deleting the fields'),
                        SPException::WARNING
                    );
                }
            },
            $this->database
        );
    }

    /**
     * @param  \SP\DataModel\CustomFieldDefinitionData  $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(CustomFieldDefinitionData $itemData): int
    {
        return $this->customFieldDefRepository->create($itemData);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(CustomFieldDefinitionData $itemData): void
    {
        if ($this->customFieldDefRepository->update($itemData) !== 1) {
            throw new ServiceException(__u('Error while updating the custom field'));
        }
    }

    /**
     * @throws ServiceException
     */
    public function update(CustomFieldDefinitionData $itemData)
    {
        return $this->transactionAware(
            function () use ($itemData) {
                $customFieldDefinitionData = $this->getById($itemData->getId());

                // Delete the data used by the items using the previous definition
                if ($customFieldDefinitionData->getModuleId() !== $itemData->moduleId) {
                    $this->customFieldRepository->deleteCustomFieldDefinitionData($customFieldDefinitionData->getId());
                }

                if ($this->customFieldDefRepository->update($itemData) !== 1) {
                    throw new ServiceException(__u('Error while updating the custom field'));
                }
            },
            $this->database
        );
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): CustomFieldDefinitionData
    {
        return $this->customFieldDefRepository->getById($id);
    }

    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldDefinitionData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->customFieldDefRepository->getAll()->getDataAsArray();
    }
}