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
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Tag\Exceptions\TagException;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\UseCases\Dtos\UpdateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\UpdateTagResult;

/**
 * Updates a Tag.
 *
 * @implements UseCase<UpdateTagCommand, UpdateTagResult>
 */
final readonly class UpdateTagUseCase implements UseCase
{
    /**
     * @param TagRepository<\SP\Domain\Tag\Models\Tag> $tagRepository
     */
    public function __construct(
        private TagRepository $tagRepository,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): UpdateTagResult
    {
        if (!$command instanceof UpdateTagCommand) {
            throw new LogicException(sprintf('Expected command %s', UpdateTagCommand::class));
        }

        if ($command->name === '') {
            throw TagException::nameRequired();
        }

        $this->tagRepository->update(
            (new Tag())->mutate(['id' => $command->id, 'name' => $command->name])
        );

        $this->eventEmitter->emit(
            'edit.tag',
            $this,
            EventMessage::build('Tag updated')
                        ->addDetail('Name', $command->name)
                        ->addDetail('ID', $command->id)
        );

        return new UpdateTagResult($command->id);
    }
}
