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

namespace SP\Domain\Install\Services;

use Exception;
use PDOException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Install\Adapters\InstallData;
use SP\Infrastructure\Database\DatabaseFileInterface;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\DbStorageInterface;
use SP\Infrastructure\File\FileException;
use SP\Util\PasswordUtil;

use function SP\__;
use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class MySQL
 *
 * @package SP\Domain\Install\Services
 */
final class MysqlService implements DatabaseSetupInterface
{
    private InstallData           $installData;
    private DbStorageInterface    $DBStorage;
    private DatabaseFileInterface $databaseFile;
    private DatabaseUtil          $databaseUtil;

    /**
     * MySQL constructor.
     *
     */
    public function __construct(
        DbStorageInterface $DBStorage,
        InstallData $installData,
        DatabaseFileInterface $databaseFile,
        DatabaseUtil $databaseUtil
    ) {
        $this->installData = $installData;
        $this->DBStorage = $DBStorage;
        $this->databaseFile = $databaseFile;
        $this->databaseUtil = $databaseUtil;
    }

    /**
     * Conectar con la BBDD
     *
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     *
     * @throws SPException
     */
    public function connectDatabase(): void
    {
        try {
            $this->DBStorage->getConnectionSimple();
        } catch (SPException $e) {
            processException($e);

            throw new SPException(
                __u('Unable to connect to DB'),
                SPException::ERROR,
                $e->getHint(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws SPException
     * @throws Exception
     */
    public function setupDbUser(): array
    {
        $user = substr(uniqid('sp_', true), 0, 16);
        $pass = PasswordUtil::randomPassword();

        try {
            // Comprobar si el usuario proporcionado existe
            $sth = $this->DBStorage->getConnectionSimple()
                ->prepare('SELECT COUNT(*) FROM mysql.user WHERE `user` = ? AND (`host` = ? OR `host` = ?)');

            $sth->execute([
                $user,
                $this->installData->getDbAuthHost(),
                $this->installData->getDbAuthHostDns(),
            ]);

            // Si no existe el usuario, se intenta crear
            if ((int)$sth->fetchColumn() === 0) {
                $this->createDBUser($user, $pass);
            }
        } catch (PDOException $e) {
            processException($e);

            throw new SPException(
                sprintf(__('Unable to check the sysPass user (%s)'), $user),
                SPException::CRITICAL,
                __u('Please, check the DB connection user rights'),
                $e->getCode(),
                $e
            );
        }

        return [$user, $pass];
    }

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     *
     * @throws SPException
     */
    public function createDBUser(string $user, string $pass): void
    {
        if ($this->installData->isHostingMode()) {
            return;
        }

        logger('Creating DB user');

        try {
            $query = 'CREATE USER %s@%s IDENTIFIED BY %s';

            $dbc = $this->DBStorage->getConnectionSimple();

            $dbc->exec(
                sprintf(
                    $query,
                    $dbc->quote($user),
                    $dbc->quote($this->installData->getDbAuthHost()),
                    $dbc->quote($pass)
                )
            );

            if (!empty($this->installData->getDbAuthHostDns())
                && $this->installData->getDbAuthHost() !== $this->installData->getDbAuthHostDns()
            ) {
                $dbc->exec(
                    sprintf(
                        $query,
                        $dbc->quote($user),
                        $this->installData->getDbAuthHostDns(),
                        $dbc->quote($pass)
                    )
                );
            }

            $dbc->exec('FLUSH PRIVILEGES');
        } catch (PDOException $e) {
            processException($e);

            throw new SPException(
                sprintf(__u('Error while creating the MySQL connection user \'%s\''), $user),
                SPException::CRITICAL,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Crear la base de datos
     *
     * @throws SPException
     */
    public function createDatabase(?string $dbUser = null): void
    {
        if (!$this->installData->isHostingMode()) {
            if ($this->checkDatabaseExists()) {
                throw new SPException(
                    __u('The database already exists'),
                    SPException::ERROR,
                    __u('Please, enter a new database or delete the existing one')
                );
            }

            try {
                $dbc = $this->DBStorage->getConnectionSimple();

                $dbc->exec(
                    sprintf(
                        'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                        $this->installData->getDbName()
                    )
                );
            } catch (PDOException $e) {
                throw new SPException(
                    sprintf(__('Error while creating the DB (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Please check the database user permissions'),
                    $e->getCode(),
                    $e
                );
            }

            try {
                $query = 'GRANT ALL PRIVILEGES ON `%s`.* TO %s@%s';

                $dbc->exec(
                    sprintf(
                        $query,
                        $this->installData->getDbName(),
                        $dbc->quote($dbUser),
                        $dbc->quote($this->installData->getDbAuthHost())
                    )
                );

                if (!empty($this->installData->getDbAuthHostDns())
                    && $this->installData->getDbAuthHost() !== $this->installData->getDbAuthHostDns()
                ) {
                    $dbc->exec(
                        sprintf(
                            $query,
                            $this->installData->getDbName(),
                            $dbc->quote($dbUser),
                            $dbc->quote($this->installData->getDbAuthHostDns())
                        )
                    );
                }

                $dbc->exec('FLUSH PRIVILEGES');
            } catch (PDOException $e) {
                processException($e);

                $this->rollback($dbUser);

                throw new SPException(
                    sprintf(__('Error while setting the database permissions (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Please check the database user permissions'),
                    $e->getCode(),
                    $e
                );
            }
        } else {
            $this->checkDatabase(__u('You need to create it and assign the needed permissions'));
        }
    }

    public function checkDatabaseExists(): bool
    {
        $sth = $this->DBStorage->getConnectionSimple()
            ->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1');
        $sth->execute([$this->installData->getDbName()]);

        return (int)$sth->fetchColumn() === 1;
    }

    public function rollback(?string $dbUser = null): void
    {
        $dbc = $this->DBStorage->getConnectionSimple();

        if ($this->installData->isHostingMode()) {
            foreach (DatabaseUtil::TABLES as $table) {
                $dbc->exec(
                    sprintf(
                        'DROP TABLE IF EXISTS `%s`.`%s`',
                        $this->installData->getDbName(),
                        $table
                    )
                );
            }
        } else {
            $dbc->exec(
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $this->installData->getDbName()
                )
            );

            if ($dbUser) {
                $dbc->exec(
                    sprintf(
                        'DROP USER IF EXISTS %s@%s',
                        $dbc->quote($dbUser),
                        $dbc->quote($this->installData->getDbAuthHost())
                    )
                );

                if ($this->installData->getDbAuthHostDns()
                    && $this->installData->getDbAuthHost() !== $this->installData->getDbAuthHostDns()) {
                    $dbc->exec(
                        sprintf(
                            'DROP USER IF EXISTS %s@%s',
                            $dbc->quote($dbUser),
                            $dbc->quote($this->installData->getDbAuthHostDns())
                        )
                    );
                }
            }
        }

        logger('Rollback');
    }

    /**
     * @throws SPException
     */
    private function checkDatabase(string $exceptionHint): void
    {
        try {
            $this->DBStorage
                ->getConnectionSimple()
                ->exec(sprintf('USE `%s`', $this->installData->getDbName()));
        } catch (PDOException $e) {
            throw new SPException(
                sprintf(
                    __('Error while selecting \'%s\' database (%s)'),
                    $this->installData->getDbName(),
                    $e->getMessage()
                ),
                SPException::CRITICAL,
                $exceptionHint,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws SPException
     */
    public function createDBStructure(): void
    {
        $this->checkDatabase(
            __u(
                'Unable to use the database to create the structure. Please check the permissions and it does not exist.'
            )
        );

        try {
            $dbc = $this->DBStorage->getConnectionSimple();

            foreach ($this->databaseFile->parse() as $query) {
                $dbc->exec($query);
            }
        } catch (PDOException $e) {
            processException($e);

            $this->rollback();

            throw new SPException(
                sprintf(__('Error while creating the DB (\'%s\')'), $e->getMessage()),
                SPException::CRITICAL,
                __u('Error while creating database structure.'),
                $e->getCode(),
                $e
            );
        } catch (FileException $e) {
            processException($e);

            $this->rollback();

            throw new SPException(
                sprintf(__('Error while creating the DB (\'%s\')'), $e->getMessage()),
                SPException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Comprobar la conexión a la BBDD
     *
     * @throws SPException
     */
    public function checkConnection(): void
    {
        if (!$this->databaseUtil->checkDatabaseTables($this->installData->getDbName())) {
            $this->rollback();

            throw new SPException(
                __u('Error while checking the database'),
                SPException::CRITICAL,
                __u('Please, try the installation again')
            );
        }
    }
}
