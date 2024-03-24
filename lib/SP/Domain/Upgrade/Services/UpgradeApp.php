<?php
/*
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

namespace SP\Domain\Upgrade\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Upgrade\Ports\UpgradeAppService;
use SP\Infrastructure\File\FileException;
use SP\Providers\Log\FileLogHandler;
use SP\Util\VersionUtil;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class UpgradeApp
 */
final class UpgradeApp extends Service implements UpgradeAppService
{
    private const UPGRADES = [
        '300.18010101',
        '300.18072901',
        '300.18072902',
        '310.19012201',
        '310.19042701',
    ];

    public function __construct(
        Application    $application,
        FileLogHandler $fileLogHandler,
    ) {
        parent::__construct($application);

        $this->eventDispatcher->attach($fileLogHandler);
    }

    public static function needsUpgrade(string $version): bool
    {
        return empty($version) || VersionUtil::checkVersion($version, self::UPGRADES);
    }

    /**
     * @throws UpgradeException
     * @throws FileException
     */
    public function upgrade(
        string $version,
        ConfigDataInterface $configData
    ): void {
        $this->eventDispatcher->notify(
            'upgrade.app.start',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Update Application'))
            )
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

                $this->config->save($configData, false);
            }
        }

        $this->eventDispatcher->notify(
            'upgrade.app.end',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Update Application'))
            )
        );
    }

    /**
     * Actualizaciones de la aplicación
     */
    private function applyUpgrade(string $version): bool
    {
        try {
            return true;
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }
}
