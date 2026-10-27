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
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Tag\Exceptions\TagException;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\UseCases\Dtos\GetTagCommand;
use SP\Domain\Tag\UseCases\Dtos\GetTagResult;

/**
 * Reads a single Tag.
 *
 * @implements UseCase<GetTagCommand, GetTagResult>
 */
final readonly class GetTagUseCase implements UseCase
{
    /**
     * @param TagRepository<\SP\Domain\Tag\Models\Tag> $tagRepository
     */
    public function __construct(private TagRepository $tagRepository)
    {
    }

    public function execute(Command $command): GetTagResult
    {
        if (!$command instanceof GetTagCommand) {
            throw new LogicException(sprintf('Expected command %s', GetTagCommand::class));
        }

        $result = $this->tagRepository->getById($command->id);

        if ($result->getNumRows() === 0) {
            throw TagException::notFound();
        }

        return new GetTagResult($result->getData(Tag::class));
    }
}
