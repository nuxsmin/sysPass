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
use SP\Domain\ItemPreset\UseCases\Dtos\CreateItemPresetCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\CreateItemPresetResult;

/**
 * Create ItemPreset use case.
 *
 * @implements UseCase<CreateItemPresetCommand, CreateItemPresetResult>
 */
final readonly class CreateItemPresetUseCase implements UseCase
{
    /**
     * @param ItemPresetService<ItemPreset> $itemPresetService
     */
    public function __construct(
        private ItemPresetService $itemPresetService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): CreateItemPresetResult
    {
        if (!$command instanceof CreateItemPresetCommand) {
            throw new LogicException(sprintf('Expected command %s', CreateItemPresetCommand::class));
        }

        $id = $this->itemPresetService->create($command->model);

        $this->eventEmitter->emit(
            'create.itemPreset',
            $this,
            EventMessage::build('Value created')->addDetail('Type', $command->model->getType())->addDetail('ID', $id)
        );

        return new CreateItemPresetResult($id);
    }
}
