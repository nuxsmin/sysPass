<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Exceptions\SPException;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Domain\Persistence\UpgradeDatabaseServiceInterface;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\MySQLFileParser;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Providers\Log\FileLogHandler;
use SP\Util\VersionUtil;

/**
 * Class UpgradeDatabaseService
 *
 * @package SP\Domain\Upgrade\Services
 */
final class UpgradeDatabaseService extends Service implements UpgradeDatabaseServiceInterface
{
    /**
     * @var array Versiones actualizables
     */
    private const UPGRADES = [
        '300.18010101',
        '300.18072302',
        '300.18072501',
        '300.18083001',
        '300.18083002',
        '300.18091101',
        '300.18092401',
        '300.18093001',
        '300.18111801',
        '300.18111901',
        '310.19012201',
        '310.19042701',
    ];

    private DatabaseInterface $database;

    public function __construct(Application $application, DatabaseInterface $database, FileLogHandler $fileLogHandler)
    {
        parent::__construct($application);

        $this->database = $database;
        $this->eventDispatcher->attach($fileLogHandler);
    }


    /**
     * Check if it needs to be upgraded
     */
    public static function needsUpgrade(string $version): bool
    {
        return empty($version) || VersionUtil::checkVersion($version, self::UPGRADES);
    }

    /**
     * Inicia el proceso de actualización de la BBDD.
     *
     * @throws FileException
     * @throws UpgradeException
     */
    public function upgrade(string $version, ConfigDataInterface $configData): bool
    {
        $this->eventDispatcher->notifyEvent(
            'upgrade.db.start',
            new Event($this, EventMessage::factory()->addDescription(__u('Update DB')))
        );

        foreach (self::UPGRADES as $upgradeVersion) {
            if (VersionUtil::checkVersion($version, $upgradeVersion)) {
                if ($this->applyPreUpgrade() === false) {
                    throw new UpgradeException(
                        __u('Error while applying an auxiliary update'),
                        SPException::CRITICAL,
                        __u('Please, check the event log for more details')
                    );
                }

                if ($this->applyUpgrade($upgradeVersion) === false) {
                    throw new UpgradeException(
                        __u('Error while updating the database'),
                        SPException::CRITICAL,
                        __u('Please, check the event log for more details')
                    );
                }

                logger('DB Upgrade: '.$upgradeVersion);

                $configData->setDatabaseVersion($upgradeVersion);

                $this->config->saveConfig($configData, false);
            }
        }

        $this->eventDispatcher->notifyEvent(
            'upgrade.db.end',
            new Event($this, EventMessage::factory()->addDescription(__u('Update DB')))
        );

        return true;
    }

    /**
     * Aplicar actualizaciones auxiliares antes de actualizar la BBDD
     */
    private function applyPreUpgrade(): bool
    {
        return true;
    }

    /**
     * Actualiza la BBDD según la versión.
     *
     * @throws \SP\Domain\Upgrade\Services\UpgradeException
     */
    private function applyUpgrade(string $version): bool
    {
        $queries = $this->getQueriesFromFile($version);

        if (count($queries) === 0) {
            logger(__('Update file does not contain data'), 'ERROR');

            throw new UpgradeException(__u('Update file does not contain data'), SPException::ERROR, $version);
        }

        foreach ($queries as $query) {
            try {
                $this->eventDispatcher->notifyEvent(
                    'upgrade.db.process',
                    new Event($this, EventMessage::factory()->addDetail(__u('Version'), $version))
                );

                // Direct PDO handling
                $this->database->getDbHandler()->getConnection()->exec($query);
            } catch (Exception $e) {
                processException($e);

                logger('SQL: '.$query);

                $this->eventDispatcher->notifyEvent(
                    'exception',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Error while updating the database'))
                            ->addDetail('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode()))
                    )
                );

                throw new UpgradeException(__u('Error while updating the database'));
            }
        }

        $this->eventDispatcher->notifyEvent(
            'upgrade.db.process',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Database updating was completed successfully.'))
            )
        );

        return true;
    }

    /**
     * Obtener las consultas de actualización desde un archivo
     *
     * @throws UpgradeException
     */
    private function getQueriesFromFile(string $filename): array
    {
        $fileName = SQL_PATH.
                    DIRECTORY_SEPARATOR.
                    str_replace('.', '', $filename).
                    '.sql';

        try {
            return (new MySQLFileParser(new FileHandler($fileName)))->parse('$$');
        } catch (FileException $e) {
            processException($e);

            throw new UpgradeException($e->getMessage());
        }
    }
}