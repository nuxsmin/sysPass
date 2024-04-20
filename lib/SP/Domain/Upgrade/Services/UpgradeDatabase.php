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
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\MysqlFileParser;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Providers\Log\FileLogHandler;

use function SP\__;
use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class UpgradeDatabase
 */
final class UpgradeDatabase extends UpgradeBase
{
    public function __construct(
        Application                        $application,
        FileLogHandler                     $fileLogHandler,
        private readonly DatabaseInterface $database,
    ) {
        parent::__construct($application, $fileLogHandler);
    }

    protected static function getUpgrades(): array
    {
        return [
            '400.24210101',
        ];
    }

    protected function commitVersion(string $version): void
    {
        $this->configData->setDatabaseVersion($version);
    }

    /**
     * @throws UpgradeException
     */
    protected function applyUpgrade(string $version): bool
    {
        $queries = $this->getQueriesFromFile($version);

        if (count($queries) === 0) {
            logger(__('Update file does not contain data'), 'ERROR');

            throw UpgradeException::error(__u('Update file does not contain data'), $version);
        }

        foreach ($queries as $query) {
            try {
                $this->eventDispatcher->notify(
                    'upgrade.db.process',
                    new Event($this, EventMessage::factory()->addDetail(__u('Version'), $version))
                );

                // Direct PDO handling
                $this->database->runQueryRaw($query);
            } catch (Exception $e) {
                processException($e);

                logger('SQL: ' . $query);

                $this->eventDispatcher->notify(
                    'exception',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Error while updating the database'))
                            ->addDetail('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode()))
                    )
                );

                throw UpgradeException::error(__u('Error while updating the database'));
            }
        }

        $this->eventDispatcher->notify(
            'upgrade.db.process',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Database updating was completed successfully.'))
            )
        );

        return true;
    }

    /**
     * @throws UpgradeException
     */
    private function getQueriesFromFile(string $filename): iterable
    {
        $fileName = SQL_PATH .
                    DIRECTORY_SEPARATOR .
                    str_replace('.', '', $filename) .
                    '.sql';

        try {
            return (new MysqlFileParser(new FileHandler($fileName)))->parse('$$');
        } catch (FileException $e) {
            processException($e);

            throw UpgradeException::error($e->getMessage());
        }
    }
}
