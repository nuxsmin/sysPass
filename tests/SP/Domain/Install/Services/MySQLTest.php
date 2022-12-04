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

namespace SP\Tests\Domain\Install\Services;

use PDOException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Services\MysqlService;
use SP\Infrastructure\Database\DatabaseFileInterface;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\DbStorageInterface;
use SP\Infrastructure\File\FileException;
use SP\Tests\Stubs\Pdo;
use SP\Tests\UnitaryTestCase;
use function SP\__;
use function SP\__u;

/**
 * Class MySQLTest
 *
 * @group unitary
 */
class MySQLTest extends UnitaryTestCase
{
    private DbStorageInterface    $DBStorage;
    private MysqlService          $mysql;
    private Pdo                   $pdo;
    private InstallData           $installData;
    private ConfigDataInterface   $configData;
    private DatabaseFileInterface $databaseFile;
    private DatabaseUtil          $databaseUtil;

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testConnectDatabaseIsSuccessful(): void
    {
        $this->DBStorage->expects(self::once())->method('getConnectionSimple');

        $this->mysql->connectDatabase();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testConnectDatabaseIsNotSuccessful(): void
    {
        $this->DBStorage->expects(self::once())
            ->method('getConnectionSimple')
            ->willThrowException(
                new SPException('test')
            );

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('Unable to connect to DB');

        $this->mysql->connectDatabase();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testSetupUserIsSuccessful(): void
    {
        $this->pdo->mock('SELECT COUNT(*) FROM mysql.user WHERE `user` = ? AND (`host` = ? OR `host` = ?)', []);

        [$user, $pass] = $this->mysql->setupDbUser();

        $this->assertSame(preg_match('/sp_\w+/', $user), 1);
        $this->assertNotEmpty($pass);
        $this->assertEquals(16, strlen($pass));

    }

    public function testSetupUserIsNotSuccessful(): void
    {
        $this->pdo->mock('SELECT COUNT(*) FROM mysql.user WHERE `user` = ? AND (`host` = ? OR `host` = ?)', []);

        $pdoException = new PDOException('test');

        $this->DBStorage->expects(self::once())
            ->method('getConnectionSimple')
            ->willThrowException($pdoException);

        $this->expectException(SPException::class);
        $this->expectExceptionMessageMatches('/Unable to check the sysPass user \(sp_\w+\)/');

        $this->mysql->setupDbUser();
    }

    public function testCheckDatabaseDoesNotExist(): void
    {
        $this->pdo->mock('SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1', [], []);

        $this->assertFalse($this->mysql->checkDatabaseExists());
    }

    public function testCheckDatabaseExists(): void
    {
        $this->pdo->mock(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1',
            [[1]],
            [$this->installData->getDbName()]
        );

        $this->assertTrue($this->mysql->checkDatabaseExists());
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsSuccessful(): void
    {
        $this->pdo->mock(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1',
            [],
            [$this->installData->getDbName()]
        );

        $this->configData->setDbUser(self::$faker->userName);

        $execArguments = [
            [
                sprintf(
                    'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                    $this->installData->getDbName()
                ),
            ],
            [
                sprintf(
                    'GRANT ALL PRIVILEGES ON `%s`.* TO %s@%s',
                    $this->installData->getDbName(),
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHost()
                ),
            ],
            ['FLUSH PRIVILEGES'],
        ];

        $this->pdo->expects(self::exactly(3))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->mysql->createDatabase($this->configData->getDbUser());
    }


    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsSuccessfulInHostingMode(): void
    {
        $this->installData->setHostingMode(true);

        $this->pdo->expects(self::once())
            ->method('exec')
            ->with(
                sprintf(
                    'USE `%s`',
                    $this->installData->getDbName()
                )
            );

        $this->mysql->createDatabase();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsNotSuccessfulWithDuplicateDatabase(): void
    {
        $this->pdo->mock(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1',
            [[1]],
            [$this->installData->getDbName()]
        );

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('The database already exists');

        $this->mysql->createDatabase();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsSuccessfulWithDns(): void
    {
        $this->pdo->mock(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1',
            [],
            [$this->installData->getDbName()]
        );

        $this->configData->setDbUser(self::$faker->userName);
        $this->installData->setDbAuthHostDns(self::$faker->domainName);

        $execArguments = [
            [
                sprintf(
                    'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                    $this->installData->getDbName()
                ),
            ],
            [
                sprintf(
                    'GRANT ALL PRIVILEGES ON `%s`.* TO %s@%s',
                    $this->installData->getDbName(),
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHost()
                ),
            ],
            [
                sprintf(
                    'GRANT ALL PRIVILEGES ON `%s`.* TO %s@%s',
                    $this->installData->getDbName(),
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHostDns()
                ),
            ],
            ['FLUSH PRIVILEGES'],
        ];

        $this->pdo->expects(self::exactly(4))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->mysql->createDatabase($this->configData->getDbUser());
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsNotSuccessfulWithCreateError(): void
    {
        $this->pdo->mock(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1',
            [],
            [$this->installData->getDbName()]
        );

        $pdoException = new PDOException('test');

        $this->pdo->method('exec')
            ->with(
                sprintf(
                    'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                    $this->installData->getDbName()
                ),
            )
            ->willThrowException($pdoException);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(sprintf(__('Error while creating the DB (\'%s\')'), $pdoException->getMessage()));

        $this->mysql->createDatabase();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsNotSuccessfulWithPermissionError(): void
    {
        $this->pdo->mock(
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1',
            [],
            [$this->installData->getDbName()]
        );

        $this->configData->setDbUser(self::$faker->userName);

        $matcher = $this->any();
        $execArguments = [
            [
                sprintf(
                    'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                    $this->installData->getDbName()
                ),
            ],
            [
                sprintf(
                    'GRANT ALL PRIVILEGES ON `%s`.* TO %s@%s',
                    $this->installData->getDbName(),
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHost()
                ),
            ],
        ];
        $pdoException = new PDOException('test');

        $this->pdo->expects($matcher)
            ->method('exec')
            ->withConsecutive(...$execArguments)
            ->willReturnCallback(function () use ($matcher, $pdoException) {
                if ($matcher->getInvocationCount() === 3) {
                    throw $pdoException;
                }
            });

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(__('Error while setting the database permissions (\'%s\')'), $pdoException->getMessage())
        );

        $this->mysql->createDatabase($this->configData->getDbUser());
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDatabaseIsNotSuccessfulInHostingMode(): void
    {
        $this->installData->setHostingMode(true);

        $pdoException = new PDOException('test');

        $this->pdo->expects(self::once())
            ->method('exec')
            ->with(
                sprintf(
                    'USE `%s`',
                    $this->installData->getDbName()
                )
            )->willThrowException($pdoException);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(
                __('Error while selecting \'%s\' database (%s)'),
                $this->installData->getDbName(),
                $pdoException->getMessage()
            )
        );

        $this->mysql->createDatabase();
    }

    public function testRollbackIsSuccessful(): void
    {
        $this->configData->setDbUser(self::$faker->userName);

        $execArguments = [
            [
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $this->installData->getDbName()
                ),
            ],
            [
                sprintf(
                    'DROP USER IF EXISTS %s@%s',
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHost()
                ),
            ],
            [
                sprintf(
                    'DROP USER IF EXISTS %s@%s',
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHostDns()
                ),
            ],
        ];

        $this->pdo->expects(self::exactly(3))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->mysql->rollback($this->configData->getDbUser());
    }

    public function testRollbackIsSuccessfulWithSameDnsHost(): void
    {
        $this->configData->setDbUser(self::$faker->userName);
        $this->installData->setDbAuthHost('localhost');
        $this->installData->setDbAuthHostDns('localhost');

        $execArguments = [
            [
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $this->installData->getDbName()
                ),
            ],
            [
                sprintf(
                    'DROP USER IF EXISTS %s@%s',
                    $this->configData->getDbUser(),
                    $this->installData->getDbAuthHost()
                ),
            ],
        ];

        $this->pdo->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->mysql->rollback($this->configData->getDbUser());
    }

    public function testRollbackIsSuccessfulWithHostingMode(): void
    {
        $this->installData->setHostingMode(true);

        $dropRegex = '/DROP TABLE IF EXISTS `'.$this->installData->getDbName().'`\.`\w+`/';
        $this->pdo->expects(self::exactly(count(DatabaseUtil::TABLES)))
            ->method('exec')
            ->with($this->callback(fn($arg) => preg_match($dropRegex, $arg) > 0));

        $this->mysql->rollback();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBStructureIsSuccessful(): void
    {
        $execArguments = [
            [
                sprintf('USE `%s`', $this->installData->getDbName()),
            ],
            [
                'DROP TABLE IF EXISTS `Account`;',
            ],
        ];

        $this->pdo->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->databaseFile->expects(self::once())
            ->method('parse')
            ->willReturn(['DROP TABLE IF EXISTS `Account`;']);

        $this->mysql->createDBStructure();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBStructureIsNotSuccessfulWithUseDatabaseError(): void
    {
        $pdoException = new PDOException("test");

        $this->pdo->expects(self::once())
            ->method('exec')
            ->with(sprintf('USE `%s`', $this->installData->getDbName()))
            ->willThrowException($pdoException);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(
                __('Error while selecting \'%s\' database (%s)'),
                $this->installData->getDbName(),
                $pdoException->getMessage()
            )
        );

        $this->mysql->createDBStructure();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBStructureIsNotSuccessfulWithCreateSchemaError(): void
    {
        $pdoException = new PDOException("test");
        $execArguments = [
            [
                sprintf('USE `%s`', $this->installData->getDbName()),
            ],
            [
                'DROP TABLE IF EXISTS `Account`;',
            ],
            [
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $this->installData->getDbName()
                ),
            ]
        ];
        $matcher = $this->exactly(3);

        $this->pdo->expects($matcher)
            ->method('exec')
            ->withConsecutive(...$execArguments)
            ->willReturnCallback(function () use ($matcher, $pdoException) {
                if ($matcher->getInvocationCount() === 2) {
                    throw $pdoException;
                }
            });

        $this->databaseFile->expects(self::once())
            ->method('parse')
            ->willReturn(['DROP TABLE IF EXISTS `Account`;']);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(__('Error while creating the DB (\'%s\')'), $pdoException->getMessage())
        );

        $this->mysql->createDBStructure();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBStructureIsNotSuccessfulWithParseSchemaError(): void
    {
        $fileException = new FileException("test");
        $execArguments = [
            [
                sprintf('USE `%s`', $this->installData->getDbName()),
            ],
            [
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $this->installData->getDbName()
                ),
            ]
        ];
        $matcher = $this->exactly(2);

        $this->pdo->expects($matcher)
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->databaseFile->expects(self::once())
            ->method('parse')
            ->willThrowException($fileException);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(__('Error while creating the DB (\'%s\')'), $fileException->getMessage())
        );

        $this->mysql->createDBStructure();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCheckConnectionIsSuccessful(): void
    {
        $this->databaseUtil->expects(self::once())
            ->method('checkDatabaseTables')
            ->with($this->installData->getDbName())
            ->willReturn(true);

        $this->mysql->checkConnection();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCheckConnectionIsNotSuccessful(): void
    {
        $this->databaseUtil->expects(self::once())
            ->method('checkDatabaseTables')
            ->with($this->installData->getDbName())
            ->willReturn(false);

        $execArguments = [
            [
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $this->installData->getDbName()
                ),
            ]
        ];

        $this->pdo->expects(self::once())
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(__u('Error while checking the database'));

        $this->mysql->checkConnection();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBUserIsSuccessful(): void
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $execArguments = [
            [
                sprintf(
                    'CREATE USER %s@%s IDENTIFIED BY %s',
                    $user,
                    $this->installData->getDbAuthHost(),
                    $pass
                ),
            ],
            [
                'FLUSH PRIVILEGES',
            ],
        ];

        $this->pdo->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->mysql->createDBUser($user, $pass);
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBUserIsSuccessfulWithDns(): void
    {
        $this->installData->setDbAuthHostDns(self::$faker->domainName);

        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $execArguments = [
            [
                sprintf(
                    'CREATE USER %s@%s IDENTIFIED BY %s',
                    $user,
                    $this->installData->getDbAuthHost(),
                    $pass
                ),
            ],
            [
                sprintf(
                    'CREATE USER %s@%s IDENTIFIED BY %s',
                    $user,
                    $this->installData->getDbAuthHostDns(),
                    $pass
                ),
            ],
            [
                'FLUSH PRIVILEGES',
            ],
        ];

        $this->pdo->expects(self::exactly(3))
            ->method('exec')
            ->withConsecutive(...$execArguments);

        $this->mysql->createDBUser($user, $pass);
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBUserIsSuccessfulWithHostingMode(): void
    {
        $this->installData->setHostingMode(true);

        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $this->pdo->expects(self::exactly(0))->method('exec');

        $this->mysql->createDBUser($user, $pass);
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateDBUserIsNotSuccessful(): void
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;
        
        $this->pdo->expects(self::once())
            ->method('exec')
            ->willThrowException(new PDOException('test'));

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(sprintf(__u('Error while creating the MySQL connection user \'%s\''), $user));

        $this->mysql->createDBUser($user, $pass);
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = $this->getMockBuilder(Pdo::class)->enableProxyingToOriginalMethods()->getMock();

        $this->DBStorage = $this->createMock(DbStorageInterface::class);
        $this->DBStorage->method('getConnectionSimple')->willReturn($this->pdo);
        $this->databaseFile = $this->createMock(DatabaseFileInterface::class);

        $this->installData = $this->getInstallData();
        $this->configData = $this->config->getConfigData();
        $this->databaseUtil = $this->createMock(DatabaseUtil::class);
        $this->mysql = new MysqlService(
            $this->DBStorage, $this->installData, $this->databaseFile, $this->databaseUtil
        );
    }

    /**
     * @return \SP\Domain\Install\Adapters\InstallData
     */
    private function getInstallData(): InstallData
    {
        $params = new InstallData();
        $params->setDbAdminUser(self::$faker->userName);
        $params->setDbAdminPass(self::$faker->password);
        $params->setDbName(self::$faker->colorName);
        $params->setDbHost(self::$faker->domainName);
        $params->setAdminLogin(self::$faker->userName);
        $params->setAdminPass(self::$faker->password);
        $params->setMasterPassword(self::$faker->password(11));
        $params->setSiteLang(self::$faker->languageCode);
        $params->setDbAuthHost(self::$faker->ipv4);

        return $params;
    }
}
