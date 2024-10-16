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

namespace SP\Tests\Core;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Core\MimeTypes;
use SP\Domain\Core\File\MimeType;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Storage\Ports\XmlFileStorageService;
use SP\Domain\Storage\Ports\YamlFileStorageService;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class MimeTypesTest
 */
#[Group('unitary')]
class MimeTypesTest extends UnitaryTestCase
{

    private FileCacheService|MockObject      $fileCache;
    private XmlFileStorageService|MockObject $yamlFileStorage;

    /**
     * @throws FileException
     */
    public function testGetMimeTypes()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $mimeTypes = new MimeTypes($this->fileCache, $this->yamlFileStorage);
        $out = $mimeTypes->getMimeTypes();

        $this->assertCount(10, $out);

        foreach ($out as $mimeType) {
            $this->assertInstanceOf(MimeType::class, $mimeType);
        }
    }

    /**
     * @throws FileException
     */
    public function testResetWithCacheExpiredTime()
    {
        $this->fileCache
            ->expects(self::exactly(2))
            ->method('exists')
            ->willReturn(true);

        $this->fileCache
            ->expects(self::once())
            ->method('delete');

        $this->fileCache
            ->expects(self::exactly(2))
            ->method('isExpired')
            ->with(MimeTypes::CACHE_EXPIRE)
            ->willReturn(false, true);

        $this->checkBuildCache();

        $mimeTypes = new MimeTypes($this->fileCache, $this->yamlFileStorage);
        $mimeTypes->reset();
    }

    private function checkBuildCache(): void
    {
        $mimeTypes = array_map(
            static fn() => [
                'type' => self::$faker->mimeType(),
                'description' => self::$faker->text,
                'extension' => self::$faker->fileExtension()
            ],
            range(0, 9)
        );

        $this->yamlFileStorage
            ->expects(self::once())
            ->method('load')
            ->willReturn($mimeTypes);

        $this->fileCache
            ->expects(self::once())
            ->method('save');
    }

    /**
     * @throws FileException
     * @throws Exception
     */
    public function testResetWithCacheExpiredFileDate()
    {
        $this->fileCache
            ->expects(self::exactly(2))
            ->method('exists')
            ->willReturn(true);

        $this->fileCache
            ->expects(self::once())
            ->method('delete');

        $this->fileCache
            ->expects(self::exactly(2))
            ->method('isExpired')
            ->with(MimeTypes::CACHE_EXPIRE)
            ->willReturn(false, false);

        $this->yamlFileStorage
            ->expects(self::exactly(2))
            ->method('getFileTime')
            ->willReturn(0);

        $this->fileCache
            ->expects(self::exactly(2))
            ->method('isExpiredDate')
            ->with(0)
            ->willReturn(false, true);

        $this->checkBuildCache();

        $mimeTypes = new MimeTypes($this->fileCache, $this->yamlFileStorage);
        $mimeTypes->reset();
    }

    /**
     * @throws FileException
     */
    public function testResetWithReadXmlException()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('delete');

        $this->fileCache
            ->method('exists')
            ->willReturn(true);

        $this->fileCache
            ->expects(self::exactly(2))
            ->method('isExpired')
            ->with(MimeTypes::CACHE_EXPIRE)
            ->willReturn(false, true);

        $this->yamlFileStorage
            ->expects(self::once())
            ->method('load')
            ->willThrowException(new FileException('test'));

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('test');

        $mimeTypes = new MimeTypes($this->fileCache, $this->yamlFileStorage);
        $mimeTypes->reset();
    }

    /**
     * @throws FileException
     */
    public function testResetWithSaveCacheException()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('delete');

        $this->fileCache
            ->method('exists')
            ->willReturn(true);

        $this->fileCache
            ->expects(self::exactly(2))
            ->method('isExpired')
            ->with(MimeTypes::CACHE_EXPIRE)
            ->willReturn(false, true);

        $this->checkBuildCache();

        $this->fileCache
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new FileException('test'));

        $mimeTypes = new MimeTypes($this->fileCache, $this->yamlFileStorage);
        $mimeTypes->reset();
    }

    /**
     * @throws FileException
     */
    public function testReset()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('delete');

        $this->fileCache
            ->expects(self::exactly(2))
            ->method('exists')
            ->willReturn(true, false);

        $this->fileCache
            ->expects(self::any())
            ->method('isExpired')
            ->with(MimeTypes::CACHE_EXPIRE)
            ->willReturn(false);

        $this->fileCache
            ->expects(self::any())
            ->method('isExpiredDate')
            ->willReturn(false);

        $this->checkBuildCache();

        $mimeTypes = new MimeTypes($this->fileCache, $this->yamlFileStorage);
        $mimeTypes->reset();
    }

    /**
     * @throws Exception
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mimeTypes = array_map(
            static fn() => new MimeType(self::$faker->mimeType(), self::$faker->text, self::$faker->fileExtension()),
            range(0, 9)
        );
        $this->fileCache = $this->createMock(FileCacheService::class);
        $this->fileCache
            ->expects(self::any())
            ->method('load')
            ->willReturn($mimeTypes);
        $this->yamlFileStorage = $this->createMock(YamlFileStorageService::class);
    }
}
