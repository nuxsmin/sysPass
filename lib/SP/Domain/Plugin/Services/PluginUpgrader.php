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
use SP\Domain\Common\Providers\Version;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\Ports\PluginUpgraderInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__;
use function SP\__u;

/**
 * Class PluginUpgrader
 */
final class PluginUpgrader extends Service implements PluginUpgraderInterface
{
    public function __construct(
        Application                           $application,
        private readonly PluginManagerService $pluginManagerService
    ) {
        parent::__construct($application);
    }

    /**
     * @param Plugin $plugin
     * @param string $version
     * @throws ConstraintException
     * @throws QueryException
     */
    public function upgradeFor(Plugin $plugin, string $version): void
    {
        try {
            $pluginModel = $this->pluginManagerService->getByName($plugin->getName());
        } catch (NoSuchItemException $e) {
            $this->eventDispatcher->notify(
                'plugin.upgrade',
                new Event(
                    $e,
                    EventMessage::factory()
                                ->addDetail(__('Plugin not registered'), $plugin->getName())
                )
            );

            return;
        }

        if ($pluginModel->getVersionLevel() === null
            || Version::checkVersion($pluginModel->getVersionLevel(), $version)
        ) {
            $this->eventDispatcher->notify(
                'plugin.upgrade.process',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Upgrading plugin'))
                                ->addDetail(__u('Name'), $plugin->getName())
                )
            );

            $plugin->onUpgrade($version);

            $this->pluginManagerService->update($pluginModel->mutate(['data' => null, 'versionLevel' => $version]));

            $this->eventDispatcher->notify(
                'plugin.upgrade.process',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Plugin upgraded'))
                                ->addDetail(__u('Name'), $plugin->getName())
                )
            );
        }
    }
}
