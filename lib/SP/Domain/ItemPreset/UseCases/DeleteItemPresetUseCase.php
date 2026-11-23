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


namespace SP\Domain\ItemPreset\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\ItemPreset\Models\ItemPreset;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\ItemPreset\UseCases\Dtos\DeleteItemPresetCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\DeleteItemPresetResult;

/**
 * Delete ItemPreset use case.
 *
 * @implements UseCase<DeleteItemPresetCommand, DeleteItemPresetResult>
 */
final readonly class DeleteItemPresetUseCase implements UseCase
{
    /**
     * @param ItemPresetService<ItemPreset> $itemPresetService
     */
    public function __construct(
        private ItemPresetService $itemPresetService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): DeleteItemPresetResult
    {
        if (!$command instanceof DeleteItemPresetCommand) {
            throw new LogicException(sprintf('Expected command %s', DeleteItemPresetCommand::class));
        }

        $this->itemPresetService->delete($command->id);

        $this->eventEmitter->emit(
            'delete.itemPreset',
            $this,
            EventMessage::build('Value deleted')->addDetail('ID', $command->id)
        );

        return new DeleteItemPresetResult($command->id);
    }
}
