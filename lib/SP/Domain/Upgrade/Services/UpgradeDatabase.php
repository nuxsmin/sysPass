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
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\UpgradeVersion;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Upgrade\Ports\UpgradeHandlerService;
use SP\Infrastructure\Database\MysqlFileParser;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileSystem;

use function SP\__;
use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class UpgradeDatabase
 */
#[UpgradeVersion('400.24210101')]
final class UpgradeDatabase extends Service implements UpgradeHandlerService
{
    public function __construct(
        Application                        $application,
        private readonly DatabaseInterface $database,
        private readonly string $sqlPath
    ) {
        parent::__construct($application);
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
                processException($e);

                logger('SQL: ' . $query);

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
            logger(__('Update file does not contain data'), 'ERROR');

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
        $filename = FileSystem::buildPath($this->sqlPath, str_replace('.', '', $version) . '.sql');

        try {
            return (new MysqlFileParser(new FileHandler($filename)))->parse('$$');
        } catch (Exception $e) {
            processException($e);

            throw UpgradeException::error($e->getMessage());
        }
    }
}
