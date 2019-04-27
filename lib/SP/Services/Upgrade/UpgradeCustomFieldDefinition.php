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

namespace SP\Services\Upgrade;

use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\CustomFieldDefDataOld;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Services\CustomField\CustomFieldTypeService;
use SP\Services\Service;
use SP\Storage\Database\Database;
use SP\Storage\Database\QueryData;
use SP\Util\Util;

/**
 * Class UpgradeCustomField
 *
 * @package SP\Services\Upgrade
 */
final class UpgradeCustomFieldDefinition extends Service
{
    /**
     * @var Database
     */
    private $db;

    /**
     * upgrade_300_18010101
     *
     * @throws Exception
     */
    public function upgrade_300_18010101()
    {
        $this->eventDispatcher->notifyEvent('upgrade.customField.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );

        try {
            $customFieldTypeService = $this->dic->get(CustomFieldTypeService::class);

            $customFieldType = [];

            foreach ($customFieldTypeService->getAll() as $customFieldTypeData) {
                $customFieldType[$customFieldTypeData->getName()] = $customFieldTypeData->getId();
            }

            $this->transactionAware(function () use ($customFieldType) {
                $customFieldDefService = $this->dic->get(CustomFieldDefService::class);

                $queryData = new QueryData();
                $queryData->setQuery('SELECT id, moduleId, field FROM CustomFieldDefinition WHERE field IS NOT NULL');

                foreach ($this->db->doSelect($queryData)->getDataAsArray() as $item) {
                    /** @var CustomFieldDefDataOld $data */
                    $data = Util::unserialize(CustomFieldDefDataOld::class, $item->field, 'SP\DataModel\CustomFieldDefData');

                    $itemData = new CustomFieldDefinitionData();
                    $itemData->setId($item->id);
                    $itemData->setModuleId($this->moduleMapper((int)$item->moduleId));
                    $itemData->setName($data->getName());
                    $itemData->setHelp($data->getHelp());
                    $itemData->setRequired($data->isRequired());
                    $itemData->setShowInList($data->isShowInItemsList());
                    $itemData->setTypeId($customFieldType[$this->typeMapper((int)$data->getType())]);

                    $customFieldDefService->updateRaw($itemData);

                    $this->eventDispatcher->notifyEvent('upgrade.customField.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Field updated'))
                            ->addDetail(__u('Field'), $data->getName()))
                    );
                }
            });
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notifyEvent('upgrade.customField.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );
    }

    /**
     * @param int $moduleId
     *
     * @return int
     */
    private function moduleMapper(int $moduleId)
    {
        switch ($moduleId) {
            case 10:
                return ActionsInterface::ACCOUNT;
            case 61:
                return ActionsInterface::CATEGORY;
            case 62:
                return ActionsInterface::CLIENT;
            case 71:
                return ActionsInterface::USER;
            case 72:
                return ActionsInterface::GROUP;
        }

        return $moduleId;
    }

    /**
     * @param int $typeId
     *
     * @return string
     */
    private function typeMapper(int $typeId)
    {
        $types = [
            1 => 'text',
            2 => 'password',
            3 => 'date',
            4 => 'number',
            5 => 'email',
            6 => 'tel',
            7 => 'url',
            8 => 'color',
            9 => 'text',
            10 => 'textarea'
        ];

        return isset($types[$typeId]) ? $types[$typeId] : $types[1];
    }

    /**
     * upgrade_300_18072901
     *
     * @throws Exception
     */
    public function upgrade_300_18072901()
    {
        $this->eventDispatcher->notifyEvent('upgrade.customField.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );

        try {
            $this->transactionAware(function () {
                $customFieldDefService = $this->dic->get(CustomFieldDefService::class);

                foreach ($customFieldDefService->getAllBasic() as $item) {

                    $itemData = clone $item;
                    $itemData->setModuleId($this->moduleMapper((int)$item->getModuleId()));

                    $customFieldDefService->updateRaw($itemData);

                    $this->eventDispatcher->notifyEvent('upgrade.customField.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Field updated'))
                            ->addDetail(__u('Field'), $item->getName()))
                    );
                }
            });
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notifyEvent('upgrade.customField.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );
    }

    /**
     * upgrade_300_19042701
     *
     * @throws Exception
     */
    public function upgrade_310_19042701()
    {
        if (!in_array('field', $this->db->getColumnsForTable('CustomFieldDefinition'))) {
            return;
        }

        $this->eventDispatcher->notifyEvent('upgrade.customField.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );

        try {
            $customFieldTypeService = $this->dic->get(CustomFieldTypeService::class);

            $customFieldType = [];

            foreach ($customFieldTypeService->getAll() as $customFieldTypeData) {
                $customFieldType[$customFieldTypeData->getName()] = $customFieldTypeData->getId();
            }

            $this->transactionAware(function () use ($customFieldType) {
                $queryData = new QueryData();
                $queryData->setQuery('SELECT id, field FROM CustomFieldDefinition WHERE field IS NOT NULL');

                foreach ($this->db->doSelect($queryData)->getDataAsArray() as $item) {
                    /** @var CustomFieldDefDataOld $data */
                    $data = Util::unserialize(CustomFieldDefDataOld::class, $item->field, 'SP\DataModel\CustomFieldDefData');

                    $typeId = $customFieldType[$this->typeMapper((int)$data->getType())];

                    $queryData = new QueryData();
                    $queryData->setQuery('UPDATE CustomFieldDefinition SET typeId = ? WHERE id = ? LIMIT 1');
                    $queryData->setParams([$typeId, $item->id]);

                    $this->db->doQuery($queryData);

                    $this->eventDispatcher->notifyEvent('upgrade.customField.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Field updated'))
                            ->addDetail(__u('Field'), $data->getName()))
                    );
                }
            });
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notifyEvent('upgrade.customField.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );
    }

    protected function initialize()
    {
        $this->db = $this->dic->get(Database::class);
    }
}