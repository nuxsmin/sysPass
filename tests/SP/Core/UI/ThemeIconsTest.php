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

namespace SP\Tests\Core\UI;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use SP\Modules\Web\UI\ThemeIcons;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\File\Ports\PhpFileLoaderInterface;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Modules\Web\Html\Assets\FontIcon;
use SP\Domain\Core\Exceptions\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class ThemeIconsTest
 *
 */
#[Group('unitary')]
class ThemeIconsTest extends UnitaryTestCase
{
    #[Test]
    public function testGetIconByNameWithUnknownIcon()
    {
        $themeIcons = new ThemeIcons();
        $out = $themeIcons->getIconByName('test');

        $this->assertInstanceOf(FontIcon::class, $out);
        $this->assertEquals('test', $out->getIcon());
        $this->assertEquals('sp-color-text--accent', $out->getClass());
    }

    #[Test]
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

    #[Test]
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
    #[Test]
    public function testLoadIconsWithCache()
    {
        $context = $this->createMock(Context::class);
        $fileCache = $this->createMock(FileCacheService::class);
        $fileLoader = $this->createMock(PhpFileLoaderInterface::class);
        $themeContext = $this->createMock(ThemeContextInterface::class);
        $themeContext->expects($this->never())
                     ->method('getFullPath');

        $context->expects(self::once())
                ->method('getAppStatus')
                ->willReturn('test');

        $fileCache->expects(self::once())
                  ->method('isExpired')
            ->willReturn(false);

        $fileCache->expects(self::once())
                  ->method('load')
                  ->willReturn(new ThemeIcons());

        ThemeIcons::loadIcons($context, $fileCache, $themeContext, $fileLoader);
    }

    #[Test]
    public function testMaterialBlueIconsUseSemanticContrastClasses(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__, 4));
        }

        /** @var ThemeIcons $icons */
        $icons = require dirname(__DIR__, 4) . '/app/modules/web/themes/material-blue/inc/Icons.php';

        self::assertInstanceOf(ThemeIcons::class, $icons);
        self::assertSame('sp-color-text--accent', $icons->add()->getClass());
        self::assertSame('sp-color--accent', $icons->add()->getClassButton());
        self::assertSame('sp-color-text--warning', $icons->warning()->getClass());
        self::assertSame('sp-color-text--error', $icons->delete()->getClass());
        self::assertSame('sp-color-text--success', $icons->save()->getClass());
        self::assertStringNotContainsString('mdl-color', $icons->appAdmin()->getClass());
        self::assertStringNotContainsString('mdl-color', $icons->clear()->getClass());
    }

}
