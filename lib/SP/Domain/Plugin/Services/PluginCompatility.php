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

namespace SP\Domain\Plugin\Services;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Install\Services\Installer;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginCompatilityService;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__;

/**
 * Class PluginCompatility
 */
final class PluginCompatility extends Service implements PluginCompatilityService
{
    public function __construct(
        Application $application,
        private readonly PluginManagerService $pluginService
    ) {
        parent::__construct($application);
    }

    /**
     * @param Plugin $plugin
     *
     * @return bool
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function checkFor(Plugin $plugin): bool
    {
        $pluginVersion = implode('.', $plugin->getCompatibleVersion());
        $appVersion = implode('.', array_slice(Installer::VERSION, 0, 2));

        if (version_compare($pluginVersion, $appVersion, '<')) {
            $this->eventDispatcher->notify(
                'plugin.check.version',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(
                                    sprintf(
                                        __('Plugin version not compatible (%s)'),
                                        implode('.', $plugin->getVersion())
                                    )
                                )
                )
            );

            $this->pluginService->toggleEnabledByName($plugin->getName(), false);

            $this->eventDispatcher->notify(
                'plugin.edit.disable',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDetail(__('Plugin disabled'), $plugin->getName())
                )
            );

            return false;
        }

        $this->eventDispatcher->notify(
            'plugin.check.version',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(
                                sprintf(
                                    __('Plugin version compatible (%s)'),
                                    implode('.', $plugin->getVersion())
                                )
                            )
            )
        );

        return true;
    }
}
