<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Plugin\Ports\PluginInterface;
use SP\Domain\Plugin\Ports\PluginManagerInterface;
use SP\Domain\Plugin\Ports\PluginRegisterInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Plugin\Repositories\PluginModel;

use function SP\__u;

/**
 * Class PluginRegister
 */
final class PluginRegister extends Service implements PluginRegisterInterface
{
    public function __construct(Application $application, private readonly PluginManagerInterface $pluginService)
    {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function registerFor(PluginInterface $plugin): void
    {
        try {
            $this->pluginService->getByName($plugin->getName());

            $this->eventDispatcher->notify(
                'register.plugin',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Plugin already registered'))
                                ->addDetail(__u('Name'), $plugin->getName())
                )
            );
        } catch (NoSuchItemException) {
            $this->eventDispatcher->notify(
                'register.plugin',
                new Event(
                    $this,
                    EventMessage::factory()
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
    private function register(PluginInterface $plugin): void
    {
        $pluginData = new PluginModel();
        $pluginData->setName($plugin->getName());
        $pluginData->setEnabled(false);

        $this->pluginService->create($pluginData);

        $this->eventDispatcher->notify(
            'create.plugin',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('New Plugin'))
                            ->addDetail(__u('Name'), $plugin->getName())
            )
        );
    }
}
