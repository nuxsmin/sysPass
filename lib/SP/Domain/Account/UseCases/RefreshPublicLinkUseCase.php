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

namespace SP\Domain\Account\UseCases;

use LogicException;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\UseCases\Dtos\RefreshPublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\RefreshPublicLinkResult;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;

/**
 * Refresh a public link use case.
 *
 * @implements UseCase<RefreshPublicLinkCommand, RefreshPublicLinkResult>
 */
final readonly class RefreshPublicLinkUseCase implements UseCase
{
    public function __construct(
        private PublicLinkService $publicLinkService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): RefreshPublicLinkResult
    {
        if (!$command instanceof RefreshPublicLinkCommand) {
            throw new LogicException(sprintf('Expected command %s', RefreshPublicLinkCommand::class));
        }

        $this->publicLinkService->refresh($command->id);

        $this->eventEmitter->emit('edit.publicLink.refresh', $this);

        return new RefreshPublicLinkResult($command->id);
    }
}
