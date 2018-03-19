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

namespace SP\Services\Upgrade;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\CustomFieldDefDataOld;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class UpgradeCustomField
 *
 * @package SP\Services\Upgrade
 */
class UpgradeCustomFieldDefinition extends Service
{
    /**
     * @var Database
     */
    private $db;

    /**
     * upgrade_300_18010101
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function upgrade_300_18010101()
    {
        $this->eventDispatcher->notifyEvent('upgrade.customField.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualización de campos personalizados'))
                ->addDescription(__FUNCTION__))
        );

        $customFieldDefService = $this->dic->get(CustomFieldDefService::class);

        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, moduleId, field FROM CustomFieldDefinition WHERE field IS NOT NULL');

        try {
            if (!DbWrapper::beginTransaction($this->db)) {
                throw new ServiceException(__u('No es posible iniciar una transacción'));
            }

            foreach (DbWrapper::getResultsArray($queryData, $this->db) as $item) {
                /** @var CustomFieldDefDataOld $data */
                $data = Util::unserialize(CustomFieldDefDataOld::class, $item->field, 'SP\DataModel\CustomFieldDefData');

                $itemData = new CustomFieldDefinitionData();
                $itemData->setId($item->id);
                $itemData->setModuleId($item->moduleId);
                $itemData->setName($data->getName());
                $itemData->setHelp($data->getHelp());
                $itemData->setRequired($data->isRequired());
                $itemData->setShowInList($data->isShowInItemsList());
                $itemData->setTypeId($data->getType());

                $customFieldDefService->update($itemData);

                $this->eventDispatcher->notifyEvent('upgrade.customField.process',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Campo actualizado'))
                        ->addDetail(__u('Campo'), $data->getName()))
                );
            }

            if (!DbWrapper::endTransaction($this->db)) {
                throw new ServiceException(__u('No es posible finalizar una transacción'));
            }
        } catch (\Exception $e) {
            DbWrapper::rollbackTransaction($this->db);

            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        $this->eventDispatcher->notifyEvent('upgrade.customField.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualización de campos personalizados'))
                ->addDescription(__FUNCTION__))
        );
    }

    protected function initialize()
    {
        $this->db = $this->dic->get(Database::class);
    }
}