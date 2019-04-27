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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\CustomField\CustomFieldDefRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

/**
 * Class CustomFieldDefService
 *
 * @package SP\Services\CustomField
 */
final class CustomFieldDefService extends Service
{
    use ServiceItemTrait;

    /**
     * @var CustomFieldDefRepository
     */
    protected $customFieldDefRepository;
    /**
     * @var CustomFieldService
     */
    protected $customFieldService;

    /**
     * @param $id
     *
     * @return mixed
     */
    public static function getFieldModuleById($id)
    {
        $modules = self::getFieldModules();

        return isset($modules[$id]) ? $modules[$id] : $id;
    }

    /**
     * Devuelve los módulos disponibles para los campos personalizados
     *
     * @return array
     */
    public static function getFieldModules()
    {
        $modules = [
            ActionsInterface::ACCOUNT => __('Accounts'),
            ActionsInterface::CATEGORY => __('Categories'),
            ActionsInterface::CLIENT => __('Clients'),
            ActionsInterface::USER => __('Users'),
            ActionsInterface::GROUP => __('Groups')
        ];

        return $modules;
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->customFieldDefRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return CustomFieldDefService
     * @throws ServiceException
     */
    public function delete($id)
    {
        $this->transactionAware(function () use ($id) {
            $this->dic->get(CustomFieldService::class)
                ->deleteCustomFieldDefinitionData($id);

            if ($this->customFieldDefRepository->delete($id) === 0) {
                throw new NoSuchItemException(__u('Field not found'), NoSuchItemException::INFO);
            }
        });

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids)
    {
        $this->transactionAware(function () use ($ids) {
            $this->dic->get(CustomFieldService::class)
                ->deleteCustomFieldDefinitionDataBatch($ids);

            if ($this->customFieldDefRepository->deleteByIdBatch($ids) !== count($ids)) {
                throw new ServiceException(__u('Error while deleting the fields'), ServiceException::WARNING);
            }
        });
    }

    /**
     * @param $itemData
     *
     * @return mixed
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData)
    {
        return $this->customFieldDefRepository->create($itemData);
    }

    /**
     * @param CustomFieldDefinitionData $itemData
     *
     * @return mixed
     * @throws ServiceException
     */
    public function update(CustomFieldDefinitionData $itemData)
    {
        return $this->transactionAware(function () use ($itemData) {
            $customFieldDefinitionData = $this->getById($itemData->getId());

            // Delete the data used by the items using the previous definition
            if ($customFieldDefinitionData->getModuleId() !== $itemData->moduleId) {
                $this->dic->get(CustomFieldService::class)
                    ->deleteCustomFieldDefinitionData($customFieldDefinitionData->getId());
            }

            if ($this->customFieldDefRepository->update($itemData) !== 1) {
                throw new ServiceException(__u('Error while updating the custom field'));
            }
        });
    }

    /**
     * @param $id
     *
     * @return CustomFieldDefinitionData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
    {
        return $this->customFieldDefRepository->getById($id);
    }

    /**
     * @param CustomFieldDefinitionData $itemData
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(CustomFieldDefinitionData $itemData)
    {
        if ($this->customFieldDefRepository->update($itemData) !== 1) {
            throw new ServiceException(__u('Error while updating the custom field'));
        }
    }

    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldDefinitionData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic()
    {
        return $this->customFieldDefRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->customFieldDefRepository = $this->dic->get(CustomFieldDefRepository::class);
    }
}