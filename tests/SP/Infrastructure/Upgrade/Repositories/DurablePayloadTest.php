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
                            'SELECT id, vault AS payload FROM AuthToken',
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
                            'SELECT id, data AS payload, type AS subtype FROM ItemPreset',
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
                            'SELECT name, itemId, data AS payload FROM PluginData',
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
                    ['name' => 'Authenticator', 'itemId' => 2, 'payload' => 'ciphertext'],
                ]
            );

        $repository = new DurablePayload($database, new QueryFactoryAdapter());
        $rows = iterator_to_array($repository->findByType(DurablePayloadType::PluginData));

        self::assertSame('Authenticator:2', $rows[0]->id);
        self::assertSame('ciphertext', $rows[0]->payload);
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
