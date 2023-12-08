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
use SP\Domain\Plugin\Ports\PluginLoaderInterface;
use SP\Domain\Plugin\Ports\PluginManagerInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__;

/**
 * Class PluginLoader
 */
final class PluginLoader extends Service implements PluginLoaderInterface
{
    public function __construct(Application $application, private readonly PluginManagerInterface $pluginService)
    {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function loadFor(PluginInterface $plugin): void
    {
        try {
            $model = $this->pluginService->getByName($plugin->getName());
        } catch (NoSuchItemException $e) {
            $this->eventDispatcher->notify(
                'plugin.load',
                new Event(
                    $e,
                    EventMessage::factory()
                                ->addDetail(__('Plugin not registered'), $plugin->getName())
                )
            );

            return;
        }

        if ($model->getEnabled()) {
            $this->eventDispatcher->attach($plugin);

            $this->eventDispatcher->notify(
                'plugin.load',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDetail(__('Plugin loaded'), $plugin->getName())
                )
            );
        } else {
            $this->eventDispatcher->notify(
                'plugin.load',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDetail(__('Plugin not loaded (disabled)'), $plugin->getName())
                )
            );
        }
    }
}
