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


namespace SP\Domain\CustomField\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\CustomField\Models\CustomFieldDefinition;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionService;
use SP\Domain\CustomField\UseCases\Dtos\UpdateCustomFieldCommand;
use SP\Domain\CustomField\UseCases\Dtos\UpdateCustomFieldResult;

/**
 * Update CustomField use case.
 *
 * @implements UseCase<UpdateCustomFieldCommand, UpdateCustomFieldResult>
 */
final readonly class UpdateCustomFieldUseCase implements UseCase
{
    /**
     * @param CustomFieldDefinitionService $customFieldDefinitionService
     */
    public function __construct(
        private CustomFieldDefinitionService $customFieldDefinitionService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): UpdateCustomFieldResult
    {
        if (!$command instanceof UpdateCustomFieldCommand) {
            throw new LogicException(sprintf('Expected command %s', UpdateCustomFieldCommand::class));
        }

        $current = $this->customFieldDefinitionService->getById((int)$command->model->getId());

        if ($current->getModuleId() !== $command->model->getModuleId()) {
            $id = $this->customFieldDefinitionService->changeModule($command->model);
        } else {
            $this->customFieldDefinitionService->update($command->model);
            $id = (int)$command->model->getId();
        }

        $this->eventEmitter->emit(
            'edit.customField',
            $this,
            EventMessage::build('Field updated')->addDetail('Field', $command->model->getName())
        );

        return new UpdateCustomFieldResult($id);
    }
}
