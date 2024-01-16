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

namespace SP\Domain\Account\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\PublickLinkOldData;
use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Account\Ports\PublicLinkRepository;
use SP\Domain\Account\Ports\UpgradePublicLinkService;
use SP\Domain\Common\Services\Service;
use SP\Util\Util;

use function SP\__u;
use function SP\processException;

/**
 * Class UpgradePublicLink
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradePublicLink extends Service implements UpgradePublicLinkService
{
    public function __construct(
        Application                           $application,
        private readonly PublicLinkRepository $publicLinkRepository
    ) {
        parent::__construct($application);
    }

    public function upgradeV300B18010101(): void
    {
        $this->eventDispatcher->notify(
            'upgrade.publicLink.start',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('Public links update'))
                            ->addDescription(__FUNCTION__)
            )
        );

        try {
            $this->publicLinkRepository->transactionAware(
                function () {
                    $items = $this->publicLinkRepository->getAny(['id', 'data'], 'PublicLink')->getDataAsArray();

                    foreach ($items as $item) {
                        $data = Util::unserialize(
                            PublickLinkOldData::class,
                            $item['data'],
                            PublicLink::class
                        );

                        $itemData = new PublicLink([
                                                       'id' => $item['id'],
                                                       'itemId' => $data->getItemId(),
                                                       'hash' => $data->getLinkHash(),
                                                       'userId' => $data->getUserId(),
                                                       'typeId' => $data->getTypeId(),
                                                       'notify' => $data->isNotify(),
                                                       'dateAdd' => $data->getDateAdd(),
                                                       'dateExpire' => $data->getDateExpire(),
                                                       'countViews' => $data->getCountViews(),
                                                       'maxCountViews' => $data->getMaxCountViews(),
                                                       'useInfo' => serialize($data->getUseInfo()),
                                                       'data' => $data->getData(),
                                                   ]);

                        $this->publicLinkRepository->update($itemData);

                        $this->eventDispatcher->notify(
                            'upgrade.publicLink.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                            ->addDescription(__u('Link updated'))
                                            ->addDetail(__u('Link'), $item['id'])
                            )
                        );
                    }
                },
                $this
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));
        }

        $this->eventDispatcher->notify(
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
