<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Config\ConfigData;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Service;
use SP\Util\Util;

/**
 * Class UpgradeAppService
 *
 * @package SP\Services\Upgrade
 */
final class UpgradeAppService extends Service implements UpgradeInterface
{
    const UPGRADES = ['300.18010101', '300.18072901', '300.18072902'];

    /**
     * @param $version
     *
     * @return bool
     */
    public static function needsUpgrade($version)
    {
        return Util::checkVersion($version, self::UPGRADES);
    }

    /**
     * @param            $version
     * @param ConfigData $configData
     *
     * @throws UpgradeException
     */
    public function upgrade($version, ConfigData $configData)
    {
        $this->eventDispatcher->notifyEvent('upgrade.app.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualizar Aplicación')))
        );

        foreach (self::UPGRADES as $appVersion) {
            if (Util::checkVersion($version, $appVersion)) {
                if ($this->applyUpgrade($appVersion) === false) {
                    throw new UpgradeException(
                        __u('Error al aplicar la actualización de la aplicación'),
                        UpgradeException::CRITICAL,
                        __u('Compruebe el registro de eventos para más detalles')
                    );
                }

                debugLog('APP Upgrade: ' . $appVersion);

                $configData->setConfigVersion($appVersion);

                $this->config->saveConfig($configData, false);
            }
        }

        $this->eventDispatcher->notifyEvent('upgrade.app.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualizar Aplicación')))
        );
    }

    /**
     * Actualizaciones de la aplicación
     *
     * @param $version
     *
     * @return bool
     */
    private function applyUpgrade($version)
    {
        try {
            switch ($version) {
                case '300.18010101':
                    $this->dic->get(UpgradeCustomFieldDefinition::class)
                        ->upgrade_300_18010101();
                    $this->dic->get(UpgradePublicLink::class)
                        ->upgrade_300_18010101();
                    return true;
                case '300.18072901':
                    $this->dic->get(UpgradeCustomFieldDefinition::class)
                        ->upgrade_300_18072901();
                    $this->dic->get(UpgradeAuthToken::class)
                        ->upgrade_300_18072901();
                    return true;
                case '300.18072902':
                    $this->dic->get(UpgradeCustomFieldData::class)
                        ->upgrade_300_18072902();
                    return true;
            }
        } catch (\Exception $e) {
            processException($e);
        }

        return false;
    }
}