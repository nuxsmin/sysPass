<?php
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

declare(strict_types=1);

namespace SP\Domain\Upgrade\Services;

use Exception;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Log\LoggerInterface;
use SP\Domain\Core\Log\NullLogger;
use SP\Domain\Common\Attributes\UpgradeVersion;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Upgrade\Ports\UpgradeHandlerService;
use SP\Domain\Database\Ports\DatabaseFileFactoryInterface;

use function SP\__u;

/**
 * Class UpgradeDatabase
 */
#[UpgradeVersion('400.24210101')]
final class UpgradeDatabase extends Service implements UpgradeHandlerService
{
    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly DatabaseInterface $database,
        private readonly string $sqlPath,
        private readonly DatabaseFileFactoryInterface $databaseFileFactory,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * @throws UpgradeException
     */
    public function apply(string $version, ConfigDataInterface $configData): bool
    {
        $count = 0;

        foreach ($this->getQueriesFromFile($version) as $query) {
            $count++;

            $this->eventDispatcher->notify(
                'upgrade.db.process',
                new Event($this, EventMessage::build()->addDetail(__u('Version'), $version))
            );

            try {
                $this->database->runQueryRaw($query);
            } catch (Exception $e) {
                $this->logger->debug('SQL: ' . $query);

                $this->eventDispatcher->notify(
                    'exception',
                    new Event(
                        $this,
                        EventMessage::build()
                            ->addDescription(__u('Error while updating the database'))
                            ->addDetail('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode()))
                    )
                );

                throw UpgradeException::error(__u('Error while updating the database'));
            }
        }

        if ($count === 0) {
            $this->logger->error(__u('Update file does not contain data'));

            throw UpgradeException::error(__u('Update file does not contain data'), $version);
        }

        $configData->setDatabaseVersion($version);

        $this->eventDispatcher->notify(
            'upgrade.db.process',
            new Event(
                $this,
                EventMessage::build()->addDescription(__u('Database updating was completed successfully.'))
            )
        );

        return true;
    }

    /**
     * @throws UpgradeException
     */
    private function getQueriesFromFile(string $version): iterable
    {
        $filename = implode(DIRECTORY_SEPARATOR, [$this->sqlPath, str_replace('.', '', $version) . '.sql']);

        try {
            return $this->databaseFileFactory->create($filename)->parse('$$');
        } catch (Exception $e) {
            throw UpgradeException::error($e->getMessage());
        }
    }
}
