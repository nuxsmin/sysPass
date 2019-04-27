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
use SP\Services\CustomField\CustomFieldService;
use SP\Services\Service;
use SP\Storage\Database\Database;
use SP\Storage\Database\QueryData;

/**
 * Class UpgradeCustomFieldData
 *
 * @package SP\Services\Upgrade
 */
final class UpgradeCustomFieldData extends Service
{
    /**
     * @var Database
     */
    private $db;

    /**
     * upgrade_300_18072902
     *
     * @throws Exception
     */
    public function upgrade_300_18072902()
    {
        $this->eventDispatcher->notifyEvent('upgrade.customField.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Custom fields update'))
                ->addDescription(__FUNCTION__))
        );

        try {
            $this->transactionAware(function () {
                $customFieldService = $this->dic->get(CustomFieldService::class);

                foreach ($customFieldService->getAll() as $item) {
                    $queryData = new QueryData();
                    $queryData->setQuery('UPDATE CustomFieldData SET moduleId = ? WHERE id = ? LIMIT 1');
                    $queryData->setParams([$this->moduleMapper($item->getModuleId()), $item->getId()]);

                    $this->db->doQuery($queryData);

                    $this->eventDispatcher->notifyEvent('upgrade.customField.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Field updated')))
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

    protected function initialize()
    {
        $this->db = $this->dic->get(Database::class);
    }
}