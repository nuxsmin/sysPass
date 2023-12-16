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

namespace SP\Domain\Auth\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Ports\AuthTokenServiceInterface;
use SP\Domain\Auth\Ports\UpgradeAuthTokenServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Infrastructure\Database\DatabaseInterface;

use function SP\__u;
use function SP\processException;


/**
 * Class UpgradeAuthToken
 */
final class UpgradeAuthToken extends Service implements UpgradeAuthTokenServiceInterface
{

    public function __construct(
        Application                                  $application,
        protected readonly AuthTokenServiceInterface $authTokenService,
        private readonly DatabaseInterface           $database
    ) {
        parent::__construct($application);
    }

    /**
     * upgrade_300_18072901
     *
     * @throws Exception
     */
    public function upgrade_300_18072901(): void
    {
        $this->eventDispatcher->notify(
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
                    foreach ($this->authTokenService->getAll() as $item) {
                        $itemData = clone $item;
                        $itemData->setActionId($this->actionMapper($item->getActionId()));

                        $this->authTokenService->updateRaw($itemData);

                        $this->eventDispatcher->notify(
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

            $this->eventDispatcher->notify('exception', new Event($e));

            throw $e;
        }

        $this->eventDispatcher->notify(
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
        return match ($moduleId) {
            1 => AclActionsInterface::ACCOUNT_SEARCH,
            100 => AclActionsInterface::ACCOUNT_VIEW,
            104 => AclActionsInterface::ACCOUNT_VIEW_PASS,
            103 => AclActionsInterface::ACCOUNT_DELETE,
            101 => AclActionsInterface::ACCOUNT_CREATE,
            615 => AclActionsInterface::CATEGORY_SEARCH,
            610 => AclActionsInterface::CATEGORY_VIEW,
            611 => AclActionsInterface::CATEGORY_CREATE,
            612 => AclActionsInterface::CATEGORY_EDIT,
            613 => AclActionsInterface::CATEGORY_DELETE,
            625 => AclActionsInterface::CLIENT_SEARCH,
            620 => AclActionsInterface::CLIENT_VIEW,
            621 => AclActionsInterface::CLIENT_CREATE,
            622 => AclActionsInterface::CLIENT_EDIT,
            623 => AclActionsInterface::CLIENT_DELETE,
            685 => AclActionsInterface::TAG_SEARCH,
            681 => AclActionsInterface::TAG_VIEW,
            680 => AclActionsInterface::TAG_CREATE,
            682 => AclActionsInterface::TAG_EDIT,
            683 => AclActionsInterface::TAG_DELETE,
            1041 => AclActionsInterface::CONFIG_BACKUP_RUN,
            1061 => AclActionsInterface::CONFIG_EXPORT_RUN,
            default => $moduleId,
        };
    }
}
