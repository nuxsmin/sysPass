<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Domain\Install\Services;

use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Services\MysqlService;
use SP\Infrastructure\Database\DatabaseFileInterface;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\DbStorageInterface;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;

use function SP\__;
use function SP\__u;

/**
 * Class MySQLTest
 *
 */
#[Group('unitary')]
class MySQLTest extends UnitaryTestCase
{
    private DbStorageInterface|MockObject    $dbStorage;
    private MysqlService                     $mysqlService;
    private PDO|MockObject                   $pdo;
    private InstallData                      $installData;
    private ConfigDataInterface              $configData;
    private DatabaseFileInterface|MockObject $databaseFile;
    private DatabaseUtil|MockObject          $databaseUtil;

    /**
     * @throws SPException
     */
    public function testConnectDatabaseIsSuccessful(): void
    {
        $this->dbStorage->expects(self::once())->method('getConnectionSimple');

        $this->mysqlService->connectDatabase();
    }

    /**
     * @throws SPException
     */
    public function testConnectDatabaseIsNotSuccessful(): void
    {
        $this->dbStorage->expects(self::once())
                        ->method('getConnectionSimple')
                        ->willThrowException(
                            new SPException('test')
                        );

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('Unable to connect to DB');

        $this->mysqlService->connectDatabase();
    }

