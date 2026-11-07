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

namespace SP\Tests\Infrastructure\Client\Repositories;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Client\Models\Client as ClientModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\DuplicatedItemException;
use SP\Infrastructure\Client\Repositories\Client;
use SP\Tests\DatabaseRepositoryTestCase;
use SP\Tests\Generators\ClientGenerator;

/**
 * DB-backed tests for the Client repository queries.
 */
#[Group('integration')]
final class ClientDbTest extends DatabaseRepositoryTestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function createPersistsTheClientAndItsNormalisedHash(): void
    {
        $this->client->create($this->clientModel('Acme Corp.', null, 'Acme services'));

        $client = $this->client->getByName('Acme Corp.')->getData(ClientModel::class);

        self::assertSame('Acme Corp.', $client->getName());
        self::assertSame('Acme services', $client->getDescription());
        self::assertSame(sha1('acmecorp'), $client->getHash());
        self::assertSame(0, (int)$client->getIsGlobal());
    }

    #[Test]
    public function createRejectsADuplicatedName(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->client->create($this->clientModel('Google'));
    }

    #[Test]
    public function updateChangesTheClientAndRecomputesTheHash(): void
    {
        $affected = $this->client->update($this->clientModel('AWS', 4, 'Amazon Web Services', 1));

        self::assertSame(1, $affected);

        $client = $this->client->getById(4)->getData(ClientModel::class);

        self::assertSame('AWS', $client->getName());
        self::assertSame('Amazon Web Services', $client->getDescription());
        self::assertSame(1, $client->getIsGlobal());
        self::assertSame(sha1('aws'), $client->getHash());
    }

    #[Test]
    public function updateRejectsANameUsedByAnotherClient(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->client->update($this->clientModel('Google', 4));
    }

    #[Test]
    public function getByIdReturnsTheClient(): void
    {
        self::assertSame('Google', $this->client->getById(1)->getData(ClientModel::class)->getName());
        self::assertSame(0, $this->client->getById(999)->getNumRows());
    }

    #[Test]
    public function getByNameMatchesTheNameOrTheHashIgnoringCase(): void
    {
        self::assertSame(1, $this->client->getByName('Google')->getData(ClientModel::class)->getId());
        self::assertSame(1, $this->client->getByName('google')->getData(ClientModel::class)->getId());
        self::assertSame(0, $this->client->getByName('missing')->getNumRows());
    }

    #[Test]
    public function getAllReturnsEveryClient(): void
    {
        $names = array_map(
            static fn(ClientModel $client) => $client->getName(),
            $this->client->getAll()->getDataAsArray(ClientModel::class)
        );

        sort($names);

        self::assertSame(['Amazon', 'Apple', 'Google', 'Microsoft'], $names);
    }

    #[Test]
    public function getAllForFilterReturnsUnusedAndGlobalClients(): void
    {
        $ids = array_map(
            static fn($client) => (int)$client->id,
            $this->client->getAllForFilter($this->buildAccountFilter())->getDataAsArray()
        );

        sort($ids);

        self::assertSame([3, 4], $ids);
    }

    #[Test]
    public function deleteByIdBatchRemovesOnlyTheSelectedClients(): void
    {
        $this->client->create($this->clientModel('Temporary A'));
        $this->client->create($this->clientModel('Temporary B'));

        $ids = [
            $this->client->getByName('Temporary A')->getData(ClientModel::class)->getId(),
            $this->client->getByName('Temporary B')->getData(ClientModel::class)->getId(),
        ];

        $this->client->deleteByIdBatch($ids);

        self::assertSame(4, $this->client->getAll()->getNumRows());
        self::assertSame(0, $this->client->deleteByIdBatch([])->getAffectedNumRows());
    }

    #[Test]
    public function deleteRemovesASingleClient(): void
    {
        $this->client->delete(4);

        self::assertSame(0, $this->client->getById(4)->getNumRows());
        self::assertSame(3, $this->client->getAll()->getNumRows());
    }

    #[Test]
    public function deleteRejectsAClientInUse(): void
    {
        $this->expectException(ConstraintException::class);

        $this->client->delete(1);
    }

    #[Test]
    public function searchFiltersByNameOrDescriptionAndPaginates(): void
    {
        $result = $this->client->search(new ItemSearchDto('Apple'));

        self::assertSame(1, $result->getNumRows());
        self::assertSame(2, $result->getDataAsArray(ClientModel::class)[0]->getId());

        $all = $this->client->search(new ItemSearchDto());

        self::assertSame(
            ['Amazon', 'Apple', 'Google', 'Microsoft'],
            array_map(
                static fn(ClientModel $client) => $client->getName(),
                $all->getDataAsArray(ClientModel::class)
            )
        );
        self::assertSame(4, $all->getTotalNumRows());

        $page = $this->client->search(new ItemSearchDto(null, 2, 1));

        self::assertSame(
            ['Google'],
            array_map(
                static fn(ClientModel $client) => $client->getName(),
                $page->getDataAsArray(ClientModel::class)
            )
        );
    }

    private function clientModel(
        string  $name,
        ?int    $id = null,
        ?string $description = null,
        int     $isGlobal = 0
    ): ClientModel {
        return ClientGenerator::factory()->buildClient()->mutate([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'isGlobal' => $isGlobal,
            'hash' => null,
        ]);
    }

    private function buildAccountFilter(): AccountFilterBuilder
    {
        return new class($this->queryFactory) implements AccountFilterBuilder {
            public function __construct(private readonly QueryFactory $queryFactory)
            {
            }

            public function buildFilterHistory(
                bool $useGlobalSearch = false,
                ?SelectInterface $query = null
            ): SelectInterface {
                return $this->buildFilter($useGlobalSearch, $query);
            }

            public function buildFilter(
                bool $useGlobalSearch = false,
                ?SelectInterface $query = null
            ): SelectInterface {
                return ($query ?? $this->queryFactory->newSelect())->from('Account');
            }
        };
    }
}
