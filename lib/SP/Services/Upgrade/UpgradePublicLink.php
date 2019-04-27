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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\PublickLinkOldData;
use SP\DataModel\PublicLinkData;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\Service;
use SP\Storage\Database\Database;
use SP\Storage\Database\QueryData;
use SP\Util\Util;

/**
 * Class UpgradePublicLink
 *
 * @package SP\Services\Upgrade
 */
final class UpgradePublicLink extends Service
{
    /**
     * @var Database
     */
    private $db;

    /**
     * upgrade_300_18010101
     */
    public function upgrade_300_18010101()
    {
        $this->eventDispatcher->notifyEvent('upgrade.publicLink.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Public links update'))
                ->addDescription(__FUNCTION__))
        );

        try {
            $this->transactionAware(function () {
                $publicLinkService = $this->dic->get(PublicLinkService::class);

                $queryData = new QueryData();
                $queryData->setQuery('SELECT id, `data` FROM PublicLink');

                foreach ($this->db->doSelect($queryData)->getDataAsArray() as $item) {
                    /** @var PublickLinkOldData $data */
                    $data = Util::unserialize(PublickLinkOldData::class, $item->data, 'SP\DataModel\PublicLinkData');

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

                    $publicLinkService->update($itemData);

                    $this->eventDispatcher->notifyEvent('upgrade.publicLink.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Link updated'))
                            ->addDetail(__u('Link'), $item->id))
                    );
                }
            });
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        $this->eventDispatcher->notifyEvent('upgrade.publicLink.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Public links update'))
                ->addDescription(__FUNCTION__))
        );
    }

    protected function initialize()
    {
        $this->db = $this->dic->get(Database::class);
    }
}