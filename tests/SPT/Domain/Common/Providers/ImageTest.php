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

namespace SPT\Domain\Common\Providers;

use GdImage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SP\Domain\Core\Exceptions\InvalidImageException;
use SP\Domain\Core\Exceptions\SPException;
use SPT\Stubs\PhpExtensionCheckerStub;

/**
 * Class ImageTest
 */
#[Group('unitary')]
class ImageTest extends TestCase
{
    private \SP\Domain\Common\Providers\Image $imageUtil;

    /**
     * @throws InvalidImageException
     * @throws SPException
     */
    public function testCreateThumbnail()
    {
        $image = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
                 . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
                 . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
                 . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';

        $out = $this->imageUtil->createThumbnail(base64_decode($image));

        $this->assertTrue(imagecreatefromstring(base64_decode($out)) instanceof GdImage);
    }

    /**
     * @throws InvalidImageException
     * @throws SPException
     */
    public function testCreateThumbnailWithException()
    {
        $this->expectException(InvalidImageException::class);
        $this->expectExceptionMessage('Invalid image');

        $this->imageUtil->createThumbnail('');
    }

    public function testConvertText()
    {
        $out = $this->imageUtil->convertText('test');

        $this->assertTrue(imagecreatefromstring(base64_decode($out)) instanceof GdImage);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $phpExtensionCheckerService = $this->createMock(PhpExtensionCheckerStub::class);
        $phpExtensionCheckerService->expects($this->once())
                                   ->method('checkCurl')
                                   ->with(true);

        $this->imageUtil = new \SP\Domain\Common\Providers\Image($phpExtensionCheckerService);
    }
}
