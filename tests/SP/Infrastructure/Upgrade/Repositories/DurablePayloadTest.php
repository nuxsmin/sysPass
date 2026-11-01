<?php

declare(strict_types=1);
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

namespace SP\Tests\Infrastructure\Upgrade\Repositories;

use LogicException;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Database\Enums\DbStorageDriver;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Database\Ports\QueryDataInterface;
use SP\Domain\Upgrade\Enums\DurablePayloadType;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\QueryFactoryAdapter;
use SP\Infrastructure\Upgrade\Repositories\DurablePayload;

/**
 * Class DurablePayloadTest
 */
#[Group('unitary')]
final class DurablePayloadTest extends TestCase
{
    #[Test]
    public function findsAndMapsRows(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects(self::once())
            ->method('doFetchWithOptions')
            ->with(
                self::callback(
                    static function (QueryDataInterface $queryData): bool {
                        self::assertSame(
                            'SELECT id, vault AS payload FROM AuthToken WHERE id > :lastId ORDER BY id LIMIT :batchSize',
                            $queryData->getQuery()->getStatement()
                        );
                        self::assertSame(
                            ['lastId' => 0, 'batchSize' => 500],
                            $queryData->getQuery()->getBindValues()
                        );

                        return true;
                    }
                ),
                [],
                PDO::FETCH_ASSOC
            )
            ->willReturn(
                [
                    ['id' => 1, 'payload' => 'payload'],
                    ['id' => 2, 'payload' => null],
                ]
            );

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $rows = iterator_to_array($repository->findByType(DurablePayloadType::AuthTokenVault));

        self::assertCount(2, $rows);
        self::assertSame('1', $rows[0]->id);
        self::assertSame('payload', $rows[0]->payload);
        self::assertSame('2', $rows[1]->id);
        self::assertNull($rows[1]->payload);
    }

    #[Test]
    public function fetchesAndMapsRowsThroughTheRealDatabasePath(): void
    {
        $connection = new PDO('sqlite::memory:');
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->exec('CREATE TABLE AuthToken (id INTEGER PRIMARY KEY, vault TEXT)');
        $connection->exec(
            "INSERT INTO AuthToken (id, vault) VALUES (1, 'serialized-payload'), (2, NULL)"
        );

        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($connection);
        $storage->method('getDriver')->willReturn(DbStorageDriver::mysql);

        $database = new Database($storage, $this->createStub(EventDispatcherInterface::class));
        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        $rows = iterator_to_array($repository->findByType(DurablePayloadType::AuthTokenVault));

        self::assertCount(2, $rows);
        self::assertSame('1', $rows[0]->id);
        self::assertSame('serialized-payload', $rows[0]->payload);
        self::assertSame('2', $rows[1]->id);
        self::assertNull($rows[1]->payload);
    }

    #[Test]
    public function paginatesThroughTheRealDatabasePath(): void
    {
        $connection = new PDO('sqlite::memory:');
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->exec('CREATE TABLE AuthToken (id INTEGER PRIMARY KEY, vault TEXT)');

        $insert = $connection->prepare('INSERT INTO AuthToken (id, vault) VALUES (?, ?)');
        $connection->beginTransaction();

        for ($id = 1; $id <= 501; $id++) {
            $insert->execute([$id, 'payload-' . $id]);
        }

        $connection->commit();

        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($connection);
        $storage->method('getDriver')->willReturn(DbStorageDriver::mysql);

        $database = new Database($storage, $this->createStub(EventDispatcherInterface::class));
        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        $rows = iterator_to_array($repository->findByType(DurablePayloadType::AuthTokenVault));

        self::assertCount(501, $rows);
        self::assertSame('1', $rows[0]->id);
        self::assertSame('payload-1', $rows[0]->payload);
        self::assertSame('501', $rows[500]->id);
        self::assertSame('payload-501', $rows[500]->payload);
    }

