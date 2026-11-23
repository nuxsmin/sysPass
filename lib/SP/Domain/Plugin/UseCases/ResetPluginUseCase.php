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

namespace SP\Domain\Plugin\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Plugin\Ports\PluginDataService;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\UseCases\Dtos\ResetPluginCommand;
use SP\Domain\Plugin\UseCases\Dtos\ResetPluginResult;

/**
 * Reset a plugin's data use case.
 *
 * @implements UseCase<ResetPluginCommand, ResetPluginResult>
 */
final readonly class ResetPluginUseCase implements UseCase
{
    /**
     * @param PluginDataService<\SP\Domain\Plugin\Models\PluginData> $pluginDataService
     */
    public function __construct(
        private PluginManagerService $pluginManagerService,
        private PluginDataService $pluginDataService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): ResetPluginResult
    {
        if (!$command instanceof ResetPluginCommand) {
            throw new LogicException(sprintf('Expected command %s', ResetPluginCommand::class));
        }

        $pluginName = $this->pluginManagerService->getById($command->id)->getName();

        $this->pluginDataService->delete($pluginName);

        $this->eventEmitter->emit(
            'edit.plugin.reset',
            $this,
            EventMessage::build()->addDescription('Plugin reset')
        );

        return new ResetPluginResult($command->id);
    }
}
