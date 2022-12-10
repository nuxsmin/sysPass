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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\Domain\Account\Ports\AccountFileRepositoryInterface;
use SP\Domain\Account\Services\AccountFileService;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\FileDataGenerator;
use SP\Tests\Generators\ItemSearchDataGenerator;
use SP\Tests\UnitaryTestCase;
use SP\Util\ImageUtilInterface;

/**
 * Class AccountFileServiceTest
 *
 * @group unitary
 */
class AccountFileServiceTest extends UnitaryTestCase
{

    private MockObject|AccountFileRepositoryInterface $accountFileRepository;
    private ImageUtilInterface|MockObject             $imageUtil;
    private AccountFileService                        $accountFileService;

    /**
     * @throws \SP\Core\Exceptions\InvalidImageException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testCreate(): void
    {
        $fileData = FileData::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData());

        $this->accountFileRepository
            ->expects(self::once())
            ->method('create')
            ->with($fileData);

        $this->accountFileService->create($fileData);
    }

    /**
     * @throws \SP\Core\Exceptions\InvalidImageException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testCreateWithThumbnail(): void
    {
        $fileData = FileData::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData());

        $this->accountFileRepository
            ->expects(self::once())
            ->method('create')
            ->with($fileData);
        $this->imageUtil
            ->expects(self::once())
            ->method('createThumbnail');

        $this->accountFileService->create($fileData);
    }

    public function testGetById(): void
    {
        $fileData = FileExtData::buildFromSimpleModel(FileDataGenerator::factory()->buildFileExtData());

        $queryResult = new QueryResult([$fileData]);

        $this->accountFileRepository
            ->expects(self::once())
            ->method('getById')
            ->with($fileData->getId())
            ->willReturn($queryResult);

        $out = $this->accountFileService->getById($fileData->getId());

        $this->assertEquals($fileData, $out);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testDeleteByIdBatch(): void
    {
        $ids = array_map(static fn() => self::$faker->randomNumber(), range(0, 9));

        $this->accountFileRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(count($ids));

        $out = $this->accountFileService->deleteByIdBatch($ids);

        $this->assertEquals(count($ids), $out);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function testDeleteByIdBatchWithMissingUpdates(): void
    {
        $ids = array_map(static fn() => self::$faker->randomNumber(), range(0, 9));

        $this->accountFileRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(5);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the files');

        $this->accountFileService->deleteByIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete(): void
    {
        $id = self::$faker->randomNumber();

        $this->accountFileRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);

        $this->accountFileService->delete($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteWithMissingFile(): void
    {
        $id = self::$faker->randomNumber();

        $this->accountFileRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(false);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('File not found');

        $this->accountFileService->delete($id);
    }

    public function testSearch(): void
    {
        $files = array_map(
            static fn() => FileExtData::buildFromSimpleModel(FileDataGenerator::factory()->buildFileExtData()),
            range(0, 4)
        );
        $itemSearchData = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->accountFileRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn(new QueryResult($files));

        $out = $this->accountFileService->search($itemSearchData);

        $this->assertEquals($files, $out->getDataAsArray());
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByAccountId(): void
    {
        $fileData = FileData::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData());

        $queryResult = new QueryResult([$fileData]);

        $this->accountFileRepository
            ->expects(self::once())
            ->method('getByAccountId')
            ->with($fileData->getId())
            ->willReturn($queryResult);

        $out = $this->accountFileService->getByAccountId($fileData->getId());

        $this->assertEquals([$fileData], $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountFileRepository = $this->createMock(AccountFileRepositoryInterface::class);
        $this->imageUtil = $this->createMock(ImageUtilInterface::class);

        $this->accountFileService =
            new AccountFileService($this->application, $this->accountFileRepository, $this->imageUtil);
    }
}