    #[Test]
    public function fetchesPluginDataThroughTheRealDatabasePath(): void
    {
        $connection = new PDO('sqlite::memory:');
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->exec('CREATE TABLE PluginData (name TEXT, itemId INTEGER, data TEXT, PRIMARY KEY (name, itemId))');
        $connection->exec(
            "INSERT INTO PluginData (name, itemId, data) VALUES "
            . "('Authenticator', 1, 'ciphertext'), ('Authenticator', 2, ''), ('Other', 1, 'value')"
        );

        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($connection);
        $storage->method('getDriver')->willReturn(DbStorageDriver::mysql);

        $database = new Database($storage, $this->createStub(EventDispatcherInterface::class));
        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        $rows = iterator_to_array($repository->findByType(DurablePayloadType::PluginData));

        self::assertCount(3, $rows);
        self::assertSame('Authenticator:1', $rows[0]->id);
        self::assertSame('1', $rows[0]->payload);
        self::assertSame('Authenticator:2', $rows[1]->id);
        self::assertNull($rows[1]->payload);
        self::assertSame('Other:1', $rows[2]->id);
        self::assertSame('1', $rows[2]->payload);
    }

    #[Test]
    public function mapsItemPresetSubtype(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects(self::once())
            ->method('doFetchWithOptions')
            ->with(
                self::callback(
                    static function (QueryDataInterface $queryData): bool {
                        self::assertSame(
                            'SELECT id, data AS payload, type AS subtype FROM ItemPreset WHERE id > :lastId ORDER BY id LIMIT :batchSize',
                            $queryData->getQuery()->getStatement()
                        );

                        return true;
                    }
                ),
                [],
                PDO::FETCH_ASSOC
            )
            ->willReturn(
                [
                    ['id' => 5, 'payload' => 'payload', 'subtype' => 'account.password'],
                ]
            );

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $rows = iterator_to_array($repository->findByType(DurablePayloadType::ItemPreset));

        self::assertSame('account.password', $rows[0]->subtype);
    }

    #[Test]
    public function mapsPluginDataCompositeIdentifier(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects(self::once())
            ->method('doFetchWithOptions')
            ->with(
                self::callback(
                    static function (QueryDataInterface $queryData): bool {
                        self::assertSame(
                            'SELECT name, itemId, CASE WHEN data IS NULL OR data = \'\' THEN NULL ELSE \'1\' END AS payload FROM PluginData ORDER BY name, itemId LIMIT :batchSize',
                            $queryData->getQuery()->getStatement()
                        );
                        self::assertSame(
                            ['batchSize' => 500],
                            $queryData->getQuery()->getBindValues()
                        );

                        return true;
                    }
                ),
                [],
                PDO::FETCH_ASSOC
            )
            ->willReturn(
                [
                    ['name' => 'Authenticator', 'itemId' => 2, 'payload' => '1'],
                ]
            );

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $rows = iterator_to_array($repository->findByType(DurablePayloadType::PluginData));

        self::assertSame('Authenticator:2', $rows[0]->id);
        self::assertSame('1', $rows[0]->payload);
    }

    #[Test]
    public function paginatesRowsByKeyset(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $calls = 0;

        $database
            ->expects(self::exactly(2))
            ->method('doFetchWithOptions')
            ->willReturnCallback(
                static function (QueryDataInterface $queryData) use (&$calls): array {
                    $calls++;
                    $bindValues = $queryData->getQuery()->getBindValues();

                    if ($calls === 1) {
                        self::assertSame(0, $bindValues['lastId']);

                        return array_map(
                            static fn(int $id): array => ['id' => $id, 'payload' => 'payload-' . $id],
                            range(1, 500)
                        );
                    }

                    self::assertSame(500, $bindValues['lastId']);

                    return [['id' => 501, 'payload' => 'payload-501']];
                }
            );

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $rows = iterator_to_array($repository->findByType(DurablePayloadType::AuthTokenVault));

        self::assertCount(501, $rows);
        self::assertSame('501', $rows[500]->id);
    }

