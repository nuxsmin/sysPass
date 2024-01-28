<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\Ports\CustomFieldDataRepository;
use SP\Domain\CustomField\Ports\UpgradeCustomFieldDataService;

use function SP\__u;
use function SP\processException;

/**
 * Class UpgradeCustomFieldData
 */
final class UpgradeCustomFieldData extends Service implements UpgradeCustomFieldDataService
{

    public function __construct(
        Application                                $application,
        private readonly CustomFieldDataRepository $customFieldDataRepository
    ) {
        parent::__construct($application);
    }

    /**
     * @throws Exception
     */
    public function upgradeV300B18072902(): void
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
            $this->customFieldDataRepository->transactionAware(function () {
                foreach ($this->customFieldDataRepository->getAll()->getDataAsArray() as $customFieldData) {
                    $moduleId = UpgradeCustomFieldData::moduleMapper($customFieldData->getModuleId());

                    if ($moduleId !== $customFieldData->getModuleId()) {
                        $this->customFieldDataRepository->deleteBatch(
                            [$customFieldData->getItemId()],
                            $customFieldData->getModuleId()
                        );

                        $this->customFieldDataRepository->create($customFieldData->mutate(['moduleId' => $moduleId]));

                        $this->eventDispatcher->notify(
                            'upgrade.customField.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                            ->addDescription(__u('Field updated'))
                                            ->addDetail('itemId', $customFieldData->getItemId())
                                            ->addDetail('moduleId', $moduleId)
                                            ->addDetail('definitionId', $customFieldData->getDefinitionId())
                            )
                        );
                    } else {
                        $this->eventDispatcher->notify(
                            'upgrade.customField.process',
                            new Event(
                                $this,
                                EventMessage::factory()
                                            ->addDescription(__u('Field not updated'))
                                            ->addDetail('itemId', $customFieldData->getItemId())
                                            ->addDetail('moduleId', $customFieldData->getModuleId())
                                            ->addDetail('definitionId', $customFieldData->getDefinitionId())
                            )
                        );
                    }
                }
            }, $this);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            throw ServiceException::from($e);
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

    private static function moduleMapper(int $moduleId): int
    {
        return match ($moduleId) {
            10 => AclActionsInterface::ACCOUNT,
            61 => AclActionsInterface::CATEGORY,
            62 => AclActionsInterface::CLIENT,
            71 => AclActionsInterface::USER,
            72 => AclActionsInterface::GROUP,
            default => $moduleId,
        };
    }
}
