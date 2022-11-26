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

namespace SP\Domain\Account\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\PublickLinkOldData;
use SP\DataModel\PublicLinkData;
use SP\Domain\Account\Ports\PublicLinkServiceInterface;
use SP\Domain\Account\Ports\UpgradePublicLinkServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Util\Util;

/**
 * Class UpgradePublicLink
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradePublicLinkService extends Service implements UpgradePublicLinkServiceInterface
{
    protected PublicLinkServiceInterface $publicLinkService;
    protected DatabaseInterface          $database;

    public function __construct(
        Application $application,
        PublicLinkServiceInterface $publicLinkService,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->publicLinkService = $publicLinkService;
        $this->database = $database;
    }

    /**
     * upgrade_300_18010101
     */
    public function upgrade_300_18010101(): void
    {
        $this->eventDispatcher->notifyEvent(
            'upgrade.publicLink.start',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Public links update'))
                    ->addDescription(__FUNCTION__)
            )
        );

        try {
            $this->transactionAware(
                function () {
                    $queryData = new QueryData();
                    $queryData->setQuery('SELECT id, `data` FROM PublicLink');

                    foreach ($this->database->doSelect($queryData)->getDataAsArray() as $item) {
                        /** @var PublickLinkOldData $data */
                        $data = Util::unserialize(
                            PublickLinkOldData::class,
                            $item->data,
                            PublicLinkData::class
                        );

                        $itemData = new PublicLinkData();
                        $itemData->setId($item->id);
                        $itemData->setItemId($data->getItemId());
                        $itemData->setHash($data->getLinkHash());
                        $itemData->setUserId($data->getUserId());
                        $itemData->setTypeId($data->getTypeId());
                        $itemData->setNotify($data->isNotify());
                        $itemData->setDateAdd($data->getDateAdd());
                        $itemData->setDateExpire($data->getDateExpire());
                        $itemData->setCountViews($data->getCountViews());
                        $itemData->setMaxCountViews($data->getCountViews());
                        $itemData->setUseInfo($data->getUseInfo());
                        $itemData->setData($data->getData());

                        $this->publicLinkService->update($itemData);

                        $this->eventDispatcher->notifyEvent(
                            'upgrade.publicLink.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                    ->addDescription(__u('Link updated'))
                                    ->addDetail(__u('Link'), $item->id)
                            )
                        );
                    }
                },
                $this->database
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        $this->eventDispatcher->notifyEvent(
            'upgrade.publicLink.end',
            new Event(
                $this,
                EventMessage::factory()
                    ->addDescription(__u('Public links update'))
                    ->addDescription(__FUNCTION__)
            )
        );
    }
}
