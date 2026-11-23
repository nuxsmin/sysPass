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
use SP\Domain\Tag\UseCases\Dtos\CreateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\CreateTagResult;

/**
 * Creates a Tag.
 *
 * @implements UseCase<CreateTagCommand, CreateTagResult>
 */
final readonly class CreateTagUseCase implements UseCase
{
    /**
     * @param TagRepository<\SP\Domain\Tag\Models\Tag> $tagRepository
     */
    public function __construct(
        private TagRepository $tagRepository,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): CreateTagResult
    {
        if (!$command instanceof CreateTagCommand) {
            throw new LogicException(sprintf('Expected command %s', CreateTagCommand::class));
        }

        if ($command->name === '') {
            throw TagException::nameRequired();
        }

        $id = $this->tagRepository
            ->create((new Tag())->mutate(['name' => $command->name]))
            ->getLastId();

        $this->eventEmitter->emit(
            'create.tag',
            $this,
            EventMessage::build('Tag added')
                        ->addDetail('Name', $command->name)
                        ->addDetail('ID', $id)
        );

        return new CreateTagResult($id);
    }
}
