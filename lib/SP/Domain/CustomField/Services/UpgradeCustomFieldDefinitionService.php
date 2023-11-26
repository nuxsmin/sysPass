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

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\CustomFieldDefDataOld;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\Ports\CustomFieldDefServiceInterface;
use SP\Domain\CustomField\Ports\CustomFieldTypeServiceInterface;
use SP\Domain\CustomField\Ports\UpgradeCustomFieldDefinitionServiceInterface;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Util\Util;

/**
 * Class UpgradeCustomField
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradeCustomFieldDefinitionService extends Service
    implements UpgradeCustomFieldDefinitionServiceInterface
{
    protected CustomFieldDefServiceInterface $customFieldDefService;
    protected DatabaseInterface              $database;
    private CustomFieldTypeServiceInterface  $customFieldTypeService;

    public function __construct(
        Application $application,
        CustomFieldTypeServiceInterface $customFieldTypeService,
        CustomFieldDefServiceInterface $customFieldDefService,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->database = $database;
        $this->customFieldTypeService = $customFieldTypeService;
        $this->customFieldDefService = $customFieldDefService;
    }

    /**
     * upgrade_300_18010101
     *
     * @throws Exception
     */
    public function upgrade_300_18010101(): void
    {
        $this->eventDispatcher->notify(
            'upgrade.customField.start',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Custom fields update'))
                    ->addDescription(__FUNCTION__)
            )
        );

        try {
            $customFieldType = [];

            foreach ($this->customFieldTypeService->getAll() as $customFieldTypeData) {
                $customFieldType[$customFieldTypeData->getName()] = $customFieldTypeData->getId();
            }

            $this->transactionAware(
                function () use ($customFieldType) {
                    $queryData = new QueryData();
                    $queryData->setQuery(
                        'SELECT id, moduleId, field FROM CustomFieldDefinition WHERE field IS NOT NULL'
                    );

                    foreach ($this->database->doSelect($queryData)->getDataAsArray() as $item) {
                        /** @var CustomFieldDefDataOld $data */
                        $data = Util::unserialize(
                            CustomFieldDefDataOld::class,
                            $item->field,
                            'SP\DataModel\CustomFieldDefData'
                        );

                        $itemData = new CustomFieldDefinitionData();
                        $itemData->setId($item->id);
                        $itemData->setModuleId($this->moduleMapper((int)$item->moduleId));
                        $itemData->setName($data->getName());
                        $itemData->setHelp($data->getHelp());
                        $itemData->setRequired($data->isRequired());
                        $itemData->setShowInList($data->isShowInItemsList());
                        $itemData->setTypeId($customFieldType[$this->typeMapper((int)$data->getType())]);

                        $this->customFieldDefService->updateRaw($itemData);

                        $this->eventDispatcher->notify(
                            'upgrade.customField.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                    ->addDescription(__u('Field updated'))
                                    ->addDetail(__u('Field'), $data->getName())
                            )
                        );
                    }
                },
                $this->database
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notify(
            'upgrade.customField.end',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Custom fields update'))
                    ->addDescription(__FUNCTION__)
            )
        );
    }

    /**
     * @param  int  $moduleId
     *
     * @return int
     */
    private function moduleMapper(int $moduleId): int
    {
        switch ($moduleId) {
            case 10:
                return AclActionsInterface::ACCOUNT;
            case 61:
                return AclActionsInterface::CATEGORY;
            case 62:
                return AclActionsInterface::CLIENT;
            case 71:
                return AclActionsInterface::USER;
            case 72:
                return AclActionsInterface::GROUP;
        }

        return $moduleId;
    }

    /**
     * @param  int  $typeId
     *
     * @return string
     */
    private function typeMapper(int $typeId): string
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
            10 => 'textarea',
        ];

        return $types[$typeId] ?? $types[1];
    }

    /**
     * upgrade_300_18072901
     *
     * @throws Exception
     */
    public function upgrade_300_18072901(): void
    {
        $this->eventDispatcher->notify(
            'upgrade.customField.start',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Custom fields update'))
                    ->addDescription(__FUNCTION__)
            )
        );

        try {
            $this->transactionAware(
                function () {
                    foreach ($this->customFieldDefService->getAllBasic() as $item) {
                        $itemData = clone $item;
                        $itemData->setModuleId($this->moduleMapper((int)$item->getModuleId()));

                        $this->customFieldDefService->updateRaw($itemData);

                        $this->eventDispatcher->notify(
                            'upgrade.customField.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                    ->addDescription(__u('Field updated'))
                                    ->addDetail(__u('Field'), $item->getName())
                            )
                        );
                    }
                }
                ,
                $this->database
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notify(
            'upgrade.customField.end',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Custom fields update'))
                    ->addDescription(__FUNCTION__)
            )
        );
    }

    /**
     * upgrade_300_19042701
     *
     * @throws Exception
     */
    public function upgrade_310_19042701(): void
    {
        if (!in_array('field', $this->db->getColumnsForTable('CustomFieldDefinition'), true)) {
            return;
        }

        $this->eventDispatcher->notify(
            'upgrade.customField.start',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Custom fields update'))
                    ->addDescription(__FUNCTION__)
            )
        );

        try {
            $customFieldType = [];

            foreach ($this->customFieldTypeService->getAll() as $customFieldTypeData) {
                $customFieldType[$customFieldTypeData->getName()] = $customFieldTypeData->getId();
            }

            $this->transactionAware(
                function () use ($customFieldType) {
                    $queryData = new QueryData();
                    $queryData->setQuery('SELECT id, field FROM CustomFieldDefinition WHERE field IS NOT NULL');

                    foreach ($this->database->doSelect($queryData)->getDataAsArray() as $item) {
                        /** @var CustomFieldDefDataOld $data */
                        $data = Util::unserialize(
                            CustomFieldDefDataOld::class,
                            $item->field,
                            'SP\DataModel\CustomFieldDefData'
                        );

                        $typeId = $customFieldType[$this->typeMapper((int)$data->getType())];

                        $queryData = new QueryData();
                        $queryData->setQuery('UPDATE CustomFieldDefinition SET typeId = ? WHERE id = ? LIMIT 1');
                        $queryData->setParams([$typeId, $item->id]);

                        $this->database->doQuery($queryData);

                        $this->eventDispatcher->notify(
                            'upgrade.customField.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                    ->addDescription(__u('Field updated'))
                                    ->addDetail(__u('Field'), $data->getName())
                            )
                        );
                    }
                },
                $this->database
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notify(
            'upgrade.customField.end',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Custom fields update'))
                    ->addDescription(__FUNCTION__)
            )
        );
    }
}
