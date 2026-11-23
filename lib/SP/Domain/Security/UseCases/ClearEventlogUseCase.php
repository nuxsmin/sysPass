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

namespace SP\Domain\Security\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Security\Ports\EventlogService;
use SP\Domain\Security\UseCases\Dtos\ClearEventlogCommand;
use SP\Domain\Security\UseCases\Dtos\ClearEventlogResult;

/**
 * Clear the event log use case.
 *
 * @implements UseCase<ClearEventlogCommand, ClearEventlogResult>
 */
final readonly class ClearEventlogUseCase implements UseCase
{
    public function __construct(
        private EventlogService $eventlogService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): ClearEventlogResult
    {
        if (!$command instanceof ClearEventlogCommand) {
            throw new LogicException(sprintf('Expected command %s', ClearEventlogCommand::class));
        }

        $cleared = $this->eventlogService->clear();

        $this->eventEmitter->emit(
            'clear.eventlog',
            $this,
            EventMessage::build()->addDescription('Event log cleared')
        );

        return new ClearEventlogResult($cleared);
    }
}
