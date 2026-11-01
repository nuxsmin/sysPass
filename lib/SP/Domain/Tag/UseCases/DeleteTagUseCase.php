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

namespace SP\Domain\Tag\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Tag\Exceptions\TagException;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagCommand;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagResult;

/**
 * Deletes a single Tag and its custom field data.
 *
 * @implements UseCase<DeleteTagCommand, DeleteTagResult>
 */
final readonly class DeleteTagUseCase implements UseCase
{
    /**
     * @param TagRepository<\SP\Domain\Tag\Models\Tag> $tagRepository
     * @param CustomFieldDataService<CustomFieldDataModel> $customFieldDataService
     */
    public function __construct(
        private TagRepository $tagRepository,
        private CustomFieldDataService $customFieldDataService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): DeleteTagResult
    {
        if (!$command instanceof DeleteTagCommand) {
            throw new LogicException(sprintf('Expected command %s', DeleteTagCommand::class));
        }

        if ($this->tagRepository->delete($command->id)->getAffectedNumRows() === 0) {
            throw TagException::notFound();
        }

        $this->customFieldDataService->delete([$command->id], AclActionsInterface::TAG);

        $this->eventEmitter->emit(
            'delete.tag',
            $this,
            EventMessage::build('Tag removed')->addDetail('ID', $command->id)
        );

        return new DeleteTagResult($command->id);
    }
}
