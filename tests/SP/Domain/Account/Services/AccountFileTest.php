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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Models\File;
use SP\Domain\Account\Models\FileExtData;
use SP\Domain\Account\Ports\AccountFileRepository;
use SP\Domain\Account\Services\AccountFile;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidImageException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Image\Ports\ImageService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\FileDataGenerator;
use SP\Tests\Generators\ItemSearchDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountFileServiceTest
 *
 */
#[Group('unitary')]
class AccountFileTest extends UnitaryTestCase
{

    private MockObject|AccountFileRepository $accountFileRepository;
    private ImageService|MockObject $imageUtil;
    private AccountFile                      $accountFile;

    /**
     * @throws InvalidImageException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testCreate(): void
    {
        $fileData = File::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData())
                        ->mutate(
                                ['type' => self::$faker->mimeType()]
                            );

        $this->imageUtil
            ->expects(self::never())
            ->method('createThumbnail');

        $this->accountFileRepository
            ->expects(self::once())
            ->method('create')
            ->with($fileData);

        $this->accountFile->create($fileData);
    }

    /**
     * @throws InvalidImageException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testCreateWithThumbnail(): void
    {
        $fileData = File::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData());

        $this->accountFileRepository
            ->expects(self::once())
            ->method('create')
            ->with($fileData);

        $this->imageUtil
            ->expects(self::once())
            ->method('createThumbnail')
            ->with($fileData->getContent())
            ->willReturn(self::$faker->paragraph());

        $this->accountFile->create($fileData);
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

        $out = $this->accountFile->getById($fileData->getId());

        $this->assertEquals($fileData, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch(): void
    {
        $ids = array_map(static fn() => self::$faker->randomNumber(), range(0, 9));

        $this->accountFileRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(count($ids));

        $out = $this->accountFile->deleteByIdBatch($ids);

        $this->assertEquals(count($ids), $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
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

        $this->accountFile->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete(): void
    {
        $id = self::$faker->randomNumber();

        $this->accountFileRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);

        $this->accountFile->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
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

        $this->accountFile->delete($id);
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

        $out = $this->accountFile->search($itemSearchData);

        $this->assertEquals($files, $out->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByAccountId(): void
    {
        $fileData = File::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData());

        $queryResult = new QueryResult([$fileData]);

        $this->accountFileRepository
            ->expects(self::once())
            ->method('getByAccountId')
            ->with($fileData->getId())
            ->willReturn($queryResult);

        $out = $this->accountFile->getByAccountId($fileData->getId());

        $this->assertEquals([$fileData], $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountFileRepository = $this->createMock(AccountFileRepository::class);
        $this->imageUtil = $this->createMock(ImageService::class);

        $this->accountFile =
            new AccountFile($this->application, $this->accountFileRepository, $this->imageUtil);
    }
}