    #[Test]
    public function paginatesPluginDataByCompositeKey(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $calls = 0;

        $database
            ->expects(self::exactly(2))
            ->method('doFetchWithOptions')
            ->willReturnCallback(
                static function (QueryDataInterface $queryData) use (&$calls): array {
                    $calls++;
                    $bindValues = $queryData->getQuery()->getBindValues();

                    if ($calls === 1) {
                        self::assertArrayNotHasKey('lastName', $bindValues);

                        return array_map(
                            static fn(int $itemId): array => [
                                'name' => 'Authenticator',
                                'itemId' => $itemId,
                                'payload' => '1'
                            ],
                            range(1, 500)
                        );
                    }

                    self::assertSame('Authenticator', $bindValues['lastName']);
                    self::assertSame(500, $bindValues['lastItemId']);

                    return [['name' => 'Authenticator', 'itemId' => 501, 'payload' => null]];
                }
            );

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $rows = iterator_to_array($repository->findByType(DurablePayloadType::PluginData));

        self::assertCount(501, $rows);
        self::assertSame('Authenticator:501', $rows[500]->id);
        self::assertNull($rows[500]->payload);
    }

    #[Test]
    public function updatesRowAndReturnsAffectedRows(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects(self::once())
            ->method('runQuery')
            ->with(
                self::callback(
                    static function (QueryDataInterface $queryData): bool {
                        self::assertSame(
                            'UPDATE AuthToken SET vault = :payload WHERE id = :id LIMIT 1',
                            $queryData->getQuery()->getStatement()
                        );
                        self::assertSame(
                            ['payload' => 'payload', 'id' => '1'],
                            $queryData->getQuery()->getBindValues()
                        );

                        return true;
                    }
                )
            )
            ->willReturn(new QueryResult(null, 1));

        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        self::assertSame(1, $repository->update(DurablePayloadType::AuthTokenVault, '1', 'payload'));
    }

    #[Test]
    public function rejectsPluginDataUpdate(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::never())->method('runQuery');

        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        $this->expectException(LogicException::class);

        $repository->update(DurablePayloadType::PluginData, 'Authenticator:1', 'payload');
    }

    #[Test]
    public function commitsTransaction(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::once())->method('beginTransaction')->willReturn(true);
        $database->expects(self::once())->method('endTransaction')->willReturn(true);
        $database->expects(self::never())->method('rollbackTransaction');

        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        self::assertSame(1, $repository->transactional(static fn(): int => 1));
    }

    #[Test]
    public function failsWhenTransactionCannotStart(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::once())->method('beginTransaction')->willReturn(false);
        $database->expects(self::never())->method('endTransaction');
        $database->expects(self::never())->method('rollbackTransaction');

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $called = false;
        $thrown = null;

        try {
            $repository->transactional(static function () use (&$called): void {
                $called = true;
            });
        } catch (ServiceException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(ServiceException::class, $thrown);
        self::assertFalse($called);
    }

    #[Test]
    public function rollsBackWhenCommitFails(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::once())->method('beginTransaction')->willReturn(true);
        $database->expects(self::once())->method('endTransaction')->willReturn(false);
        $database->expects(self::once())->method('rollbackTransaction')->willReturn(true);

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $thrown = null;

        try {
            $repository->transactional(static fn(): int => 1);
        } catch (ServiceException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(ServiceException::class, $thrown);
    }

    #[Test]
    public function rollsBackTransaction(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::once())->method('beginTransaction')->willReturn(true);
        $database->expects(self::never())->method('endTransaction');
        $database->expects(self::once())->method('rollbackTransaction')->willReturn(true);

        $repository = new DurablePayload($database, new QueryFactoryAdapter());

        $this->expectException(RuntimeException::class);

        $repository->transactional(static function (): void {
            throw new RuntimeException('failure');
        });
    }
}
