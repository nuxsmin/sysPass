<?php
declare(strict_types=1);
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

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Ports\UpgradeService;
use SP\Infrastructure\File\FileException;

use function SP\__u;
use function SP\logger;

/**
 * Class UpgradeBase
 */
abstract class UpgradeBase extends Service implements UpgradeService
{
    protected ?ConfigDataInterface $configData = null;

    public function __construct(Application $application, FileHandlerProvider $fileHandlerProvider)
    {
        parent::__construct($application);

        $this->eventDispatcher->attach($fileHandlerProvider);
    }

    /**
     * @inheritDoc
     */
    final public static function needsUpgrade(string $version): bool
    {
        return !empty($version) && Version::checkVersion($version, static::getUpgrades());
    }

    abstract protected static function getUpgrades(): array;

    /**
     * @inheritDoc
     * @throws UpgradeException
     * @throws FileException
     */
    final public function upgrade(string $version, ConfigDataInterface $configData): void
    {
        $this->configData = $configData;

        $class = get_class();

        $this->eventDispatcher->notify(
            sprintf('upgrade.%s.start', $class),
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Update'))->addDetail('type', $class)
            )
        );

        $upgradeVersions = array_filter(
            static::getUpgrades(),
            static fn(string $appVersion) => Version::checkVersion($version, $appVersion)
        );

        foreach ($upgradeVersions as $upgradeVersion) {
            if ($this->applyUpgrade($upgradeVersion) === false) {
                throw UpgradeException::critical(
                    __u('Error while applying the update'),
                    __u('Please, check the event log for more details')
                );
            }

            logger('Upgrade: ' . $upgradeVersion);

            $this->commitVersion($upgradeVersion);

            $this->config->save($configData, false);
        }

        $this->eventDispatcher->notify(
            sprintf('upgrade.%s.end', $class),
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Update'))->addDetail('type', $class)
            )
        );
    }

    abstract protected function applyUpgrade(string $version): bool;

    abstract protected function commitVersion(string $version): void;
}