    /**
     * @throws SPException
     */
    public function testSetupUserIsSuccessful(): void
    {
        $query = 'SELECT COUNT(*) FROM mysql.user WHERE `user` = ? AND (`host` = ? OR `host` = ?)';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                function ($args) {
                    return str_starts_with($args[0], 'sp_')
                           && $args[1] === $this->installData->getDbAuthHost()
                           && $args[2] === null;
                }
            )
        );

        [$user, $pass] = $this->mysqlService->setupDbUser();

        $this->assertSame(preg_match('/sp_\w+/', $user), 1);
        $this->assertNotEmpty($pass);
        $this->assertEquals(16, strlen($pass));
    }

    public function testSetupUserIsNotSuccessful(): void
    {
        $this->dbStorage->expects(self::once())
                        ->method('getConnectionSimple')
                        ->willThrowException(new PDOException('test'));

        $this->expectException(SPException::class);
        $this->expectExceptionMessageMatches('/Unable to check the sysPass user \(sp_\w+\)/');

        $this->mysqlService->setupDbUser();
    }

    public function testCheckDatabaseDoesNotExist(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(0);

        $this->assertFalse($this->mysqlService->checkDatabaseExists());
    }

    public function testCheckDatabaseExists(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(1);

        $this->assertTrue($this->mysqlService->checkDatabaseExists());
    }

    /**
     * @throws SPException
     * @throws Exception
     */
    public function testCreateDatabaseIsSuccessful(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(0);

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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->pdo->expects(self::exactly(2))->method('quote')
                  ->with(
                      ...self::withConsecutive(
                      [$this->configData->getDbUser()],
                      [$this->installData->getDbAuthHost()],
                  )
                  )->willReturnArgument(0);

        $this->mysqlService->createDatabase($this->configData->getDbUser());
    }

    /**
     * @throws SPException
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

        $this->mysqlService->createDatabase();
    }

    /**
     * @throws SPException
     */
    public function testCreateDatabaseIsNotSuccessfulWithDuplicateDatabase(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(1);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('The database already exists');

        $this->mysqlService->createDatabase();
    }

    /**
     * @throws SPException
     * @throws Exception
     */
    public function testCreateDatabaseIsSuccessfulWithDns(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(0);

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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->pdo->expects(self::exactly(4))->method('quote')
                  ->with(
                      ...self::withConsecutive(
                      [$this->configData->getDbUser()],
                      [$this->installData->getDbAuthHost()],
                      [$this->configData->getDbUser()],
                      [$this->installData->getDbAuthHostDns()],
                  )
                  )->willReturnArgument(0);

        $this->mysqlService->createDatabase($this->configData->getDbUser());
    }

    /**
     * @throws SPException
     */
    public function testCreateDatabaseIsNotSuccessfulWithCreateError(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(0);

        $this->pdo->expects(self::once())
                  ->method('exec')
                  ->with(
                      sprintf(
                          'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                          $this->installData->getDbName()
                      )
                  )
                  ->willThrowException(new PDOException('test'));

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(sprintf(__('Error while creating the DB (\'%s\')'), 'test'));

        $this->mysqlService->createDatabase();
    }

    /**
     * @throws SPException
     * @throws Exception
     */
    public function testCreateDatabaseIsNotSuccessfulWithPermissionError(): void
    {
        $query = 'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $pdoStatement = $this->createMock(PDOStatement::class);

        $this->pdo->expects(self::once())->method('prepare')->with($query)->willReturn($pdoStatement);
        $pdoStatement->expects(self::once())->method('execute')->with(
            new Callback(
                fn($args) => $args[0] === $this->installData->getDbName()
            )
        );
        $pdoStatement->expects(self::once())->method('fetchColumn')->willReturn(0);

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

        $matcher = $this->any();

        $this->pdo->expects($matcher)
                  ->method('exec')
                  ->with(...self::withConsecutive(...$execArguments))
                  ->willReturnCallback(function () use ($matcher) {
                      if ($matcher->numberOfInvocations() === 2) {
                          throw new PDOException('test');
                      }

                      return 1;
                  });

        $this->pdo->method('quote')->willReturnArgument(0);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(__('Error while setting the database permissions (\'%s\')'), 'test')
        );

        $this->mysqlService->createDatabase($this->configData->getDbUser());
    }

    /**
     * @throws SPException
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

        $this->mysqlService->createDatabase();
    }

    public function testRollbackIsSuccessful(): void
    {
        $this->configData->setDbUser(self::$faker->userName);
        $this->installData->setDbAuthHostDns(self::$faker->domainName);

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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->pdo->method('quote')->willReturnArgument(0);

        $this->mysqlService->rollback($this->configData->getDbUser());
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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->pdo->method('quote')->willReturnArgument(0);

        $this->mysqlService->rollback($this->configData->getDbUser());
    }

    public function testRollbackIsSuccessfulWithHostingMode(): void
    {
        $this->installData->setHostingMode(true);

        $dropRegex = '/DROP TABLE IF EXISTS `'.$this->installData->getDbName().'`\.`\w+`/';
        $this->pdo->expects(self::exactly(count(DatabaseUtil::TABLES)))
                  ->method('exec')
                  ->with($this->callback(fn($arg) => preg_match($dropRegex, $arg) > 0));

        $this->mysqlService->rollback();
    }

    /**
     * @throws SPException
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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->databaseFile->expects(self::once())
                           ->method('parse')
                           ->willReturn(['DROP TABLE IF EXISTS `Account`;']);

        $this->mysqlService->createDBStructure();
    }

    /**
     * @throws SPException
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

        $this->mysqlService->createDBStructure();
    }

    /**
     * @throws SPException
     */
    public function testCreateDBStructureIsNotSuccessfulWithCreateSchemaError(): void
    {
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
            ],
        ];
        $matcher = $this->exactly(3);

        $this->pdo->expects($matcher)
                  ->method('exec')
                  ->with(...self::withConsecutive(...$execArguments))
                  ->willReturnCallback(function () use ($matcher) {
                      if ($matcher->numberOfInvocations() === 2) {
                          throw  new PDOException('test');
                      }

                      return 1;
                  });

        $this->databaseFile->expects(self::once())
                           ->method('parse')
                           ->willReturn(['DROP TABLE IF EXISTS `Account`;']);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(__('Error while creating the DB (\'%s\')'), 'test')
        );

        $this->mysqlService->createDBStructure();
    }

    /**
     * @throws SPException
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
            ],
        ];
        $matcher = $this->exactly(2);

        $this->pdo->expects($matcher)
                  ->method('exec')
                  ->with(...self::withConsecutive(...$execArguments));

        $this->databaseFile->expects(self::once())
                           ->method('parse')
                           ->willThrowException($fileException);

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(
            sprintf(__('Error while creating the DB (\'%s\')'), $fileException->getMessage())
        );

        $this->mysqlService->createDBStructure();
    }

    /**
     * @throws SPException
     */
    public function testCheckConnectionIsSuccessful(): void
    {
        $this->databaseUtil->expects(self::once())
                           ->method('checkDatabaseTables')
                           ->with($this->installData->getDbName())
                           ->willReturn(true);

        $this->mysqlService->checkConnection();
    }

    /**
     * @throws SPException
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
            ],
        ];

        $this->pdo->expects(self::once())
                  ->method('exec')
                  ->with(...self::withConsecutive(...$execArguments));

        $this->expectException(SPException::class);
        $this->expectExceptionMessage(__u('Error while checking the database'));

        $this->mysqlService->checkConnection();
    }

    /**
     * @throws SPException
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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->pdo->method('quote')->willReturnArgument(0);

        $this->mysqlService->createDBUser($user, $pass);
    }

    /**
     * @throws SPException
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
                  ->with(...self::withConsecutive(...$execArguments));

        $this->pdo->method('quote')->willReturnArgument(0);

        $this->mysqlService->createDBUser($user, $pass);
    }

    /**
     * @throws SPException
     */
    public function testCreateDBUserIsSuccessfulWithHostingMode(): void
    {
        $this->installData->setHostingMode(true);

        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $this->pdo->expects(self::exactly(0))->method('exec');

        $this->mysqlService->createDBUser($user, $pass);
    }

    /**
     * @throws SPException
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

        $this->mysqlService->createDBUser($user, $pass);
    }

    /**
     * @throws SPException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = $this->createMock(PDO::class);

        $this->dbStorage = $this->createMock(DbStorageInterface::class);
        $this->dbStorage->method('getConnection')->willReturn($this->pdo);
        $this->dbStorage->method('getConnectionSimple')->willReturn($this->pdo);

        $this->databaseFile = $this->createMock(DatabaseFileInterface::class);

        $this->installData = $this->getInstallData();
        $this->configData = $this->config->getConfigData();
        $this->databaseUtil = $this->createMock(DatabaseUtil::class);
        $this->mysqlService = new MysqlService(
            $this->dbStorage,
            $this->installData,
            $this->databaseFile,
            $this->databaseUtil
        );
    }

    /**
     * @return InstallData
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
