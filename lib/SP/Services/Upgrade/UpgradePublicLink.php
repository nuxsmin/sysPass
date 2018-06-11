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
use SP\DataModel\PublickLinkOldData;
use SP\DataModel\PublicLinkData;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\Database;
use SP\Storage\Database\DbWrapper;
use SP\Storage\Database\QueryData;
use SP\Util\Util;

/**
 * Class UpgradePublicLink
 *
 * @package SP\Services\Upgrade
 */
class UpgradePublicLink extends Service
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
        $this->eventDispatcher->notifyEvent('upgrade.publicLink.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualización de enlaces públicos'))
                ->addDescription(__FUNCTION__))
        );

        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, `data` FROM PublicLink');

        try {
            $publicLinkService = $this->dic->get(PublicLinkService::class);

            if (!DbWrapper::beginTransaction($this->db)) {
                throw new ServiceException(__u('No es posible iniciar una transacción'));
            }

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
                        ->addDescription(__u('Enlace actualizado'))
                        ->addDetail(__u('Enlace'), $item->id))
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

        $this->eventDispatcher->notifyEvent('upgrade.publicLink.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualización de enlaces públicos'))
                ->addDescription(__FUNCTION__))
        );
    }

    protected function initialize()
    {
        $this->db = $this->dic->get(Database::class);
    }
}