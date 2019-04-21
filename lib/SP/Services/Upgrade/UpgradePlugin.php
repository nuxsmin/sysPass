<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\Upgrade;

use Exception;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Plugin\PluginManager;
use SP\Services\Service;

/**
 * Class UpgradePlugin
 *
 * @package SP\Services\Upgrade
 */
final class UpgradePlugin extends Service
{
    /**
     * upgrade_300_18010101
     *
     * @throws Exception
     */
    public function upgrade_310_19012201()
    {
        $this->eventDispatcher->notifyEvent('upgrade.plugin.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Plugins upgrade'))
                ->addDescription(__FUNCTION__))
        );

        $this->dic->get(PluginManager::class)
            ->upgradePlugins('310.19012201');

        $this->eventDispatcher->notifyEvent('upgrade.plugin.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Plugins upgrade'))
                ->addDescription(__FUNCTION__))
        );
    }
}