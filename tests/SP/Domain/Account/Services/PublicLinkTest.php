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

namespace SP\Tests\Domain\Account\Services;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkRepository;
use SP\Domain\Account\Services\PublicLink;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Ports\RequestService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\PublicLinkDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class PublicLinkServiceTest
 *
 */
#[Group('unitary')]
class PublicLinkTest extends UnitaryTestCase
{

    private PublicLinkRepository|MockObject $publicLinkRepository;
    private PublicLink                      $publicLink;
    private CryptInterface|MockObject       $crypt;
    private MockObject|AccountService       $accountService;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testAddLinkView()
    {
        $publicLink = new PublicLinkModel(['hash' => self::$faker->sha1]);

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('addLinkView')
            ->with(
                new Callback(function (PublicLinkModel $publicLinkData) {
                    $useInfo = unserialize($publicLinkData->getUseInfo(), ['allowed_classes' => false]);

                    return is_array($useInfo) && count($useInfo) === 1;
                })
            );

        $this->publicLink->addLinkView($publicLink);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testAddLinkViewWithoutHash()
    {
        $publicLinkData = new PublicLinkModel();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Public link hash not set');

        $this->publicLink->addLinkView($publicLinkData);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testAddLinkViewWithUseInfo()
    {
        $properties = [
            'useInfo' => serialize(
                [
                    [
                        'who' => self::$faker->ipv4,
                        'time' => time(),
                        'hash' => self::$faker->sha1,
                        'agent' => self::$faker->userAgent,
                        'https' => self::$faker->boolean,
                    ],
                ]
            ),
            'hash' => self::$faker->sha1
        ];
        $publicLink = new PublicLinkModel($properties);

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('addLinkView')
            ->with(
                new Callback(function (PublicLinkModel $publicLinkData) {
                    $useInfo = unserialize($publicLinkData->getUseInfo(), ['allowed_classes' => false]);

                    return is_array($useInfo) && count($useInfo) === 2;
                })
            );

        $this->publicLink->addLinkView($publicLink);
    }

    /**
     * @throws SPException
     */
    public function testGetByHash()
    {
        $hash = self::$faker->sha1;
        $publicLink = PublicLinkDataGenerator::factory()->buildPublicLink();
        $result = new QueryResult([$publicLink]);

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getByHash')
            ->with($hash)
            ->willReturn($result);

        $actual = $this->publicLink->getByHash($hash);

        $this->assertEquals($publicLink, $actual);
    }

    /**
     * @throws SPException
     */
    public function testGetByHashNotFound()
    {
        $hash = self::$faker->sha1;

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getByHash')
            ->with($hash)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Link not found');

        $this->publicLink->getByHash($hash);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 9));

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(10);

        $actual = $this->publicLink->deleteByIdBatch($ids);

        $this->assertEquals(count($ids), $actual);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function testDeleteByIdBatchWithCountMismatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 9));

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(1);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while removing the links');

        $this->publicLink->deleteByIdBatch($ids);
    }

    public function testCreateLinkHash()
    {
        $this->assertNotEmpty(PublicLink::createLinkHash());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $publicLinkList = PublicLinkDataGenerator::factory()->buildPublicLinkList();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('update')
            ->with($publicLinkList);

        $this->publicLink->update($publicLinkList);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id);

        $this->publicLink->delete($id);
    }

    public function testSearch()
    {
        $itemSearchData = new ItemSearchDto(self::$faker->colorName);

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearchData);

        $this->publicLink->search($itemSearchData);
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetHashForItem()
    {
        $itemId = self::$faker->randomNumber();
        $publicLinkData = PublicLinkDataGenerator::factory()->buildPublicLink();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getHashForItem')
            ->with($itemId)
            ->willReturn(new QueryResult([new Simple($publicLinkData->toArray())]));

        $actual = $this->publicLink->getHashForItem($itemId);

        $this->assertEquals($publicLinkData->toArray(), $actual->toArray(includeOuter: true));
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function testGetHashForItemNotFound()
    {
        $itemId = self::$faker->randomNumber();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getHashForItem')
            ->with($itemId)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Link not found');

        $this->publicLink->getHashForItem($itemId);
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws SPException
     */
    public function testRefresh()
    {
        $id = self::$faker->randomNumber();
        $publicLinkData = PublicLinkDataGenerator::factory()->buildPublicLink();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([new Simple($publicLinkData->toArray())]));

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('refresh')
            ->with(
                new Callback(function (PublicLinkModel $actual) use ($publicLinkData) {
                    $filter = ['hash', 'dateExpire', 'maxCountViews', 'data'];

                    return $actual->toArray(null, $filter) === $publicLinkData->toArray(null, $filter)
                           && !empty($actual->getHash())
                           && !empty($actual->getDateExpire())
                           && !empty($actual->getMaxCountViews())
                           && !empty($actual->getData());
                })
            )
            ->willReturn(true);

        $passData = ['pass' => self::$faker->password, 'key' => self::$faker->sha1];

        $this->accountService
            ->expects(self::once())
            ->method('getDataForLink')
            ->with($publicLinkData->getItemId())
            ->willReturn(new Simple($passData));

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->with(
                $passData['pass'],
                $passData['key'],
                $this->context->getTrasientKey(Context::MASTER_PASSWORD_KEY)
            )
            ->willReturn(self::$faker->password);

        $actual = $this->publicLink->refresh($id);

        $this->assertTrue($actual);
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws SPException
     */
    public function testRefreshNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Link not found');

        $this->publicLink->refresh($id);
    }

    /**
     * @return void
     * @throws EnvironmentIsBrokenException
     */
    public function testGetPublicLinkKey()
    {
        $hash = self::$faker->sha1;

        $actual = $this->publicLink->getPublicLinkKey($hash);

        $this->assertEquals($hash, $actual->getHash());
        $this->assertNotEmpty($actual->getKey());
    }

    /**
     * @return void
     * @throws EnvironmentIsBrokenException
     */
    public function testGetPublicLinkKeyWithoutHash()
    {
        $actual = $this->publicLink->getPublicLinkKey();

        $this->assertNotEmpty($actual->getHash());
        $this->assertNotEmpty($actual->getKey());
    }

    /**
     * @return void
     * @throws SPException
     * @throws NoSuchItemException
     */
    public function testGetById()
    {
        $itemId = self::$faker->randomNumber();
        $builPublicLinkList = PublicLinkDataGenerator::factory()->buildPublicLinkList();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getById')
            ->with($itemId)
            ->willReturn(new QueryResult([new Simple($builPublicLinkList->toArray())]));

        $actual = $this->publicLink->getById($itemId);

        $this->assertEquals($builPublicLinkList->toArray(null, ['clientName']), $actual->toArray());
    }

    /**
     * @return void
     * @throws SPException
     * @throws NoSuchItemException
     */
    public function testGetByIdNotFound()
    {
        $itemId = self::$faker->randomNumber();

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('getById')
            ->with($itemId)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Link not found');

        $this->publicLink->getById($itemId);
    }

    /**
     * @throws SPException
     */
    public function testGetAllBasic()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Not implemented');

        $this->publicLink->getAll();
    }

    public function testGetUseInfo()
    {
        $hash = self::$faker->sha1;
        $who = self::$faker->ipv4;
        $userAgent = self::$faker->userAgent;

        $request = $this->createMock(RequestService::class);

        $request->expects(self::once())
                ->method('getClientAddress')
                ->with(true)
                ->willReturn($who);

        $request->expects(self::once())
                ->method('getHeader')
                ->with('User-Agent')
                ->willReturn($userAgent);

        $request->expects(self::once())
                ->method('isHttps')
                ->willReturn(true);

        $actual = PublicLink::getUseInfo($hash, $request);

        $this->assertArrayHasKey('who', $actual);
        $this->assertArrayHasKey('time', $actual);
        $this->assertArrayHasKey('hash', $actual);
        $this->assertArrayHasKey('agent', $actual);
        $this->assertArrayHasKey('https', $actual);
        $this->assertEquals($who, $actual['who']);
        $this->assertEquals($hash, $actual['hash']);
        $this->assertEquals($userAgent, $actual['agent']);
        $this->assertTrue($actual['https']);
    }

    /**
     * @return void
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $publicLinkData = PublicLinkDataGenerator::factory()->buildPublicLink();
        $result = new QueryResult(null, 0, self::$faker->randomNumber());

        $this->publicLinkRepository
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(function (PublicLinkModel $actual) use ($publicLinkData) {
                    $filter = ['hash', 'dateExpire', 'maxCountViews', 'data'];

                    return $actual->toArray(null, $filter) === $publicLinkData->toArray(null, $filter)
                           && !empty($actual->getHash())
                           && !empty($actual->getDateExpire())
                           && !empty($actual->getMaxCountViews())
                           && !empty($actual->getData());
                })
            )
            ->willReturn($result);

        $passData = ['pass' => self::$faker->password, 'key' => self::$faker->sha1];

        $this->accountService
            ->expects(self::once())
            ->method('getDataForLink')
            ->with($publicLinkData->getItemId())
            ->willReturn(new Simple($passData));

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->with(
                $passData['pass'],
                $passData['key'],
                $this->context->getTrasientKey(Context::MASTER_PASSWORD_KEY)
            )
            ->willReturn(self::$faker->password);

        $actual = $this->publicLink->create($publicLinkData);

        $this->assertEquals($result->getLastId(), $actual);
    }

    public function testCalcDateExpire()
    {
        $expireDate = time() + $this->config->getConfigData()->getPublinksMaxTime();

        $this->assertEqualsWithDelta($expireDate, PublicLink::calcDateExpire($this->config), 2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicLinkRepository = $this->createMock(PublicLinkRepository::class);
        $request = $this->createMock(RequestService::class);
        $request->method('getClientAddress')
                ->willReturn(self::$faker->ipv4);
        $request->method('getHeader')
                ->willReturn(self::$faker->userAgent);
        $request->method('isHttps')
                ->willReturn(self::$faker->boolean);

        $this->accountService = $this->createMock(AccountService::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, self::$faker->password);

        $this->publicLink =
            new PublicLink(
                $this->application,
                $this->publicLinkRepository,
                $request,
                $this->accountService,
                $this->crypt
            );
    }
}
