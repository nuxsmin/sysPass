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

namespace SPT\Infrastructure\Database;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\DatabaseException;
use SP\Infrastructure\Database\DbStorageDriver;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\Database\PDOWrapper;
use SPT\UnitaryTestCase;

/**
 * Class MysqlHandlerTest
 */
#[Group('unitary')]
class MysqlHandlerTest extends UnitaryTestCase
{

    public static function getConnectionDataProvider(): array
    {
        return [
            ['socket', 'localhost', null, 'a_user', 'a_password'],
            ['socket', 'localhost', 'test', null, 'a_password'],
            ['socket', 'localhost', 'test', 'a_user', null],
            [null, null, 'test', 'a_user', 'a_password'],
        ];
    }

    public static function getConnectionSimpleDataProvider(): array
    {
        return [
            ['socket', 'localhost', null, 'a_password'],
            ['socket', 'localhost', 'a_user', null],
            [null, null, 'a_user', 'a_password'],
        ];
    }

    /**
     * @throws Exception
     * @throws DatabaseException
     */
    #[DataProvider('getConnectionDataProvider')]
    public function testGetConnectionWithException(
        ?string $socket,
        ?string $host,
        ?string $name,
        ?string $user,
        ?string $pass
    ) {
        $connectionData = $this->createStub(DatabaseConnectionData::class);
        $pdoWrapper = $this->createMock(PDOWrapper::class);

        $connectionData->method('getDbSocket')
                       ->willReturn($socket);

        $connectionData->method('getDbHost')
                       ->willReturn($host);

        $connectionData->method('getDbName')
                       ->willReturn($name);

        $connectionData->method('getDbUser')
                       ->willReturn($user);

        $connectionData->method('getDbPass')
                       ->willReturn($pass);

        $pdoWrapper->expects($this->never())
                   ->method('build');

        $mysqlHandler = new MysqlHandler($connectionData, $pdoWrapper);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to connect to DB');

        $mysqlHandler->getConnection();
    }

    /**
     * @throws Exception
     * @throws DatabaseException
     */
    public function testGetConnection()
    {
        $pdoOptions = [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];

        $connectionData = $this->createStub(DatabaseConnectionData::class);
        $pdoWrapper = $this->createMock(PDOWrapper::class);
        $pdo = $this->createMock(PDO::class);

        $connectionData->method('getDbHost')
                       ->willReturn('localhost');

        $connectionData->method('getDbPort')
                       ->willReturn(3306);

        $connectionData->method('getDbName')
                       ->willReturn('test');

        $connectionData->method('getDbUser')
                       ->willReturn('a_user');

        $connectionData->method('getDbPass')
                       ->willReturn('a_password');

        $dsn = 'mysql:charset=utf8;host=localhost;port=3306;dbname=test';

        $pdoWrapper->expects($this->once())
                   ->method('build')
                   ->with($dsn, $connectionData, $pdoOptions)
                   ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->willReturn('5.1.17');

        $pdo->expects($this->once())
            ->method('setAttribute')
            ->with(PDO::ATTR_EMULATE_PREPARES, false);

        $mysqlHandler = new MysqlHandler($connectionData, $pdoWrapper);

        $out = $mysqlHandler->getConnection();

        $this->assertEquals($pdo, $out);
    }

    /**
     * @throws DatabaseException
     * @throws Exception
     */
    public function testGetConnectionSimple()
    {
        $pdoOptions = [
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $connectionData = $this->createStub(DatabaseConnectionData::class);
        $pdoWrapper = $this->createMock(PDOWrapper::class);
        $pdo = $this->createMock(PDO::class);

        $connectionData->method('getDbHost')
                       ->willReturn('localhost');

        $connectionData->method('getDbPort')
                       ->willReturn(3306);

        $connectionData->method('getDbUser')
                       ->willReturn('a_user');

        $connectionData->method('getDbPass')
                       ->willReturn('a_password');

        $dsn = 'mysql:charset=utf8;host=localhost;port=3306';

        $pdoWrapper->expects($this->once())
                   ->method('build')
                   ->with($dsn, $connectionData, $pdoOptions)
                   ->willReturn($pdo);

        $mysqlHandler = new MysqlHandler($connectionData, $pdoWrapper);

        $out = $mysqlHandler->getConnectionSimple();

        $this->assertEquals($pdo, $out);
    }

    /**
     * @throws DatabaseException
     * @throws Exception
     */
    #[DataProvider('getConnectionSimpleDataProvider')]
    public function testGetConnectionSimpleWithException(
        ?string $socket,
        ?string $host,
        ?string $user,
        ?string $pass
    ) {
        $connectionData = $this->createStub(DatabaseConnectionData::class);
        $pdoWrapper = $this->createMock(PDOWrapper::class);

        $connectionData->method('getDbSocket')
                       ->willReturn($socket);

        $connectionData->method('getDbHost')
                       ->willReturn($host);

        $connectionData->method('getDbUser')
                       ->willReturn($user);

        $connectionData->method('getDbPass')
                       ->willReturn($pass);

        $pdoWrapper->expects($this->never())
                   ->method('build');

        $mysqlHandler = new MysqlHandler($connectionData, $pdoWrapper);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to connect to DB');

        $mysqlHandler->getConnectionSimple();
    }

    /**
     * @throws Exception
     */
    public function testGetDriver()
    {
        $connectionData = $this->createStub(DatabaseConnectionData::class);
        $pdoWrapper = $this->createMock(PDOWrapper::class);
        $mysqlHandler = new MysqlHandler($connectionData, $pdoWrapper);

        $this->assertEquals(DbStorageDriver::mysql, $mysqlHandler->getDriver());
    }
}
