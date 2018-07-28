<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

use SP\Core\Acl\ActionsInterface;
use SP\DataModel\CustomFieldDefinitionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\CustomField\CustomFieldDefRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;

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
            ActionsInterface::ACCOUNT => __('Cuentas'),
            ActionsInterface::CATEGORY => __('Categorías'),
            ActionsInterface::CLIENT => __('Clientes'),
            ActionsInterface::USER => __('Usuarios'),
            ActionsInterface::GROUP => __('Grupos')
        ];

        return $modules;
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
                throw new NoSuchItemException(__u('Campo no encontrado'), NoSuchItemException::INFO);
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
                throw new ServiceException(__u('Error al eliminar los campos'), ServiceException::WARNING);
            }
        });
    }

    /**
     * @param $itemData
     *
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
                throw new ServiceException(__u('Error al actualizar el campo personalizado'));
            }
        });
    }

    /**
     * @param $id
     *
     * @return \SP\DataModel\CustomFieldDefinitionData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getById($id)
    {
        return $this->customFieldDefRepository->getById($id);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAllBasic()
    {
        return $this->customFieldDefRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->customFieldDefRepository = $this->dic->get(CustomFieldDefRepository::class);
    }
}