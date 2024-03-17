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

namespace SPT\Core\UI;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Html\Assets\FontIcon;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;

/**
 * Class ThemeIconsTest
 *
 */
#[Group('unitary')]
class ThemeIconsTest extends UnitaryTestCase
{
    public function testGetIconByNameWithUnknownIcon()
    {
        $themeIcons = new ThemeIcons();
        $out = $themeIcons->getIconByName('test');

        $this->assertInstanceOf(FontIcon::class, $out);
        $this->assertEquals('test', $out->getIcon());
        $this->assertEquals('mdl-color-text--indigo-A200', $out->getClass());
    }

    public function testGetIconByName()
    {
        $themeIcons = new ThemeIcons();
        $themeIcons->addIcon('test', new FontIcon('test', 'testClass', 'testTitle'));

        $out = $themeIcons->getIconByName('test');

        $this->assertInstanceOf(FontIcon::class, $out);
        $this->assertEquals('test', $out->getIcon());
        $this->assertEquals('testClass', $out->getClass());
        $this->assertEquals('testTitle', $out->getTitle());
    }

    public function testAddIcon()
    {
        $themeIcons = new ThemeIcons();
        $themeIcons->addIcon('test', new FontIcon('test', 'testClass', 'testTitle'));

        $out = $themeIcons->getIconByName('test');

        $this->assertInstanceOf(FontIcon::class, $out);
        $this->assertEquals('test', $out->getIcon());
        $this->assertEquals('testClass', $out->getClass());
        $this->assertEquals('testTitle', $out->getTitle());
    }

    /**
     * @throws InvalidClassException
     * @throws Exception
     * @throws FileException
     */
    public function testLoadIconsWithCache()
    {
        $context = $this->createMock(ContextInterface::class);
        $fileCache = $this->createMock(FileCacheService::class);
        $themeContext = $this->createMock(ThemeContextInterface::class);

        $context->expects(self::once())
                ->method('getAppStatus')
                ->willReturn('test');

        $fileCache->expects(self::once())
                  ->method('isExpired')
                  ->willReturn(true);

        $fileCache->expects(self::once())
                  ->method('load')
                  ->willReturn(new ThemeIcons());

        ThemeIcons::loadIcons($context, $fileCache, $themeContext);
    }
}
