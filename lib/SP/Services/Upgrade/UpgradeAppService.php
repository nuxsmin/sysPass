<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Upgrade;

use Exception;
use SP\Config\ConfigDataInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Providers\Log\FileLogHandler;
use SP\Services\Service;
use SP\Storage\File\FileException;
use SP\Util\VersionUtil;

/**
 * Class UpgradeAppService
 *
 * @package SP\Services\Upgrade
 */
final class UpgradeAppService extends Service implements UpgradeInterface
{
    private const UPGRADES = [
        '300.18010101',
        '300.18072901',
        '300.18072902',
        '310.19012201',
        '310.19042701'
    ];

    public static function needsUpgrade(string $version): bool
    {
        return empty($version)
            || VersionUtil::checkVersion($version, self::UPGRADES);
    }

    /**
     * @throws UpgradeException
     * @throws FileException
     */
    public function upgrade(
        string              $version,
        ConfigDataInterface $configData
    ): void
    {
        $this->eventDispatcher->notifyEvent(
            'upgrade.app.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Update Application')))
        );

        foreach (self::UPGRADES as $appVersion) {
            if (VersionUtil::checkVersion($version, $appVersion)) {
                if ($this->applyUpgrade($appVersion) === false) {
                    throw new UpgradeException(
                        __u('Error while applying the application update'),
                        SPException::CRITICAL,
                        __u('Please, check the event log for more details')
                    );
                }

                logger('APP Upgrade: ' . $appVersion);

                $configData->setAppVersion($appVersion);

                $this->config->saveConfig($configData, false);
            }
        }

        $this->eventDispatcher->notifyEvent(
            'upgrade.app.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Update Application')))
        );
    }

    /**
     * Actualizaciones de la aplicación
     */
    private function applyUpgrade(string $version): bool
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
                case '310.19012201':
                    $this->dic->get(UpgradePlugin::class)
                        ->upgrade_310_19012201();
                    return true;
                case '310.19042701':
                    $this->dic->get(UpgradeCustomFieldDefinition::class)
                        ->upgrade_310_19042701();
                    return true;
            }
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }

    protected function initialize(): void
    {
        $this->eventDispatcher->attach($this->dic->get(FileLogHandler::class));
    }
}