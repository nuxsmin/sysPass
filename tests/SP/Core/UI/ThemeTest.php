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

use Directory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Core\UI\Theme;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class ThemeTest
 *
 */
#[Group('unitary')]
class ThemeTest extends UnitaryTestCase
{

    private ThemeContextInterface|MockObject $themeContext;
    private ThemeIconsInterface|MockObject   $themeIcons;
    private Theme                                               $theme;

    public function testGetIcons()
    {
        $expected = spl_object_id($this->themeIcons);
        $current = spl_object_id($this->theme->getIcons());

        $this->assertNotEquals($expected, $current);
    }

    /**
     * @throws Exception
     */
    public function testGetThemeNameUnathenticated()
    {
        $context = $this->createMock(SessionContext::class);
        $context->expects(self::once())
                ->method('isLoggedIn')
                ->willReturn(false);

        $context->expects(self::never())
                ->method('getUserData');

        $configData = $this->config->getConfigData();
        $configData->setSiteTheme(self::$faker->colorName);

        $current = Theme::getThemeName($this->config->getConfigData(), $context);

        $this->assertEquals($configData->getSiteTheme(), $current);
    }

    /**
     * @throws Exception
     */
    public function testGetThemeNameAuthenticated()
    {
        $context = $this->createMock(SessionContext::class);
        $context->expects(self::once())
                ->method('isLoggedIn')
                ->willReturn(true);

        $userDataDto = new UserDataDto(UserDataGenerator::factory()->buildUserData());

        $context->expects(self::once())
                ->method('getUserData')
            ->willReturn($userDataDto);

        $configData = $this->config->getConfigData();
        $configData->setSiteTheme(self::$faker->colorName);

        $current = Theme::getThemeName($this->config->getConfigData(), $context);

        $this->assertEquals($userDataDto->getPreferences()->getTheme(), $current);
    }

    public function testGetViewsPath()
    {
        $path = self::$faker->filePath();
        $this->themeContext
            ->expects(self::once())
            ->method('getViewsPath')
            ->willReturn($path);

        $this->assertEquals($path, $this->theme->getViewsPath());
    }

    public function testGetInfo()
    {
        $this->themeContext
            ->expects(self::once())
            ->method('getFullPath')
            ->willReturn(self::$faker->filePath());

        $this->assertEquals([], $this->theme->getInfo());
    }

    public function testGetUri()
    {
        $url = self::$faker->url();
        $this->themeContext
            ->expects(self::once())
            ->method('getUri')
            ->willReturn($url);

        $this->assertEquals($url, $this->theme->getUri());
    }

    /**
     * @throws Exception
     */
    public function testGetAvailable()
    {
        $dirname = self::$faker->filePath();

        $dir = $this->createMock(Directory::class);
        $dir->expects(self::exactly(2))
            ->method('read')
            ->willReturn('/tmp', false);

        $this->themeContext
            ->expects(self::once())
            ->method('getViewsDirectory')
            ->willReturn($dir);

        $this->themeContext
            ->expects(self::once())
            ->method('getViewsPath')
            ->willReturn('/tmp/themes');

        $this->theme->getAvailable();
    }

    public function testGetPath()
    {
        $path = self::$faker->filePath();
        $this->themeContext
            ->expects(self::once())
            ->method('getPath')
            ->willReturn($path);

        $this->assertEquals($path, $this->theme->getPath());
    }

    /**
     * @throws ContextException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->themeContext = $this->createMock(ThemeContextInterface::class);
        $this->themeIcons = $this->createMock(ThemeIconsInterface::class);

        $this->theme = new Theme($this->themeContext, $this->themeIcons);
    }
}
