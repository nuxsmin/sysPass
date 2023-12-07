<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Language;
use SP\DataModel\UserPreferencesData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Http\RequestInterface;
use SP\Domain\User\Services\UserLoginResponse;
use SP\Tests\UnitaryTestCase;

/**
 * Class LanguageTest
 *
 * @group unitary
 */
class LanguageTest extends UnitaryTestCase
{

    private ConfigDataInterface|MockObject $configData;
    private RequestInterface|MockObject    $request;
    private Language                       $language;

    public function testSetLocales()
    {
        $locale = self::$faker->locale;

        Language::setLocales($locale);

        $this->assertEquals($locale . '.utf8', Language::$localeStatus);
        $this->assertEquals($locale . '.utf8', getenv('LANG'));
        $this->assertEquals($locale . '.utf8', getenv('LANGUAGE'));
    }

    public function testSetLanguage()
    {
        $locale = self::$faker->locale;

        $this->context->setLocale($locale);

        $this->language->setLanguage();

        $this->assertEquals($locale . '.utf8', Language::$localeStatus);
        $this->assertEquals($locale . '.utf8', getenv('LANG'));
        $this->assertEquals($locale . '.utf8', getenv('LANGUAGE'));
    }

    public function testSetLanguageForceWithUserLanguage()
    {
        $locale = self::$faker->locale;

        $this->context->setLocale($locale);
        $this->configData
            ->expects(self::once())
            ->method('getSiteLang')
            ->willReturn(self::$faker->locale);

        $userData = $this->context->getUserData();

        $userData->setPreferences(new UserPreferencesData(['lang' => $locale]));

        $this->language->setLanguage(true);

        $this->assertEquals($locale . '.utf8', Language::$localeStatus);
        $this->assertEquals($locale . '.utf8', getenv('LANG'));
        $this->assertEquals($locale . '.utf8', getenv('LANGUAGE'));
        $this->assertEquals($locale, $this->context->getLocale());
    }

    public function testSetLanguageForceWithAppLanguage()
    {
        $locale = self::$faker->locale;
        $appLocale = self::$faker->locale;

        $this->context->setLocale($locale);
        $this->context->setUserData(new UserLoginResponse());

        $this->configData
            ->expects(self::once())
            ->method('getSiteLang')
            ->willReturn($appLocale);

        $this->language->setLanguage(true);

        $this->assertEquals($appLocale . '.utf8', Language::$localeStatus);
        $this->assertEquals($appLocale . '.utf8', getenv('LANG'));
        $this->assertEquals($appLocale . '.utf8', getenv('LANGUAGE'));
        $this->assertEquals($appLocale, $this->context->getLocale());
    }

    public function testSetLanguageForceWithBrowserLanguage()
    {
        $locale = self::$faker->locale;
        $browserLocale = self::$faker->locale;

        $this->context->setLocale($locale);
        $this->context->setUserData(new UserLoginResponse());

        $this->configData
            ->expects(self::once())
            ->method('getSiteLang')
            ->willReturn(null);

        $this->request
            ->expects(self::once())
            ->method('getHeader')
            ->with('Accept-Language')
            ->willReturn($browserLocale);

        $this->language->setLanguage(true);

        $this->assertEquals($browserLocale . '.utf8', Language::$localeStatus);
        $this->assertEquals($browserLocale . '.utf8', getenv('LANG'));
        $this->assertEquals($browserLocale . '.utf8', getenv('LANGUAGE'));
        $this->assertEquals($browserLocale, $this->context->getLocale());
    }

    public function testGetAvailableLanguages()
    {
        $out = Language::getAvailableLanguages();

        $this->assertCount(14, $out);
    }

    public function testSetAppLocales()
    {
        $locale = self::$faker->locale;
        $appLocale = self::$faker->locale;

        $this->context->setLocale($locale);

        $this->configData
            ->expects(self::exactly(2))
            ->method('getSiteLang')
            ->willReturn($appLocale);

        $this->language->setAppLocales();

        $this->assertEquals($appLocale . '.utf8', Language::$localeStatus);
        $this->assertEquals($appLocale . '.utf8', getenv('LANG'));
        $this->assertEquals($appLocale . '.utf8', getenv('LANGUAGE'));
    }

    public function testUnsetAppLocales()
    {
        $locale = self::$faker->locale;

        $this->context->setLocale($locale);

        $this->language->unsetAppLocales();

        $this->assertEquals($locale . '.utf8', Language::$localeStatus);
        $this->assertEquals($locale . '.utf8', getenv('LANG'));
        $this->assertEquals($locale . '.utf8', getenv('LANGUAGE'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configData = $this->createMock(ConfigDataInterface::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->language = new Language($this->context, $this->configData, $this->request);
    }

}
