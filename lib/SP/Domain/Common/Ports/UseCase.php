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

namespace SP\Domain\Common\Ports;

use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Dtos\Result;

/**
 * Base contract for use cases.
 *
 * A use case has a single public entry point that handles one user intent. It
 * receives a Command, orchestrates domain objects and ports, and returns a
 * Result. Implementations must not use HTTP, session, translation, logging or
 * presentation concerns; failures are domain exceptions.
 *
 * @template TCommand of Command
 * @template TResult of Result
 */
interface UseCase
{
    /**
     * @param TCommand $command
     * @return TResult
     */
    public function execute(Command $command): Result;
}
