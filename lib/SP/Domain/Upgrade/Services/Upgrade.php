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

namespace SP\Domain\Upgrade\Services;

use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\UpgradeVersion;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Ports\UpgradeHandlerService;
use SP\Domain\Upgrade\Ports\UpgradeService;
use SP\Infrastructure\File\FileException;
use Throwable;

use function SP\__u;
use function SP\logger;

/**
 * Class Upgrade
 */
final class Upgrade extends Service implements UpgradeService
{
    protected ?ConfigDataInterface $configData = null;

    /**
     * @var array<string> $upgradeHandlers
     */
    private array $upgradeHandlers = [];

    public function __construct(
        Application                         $application,
        FileHandlerProvider                 $fileHandlerProvider,
        private readonly ContainerInterface $container
    ) {
        parent::__construct($application);

        $this->eventDispatcher->attach($fileHandlerProvider);
    }

    /**
     * @inheritDoc
     * @throws FileException
     * @throws ServiceException
     * @throws UpgradeException
     */
    public function upgrade(string $version, ConfigDataInterface $configData): void
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

        foreach ($this->getTargetUpgradeHandlers($version) as $upgradeHandler) {
            if (!$upgradeHandler->apply($version, $configData)) {
                throw UpgradeException::critical(
                    __u('Error while applying the update'),
                    __u('Please, check the event log for more details')
                );
            }

            logger('Upgrade: ' . $upgradeHandler::class);

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

    /**
     * @return iterable<UpgradeHandlerService>
     * @throws ServiceException
     */
    private function getTargetUpgradeHandlers(string $version): iterable
    {
        try {
            foreach ($this->upgradeHandlers as $class) {
                $reflection = new ReflectionClass($class);
                /** @var ReflectionAttribute<UpgradeVersion> $attribute */
                foreach ($reflection->getAttributes(UpgradeVersion::class) as $attribute) {
                    $instance = $attribute->newInstance();

                    if (Version::checkVersion($version, $instance->version)) {
                        yield $this->container->get($class);
                    }
                }
            }
        } catch (Throwable $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * @throws ServiceException
     * @throws InvalidClassException
     */
    public function registerUpgradeHandler(string $class): void
    {
        if (!class_exists($class) || !is_subclass_of($class, UpgradeHandlerService::class)) {
            throw InvalidClassException::error('Class does not either exist or implement UpgradeService class');
        }

        $hash = sha1($class);

        if (array_key_exists($hash, $this->upgradeHandlers)) {
            throw ServiceException::error('Class already registered');
        }

        $this->upgradeHandlers[$hash] = $class;
    }
}
