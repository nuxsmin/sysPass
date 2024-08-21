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
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\Ports\PluginRegisterService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__u;

/**
 * Class PluginRegister
 */
final class PluginRegister extends Service implements PluginRegisterService
{
    public function __construct(Application $application, private readonly PluginManagerService $pluginManagerService)
    {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function registerFor(Plugin $plugin): void
    {
        try {
            $this->pluginManagerService->getByName($plugin->getName());

            $this->eventDispatcher->notify(
                'register.plugin',
                new Event(
                    $this,
                    EventMessage::build()
                                ->addDescription(__u('Plugin already registered'))
                                ->addDetail(__u('Name'), $plugin->getName())
                )
            );
        } catch (NoSuchItemException) {
            $this->eventDispatcher->notify(
                'register.plugin',
                new Event(
                    $this,
                    EventMessage::build()
                                ->addDescription(__u('Plugin not registered yet'))
                                ->addDetail(__u('Name'), $plugin->getName())
                )
            );

            $this->register($plugin);
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function register(Plugin $plugin): void
    {
        $this->pluginManagerService->create(new PluginModel(['name' => $plugin->getName(), 'enabled' => false]));

        $this->eventDispatcher->notify(
            'create.plugin',
            new Event(
                $this,
                EventMessage::build()
                            ->addDescription(__u('New Plugin'))
                            ->addDetail(__u('Name'), $plugin->getName())
            )
        );
    }
}
