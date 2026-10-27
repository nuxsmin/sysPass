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
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\CustomField\Models\CustomFieldDefinition;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionService;
use SP\Domain\CustomField\UseCases\Dtos\GetCustomFieldCommand;
use SP\Domain\CustomField\UseCases\Dtos\GetCustomFieldResult;

/**
 * Get CustomField use case.
 *
 * @implements UseCase<GetCustomFieldCommand, GetCustomFieldResult>
 */
final readonly class GetCustomFieldUseCase implements UseCase
{
    /**
     * @param CustomFieldDefinitionService $customFieldDefinitionService
     */
    public function __construct(
        private CustomFieldDefinitionService $customFieldDefinitionService
    ) {
    }

    public function execute(Command $command): GetCustomFieldResult
    {
        if (!$command instanceof GetCustomFieldCommand) {
            throw new LogicException(sprintf('Expected command %s', GetCustomFieldCommand::class));
        }

        return new GetCustomFieldResult($this->customFieldDefinitionService->getById($command->id));
    }
}
