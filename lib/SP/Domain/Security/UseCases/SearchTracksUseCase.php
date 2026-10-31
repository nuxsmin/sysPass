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
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Security\Models\Track;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\Security\UseCases\Dtos\SearchTracksCommand;
use SP\Domain\Security\UseCases\Dtos\SearchTracksResult;

/**
 * Search tracks use case.
 *
 * @implements UseCase<SearchTracksCommand, SearchTracksResult>
 */
final readonly class SearchTracksUseCase implements UseCase
{
    public function __construct(private TrackService $trackService)
    {
    }

    public function execute(Command $command): SearchTracksResult
    {
        if (!$command instanceof SearchTracksCommand) {
            throw new LogicException(sprintf('Expected command %s', SearchTracksCommand::class));
        }

        $result = $this->trackService->search($command->search);

        /** @var Track[] $tracks */
        $tracks = $result->getDataAsArray(Track::class);

        return new SearchTracksResult(
            $tracks,
            $result->getTotalNumRows()
        );
    }
}
