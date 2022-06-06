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

namespace SP\Domain\Auth\Services;

use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\AuthTokenServiceInterface;
use SP\Domain\Auth\UpgradeAuthTokenServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Infrastructure\Database\DatabaseInterface;


/**
 * Class UpgradeAuthToken
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradeAuthTokenService extends Service
    implements UpgradeAuthTokenServiceInterface
{
    protected AuthTokenServiceInterface $authTokenService;
    private DatabaseInterface           $database;

    public function __construct(
        Application $application,
        AuthTokenServiceInterface $authTokenService,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->authTokenService = $authTokenService;
        $this->database = $database;
    }


    /**
     * upgrade_300_18072901
     *
     * @throws Exception
     */
    public function upgrade_300_18072901(): void
    {
        $this->eventDispatcher->notifyEvent(
            'upgrade.authToken.start',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('API authorizations update'))
                    ->addDescription(__FUNCTION__)
            )
        );

        try {
            $this->transactionAware(
                function () {
                    foreach ($this->authTokenService->getAllBasic() as $item) {

                        $itemData = clone $item;
                        $itemData->setActionId($this->actionMapper($item->getActionId()));

                        $this->authTokenService->updateRaw($itemData);

                        $this->eventDispatcher->notifyEvent(
                            'upgrade.authToken.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                    ->addDescription(__u('Authorization updated'))
                                    ->addDetail(__u('Authorization'), $item->getId())
                            )
                        );
                    }
                },
                $this->database
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notifyEvent(
            'upgrade.authToken.end',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('API authorizations update'))
                    ->addDescription(__FUNCTION__)
            )
        );
    }

    private function actionMapper(int $moduleId): int
    {
        switch ($moduleId) {
            case 1:
                return ActionsInterface::ACCOUNT_SEARCH;
            case 100:
                return ActionsInterface::ACCOUNT_VIEW;
            case 104:
                return ActionsInterface::ACCOUNT_VIEW_PASS;
            case 103:
                return ActionsInterface::ACCOUNT_DELETE;
            case 101:
                return ActionsInterface::ACCOUNT_CREATE;
            case 615:
                return ActionsInterface::CATEGORY_SEARCH;
            case 610:
                return ActionsInterface::CATEGORY_VIEW;
            case 611:
                return ActionsInterface::CATEGORY_CREATE;
            case 612:
                return ActionsInterface::CATEGORY_EDIT;
            case 613:
                return ActionsInterface::CATEGORY_DELETE;
            case 625:
                return ActionsInterface::CLIENT_SEARCH;
            case 620:
                return ActionsInterface::CLIENT_VIEW;
            case 621:
                return ActionsInterface::CLIENT_CREATE;
            case 622:
                return ActionsInterface::CLIENT_EDIT;
            case 623:
                return ActionsInterface::CLIENT_DELETE;
            case 685:
                return ActionsInterface::TAG_SEARCH;
            case 681:
                return ActionsInterface::TAG_VIEW;
            case 680:
                return ActionsInterface::TAG_CREATE;
            case 682:
                return ActionsInterface::TAG_EDIT;
            case 683:
                return ActionsInterface::TAG_DELETE;
            case 1041:
                return ActionsInterface::CONFIG_BACKUP_RUN;
            case 1061:
                return ActionsInterface::CONFIG_EXPORT_RUN;
        }

        return $moduleId;
    }
}