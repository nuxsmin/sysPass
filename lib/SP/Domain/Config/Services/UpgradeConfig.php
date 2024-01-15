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

namespace SP\Domain\Config\Services;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\UpgradeConfigService;
use SP\Domain\Core\File\MimeType;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Providers\FileLogHandlerProvider;
use SP\Infrastructure\File\FileException;
use SP\Providers\Auth\Ldap\LdapTypeEnum;
use SP\Util\VersionUtil;

use function SP\__u;

/**
 * Class UpgradeService
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradeConfig extends Service implements UpgradeConfigService
{
    /**
     * @var array Versiones actualizables
     */
    private const UPGRADES = [
        '200.17011202',
        '300.18111001',
        '300.18112501',
        '320.20062801',
    ];
    private ?ConfigDataInterface $configData = null;

    public function __construct(
        Application                       $application,
        FileLogHandlerProvider            $fileLogHandlerProvider,
        private readonly MimeTypesService $mimeTypes
    ) {
        parent::__construct($application);

        $this->eventDispatcher->attach($fileLogHandlerProvider);
    }

    public static function needsUpgrade(string $version): bool
    {
        return VersionUtil::checkVersion($version, self::UPGRADES);
    }

    /**
     * Migrar valores de configuración.
     * @throws FileException
     */
    public function upgrade(string $version, ConfigDataInterface $configData): void
    {
        $this->configData = $configData;

        $message = EventMessage::factory()->addDescription(__u('Update Configuration'));
        $this->eventDispatcher->notify('upgrade.config.start', new Event($this, $message));

        $upgradeable = array_filter(
            self::UPGRADES,
            static fn(string $upgradeVersion) => VersionUtil::checkVersion($version, $upgradeVersion)
        );

        foreach ($upgradeable as $upgradeVersion) {
            $this->applyUpgrade($upgradeVersion);
        }

        $this->eventDispatcher->notify('upgrade.config.end', new Event($this, $message));
    }

    /**
     * @throws FileException
     */
    private function applyUpgrade(string $version): void
    {
        switch ($version) {
            case '200.17011202':
                $this->upgradeV200B17011202($version);
                break;
            case '300.18111001':
                $this->upgradeV300B18111001($version);
                break;
            case '300.18112501':
                $this->upgradeV300B18112501($version);
                break;
            case '320.20062801':
                $this->upgradeV320B20062801($version);
                break;
        }
    }

    /**
     * @throws FileException
     */
    private function upgradeV200B17011202(string $version): void
    {
        $this->configData->setSiteTheme('material-blue');
        $this->configData->setConfigVersion($version);

        $this->config->save($this->configData, false);

        $this->eventDispatcher->notify(
            'upgrade.config.process',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('Update Configuration'))
                            ->addDetail(__u('Version'), $version)
            )
        );
    }

    /**
     * @throws FileException
     */
    private function upgradeV300B18111001(string $version): void
    {
        $extensions = array_map('strtolower', $this->configData->getFilesAllowedExts());
        $configMimeTypes = [];

        foreach ($extensions as $extension) {
            $match = array_filter(
                $this->mimeTypes->getMimeTypes(),
                static fn(MimeType $mimeType) => strcasecmp($mimeType->getExtension(), $extension) === 0
            );

            if (count($match) > 0) {
                $mimeType = array_shift($match);
                $configMimeTypes[] = $mimeType->getType();

                $this->eventDispatcher->notify(
                    'upgrade.config.process',
                    new Event(
                        $this,
                        EventMessage::factory()
                                    ->addDescription(__u('MIME type set for this extension'))
                                    ->addDetail(__u('MIME type'), $mimeType->getType())
                                    ->addDetail(__u('Extension'), $extension)
                    )
                );
            } else {
                $this->eventDispatcher->notify(
                    'upgrade.config.process',
                    new Event(
                        $this,
                        EventMessage::factory()
                                    ->addDescription(__u('MIME type not found for this extension'))
                                    ->addDetail(__u('Extension'), $extension)
                    )
                );
            }
        }

        $this->configData->setFilesAllowedMime($configMimeTypes);
        $this->configData->setConfigVersion($version);

        $this->config->save($this->configData, false);

        $this->eventDispatcher->notify(
            'upgrade.config.process',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('Update Configuration'))
                            ->addDetail(__u('Version'), $version)
            )
        );
    }

    /**
     * @throws FileException
     */
    private function upgradeV300B18112501(string $version): void
    {
        if ($this->configData->isLdapEnabled()) {
            $attributes = $this->configData->getAttributes();

            if (isset($attributes['ldapAds']) && $attributes['ldapAds']) {
                $this->configData->setLdapType(LdapTypeEnum::ADS->value);
            } else {
                $this->configData->setLdapType(LdapTypeEnum::STD->value);
            }

            $this->configData->setConfigVersion($version);

            $this->config->save($this->configData, false);

            $this->eventDispatcher->notify(
                'upgrade.config.process',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Update Configuration'))
                                ->addDetail(__u('Version'), $version)
                )
            );
        }
    }

    /**
     * @throws FileException
     */
    private function upgradeV320B20062801(string $version): void
    {
        if ($this->configData->isLdapEnabled()) {
            if ($this->configData->getLdapType() === LdapTypeEnum::AZURE->value) {
                $this->configData->setLdapType(LdapTypeEnum::ADS->value);
            }

            $this->configData->setConfigVersion($version);

            $this->config->save($this->configData, false);

            $this->eventDispatcher->notify(
                'upgrade.config.process',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Update Configuration'))
                                ->addDetail(__u('Version'), $version)
                )
            );
        }
    }
}
