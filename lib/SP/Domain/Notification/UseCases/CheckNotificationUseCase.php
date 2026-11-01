<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Notification\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Domain\Notification\UseCases\Dtos\CheckNotificationCommand;
use SP\Domain\Notification\UseCases\Dtos\CheckNotificationResult;

/**
 * Mark a notification as read use case.
 *
 * @implements UseCase<CheckNotificationCommand, CheckNotificationResult>
 */
final readonly class CheckNotificationUseCase implements UseCase
{
    /**
     * @param NotificationService<\SP\Domain\Notification\Models\Notification> $notificationService
     */
    public function __construct(
        private NotificationService $notificationService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): CheckNotificationResult
    {
        if (!$command instanceof CheckNotificationCommand) {
            throw new LogicException(sprintf('Expected command %s', CheckNotificationCommand::class));
        }

        $this->notificationService->setCheckedById($command->id);

        $this->eventEmitter->emit(
            'check.notification',
            $this,
            EventMessage::build()
                ->addDescription('Notification read')
                ->addDetail('Notification', $command->id)
        );

        return new CheckNotificationResult($command->id);
    }
}
