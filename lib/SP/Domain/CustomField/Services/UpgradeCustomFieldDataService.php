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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Domain\CustomField\Ports\UpgradeCustomFieldDataServiceInterface;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;

/**
 * Class UpgradeCustomFieldData
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradeCustomFieldDataService extends Service
    implements UpgradeCustomFieldDataServiceInterface
{
    private CustomFieldServiceInterface $customFieldService;
    private DatabaseInterface           $database;

    public function __construct(
        Application $application,
        CustomFieldServiceInterface $customFieldService,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->database = $database;
        $this->customFieldService = $customFieldService;
    }

    /**
     * upgrade_300_18072902
     *
     * @throws Exception
     */
    public function upgrade_300_18072902(): void
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
            $this->transactionAware(function () {
                foreach ($this->customFieldService->getAll() as $item) {
                    $queryData = new QueryData();
                    $queryData->setQuery('UPDATE CustomFieldData SET moduleId = ? WHERE id = ? LIMIT 1');
                    $queryData->setParams([$this->moduleMapper($item->getModuleId()), $item->getId()]);

                    $this->database->doQuery($queryData);

                    $this->eventDispatcher->notify(
                        'upgrade.customField.process',
                        new Event(
                            $this,
                            EventMessage::factory()->addDescription(__u('Field updated'))
                        )
                    );
                }
            }, $this->database);
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

    private function moduleMapper(int $moduleId): int
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
}
