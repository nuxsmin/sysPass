<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Presenters\Helpers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\User\Dtos\UserDto;
use SP\Modules\Web\Mvc\View\TemplateInterface;
use SP\Modules\Web\Presenters\Helpers\LayoutHelper;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
final class LayoutHelperTest extends UnitaryTestCase
{
    #[Test]
    #[RunInSeparateProcess]
    public function initBodyAssignsApprovedBrandAssetsAndRemovesLegacyPngVars(): void
    {
        $assigned = [];

        $template = $this->createMock(TemplateInterface::class);
        $template->method('assign')
                 ->willReturnCallback(static function (string $name, mixed $value) use (&$assigned): void {
                     $assigned[$name] = $value;
                 });

        $configData = $this->config->getConfigData();
        $configData->setApplicationUrl('https://vault.example.test/syspass/');
        $configData->setPasswordSalt('0123456789abcdef0123456789abcdef');

        $context = $this->createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getUserData')->willReturn(
            UserDto::fromModel(UserDataGenerator::factory()->buildUserData())
        );

        $uriContext = $this->createStub(UriContextInterface::class);
        $uriContext->method('getWebUri')->willReturn('https://vault.example.test/syspass');
        $uriContext->method('getSubUri')->willReturn('/index.php');

        $theme = $this->createStub(ThemeInterface::class);
        $theme->method('getInfo')->willReturn([]);
        $theme->method('getPath')->willReturn('app/modules/web/themes/material-blue');

        $cryptPKI = $this->createStub(CryptPKIHandler::class);
        $cryptPKI->method('getPublicKey')->willReturn('PUBLIC-KEY');

        $request = $this->createStub(RequestService::class);
        $request->method('isHttps')->willReturn(true);

        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRouteFor')->willReturn('account/index');

        $helper = new LayoutHelper(
            $this->config,
            $context,
            $this->eventDispatcherStub,
            $template,
            $request,
            $theme,
            $cryptPKI,
            $uriContext,
            $acl
        );

        $helper->initBody();

        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/syspass-horizontal-reversed.svg',
            $assigned['logo_horizontal_reversed'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/syspass-horizontal-primary.svg',
            $assigned['logo_horizontal_primary'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/syspass-stacked-primary.svg',
            $assigned['logo_stacked_primary'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/syspass-symbol-reversed.svg',
            $assigned['logo_symbol_reversed'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/syspass-symbol-primary.svg',
            $assigned['logo_symbol_primary'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/favicon.svg',
            $assigned['favicon_svg'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/favicon.ico',
            $assigned['favicon_ico'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/images/brand/icon-180.png',
            $assigned['apple_touch_icon'] ?? null
        );
        self::assertSame(
            'https://vault.example.test/syspass/public/manifest.webmanifest',
            $assigned['manifest_url'] ?? null
        );

        self::assertArrayNotHasKey('logo_icon', $assigned);
        self::assertArrayNotHasKey('logo_no_bg_color', $assigned);
        self::assertArrayNotHasKey('logo_no_bg', $assigned);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function initBodyAssignsFallbackLangWhenStaticPropertiesAreEmpty(): void
    {
        \SP\Core\Language::$globalLang = '';
        \SP\Core\Language::$userLang = '';

        $assigned = [];

        $template = $this->createMock(TemplateInterface::class);
        $template->method('assign')
                 ->willReturnCallback(static function (string $name, mixed $value) use (&$assigned): void {
                     $assigned[$name] = $value;
                 });

        $configData = $this->config->getConfigData();
        $configData->setApplicationUrl('https://vault.example.test/syspass/');
        $configData->setPasswordSalt('0123456789abcdef0123456789abcdef');

        $context = $this->createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getLocale')->willReturn('es_ES');
        $context->method('getUserData')->willReturn(
            UserDto::fromModel(UserDataGenerator::factory()->buildUserData())
        );

        $uriContext = $this->createStub(UriContextInterface::class);
        $uriContext->method('getWebUri')->willReturn('https://vault.example.test/syspass');
        $uriContext->method('getSubUri')->willReturn('/index.php');

        $theme = $this->createStub(ThemeInterface::class);
        $theme->method('getInfo')->willReturn([]);
        $theme->method('getPath')->willReturn('app/modules/web/themes/material-blue');

        $cryptPKI = $this->createStub(CryptPKIHandler::class);
        $cryptPKI->method('getPublicKey')->willReturn('PUBLIC-KEY');

        $request = $this->createStub(RequestService::class);
        $request->method('isHttps')->willReturn(true);

        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRouteFor')->willReturn('account/index');

        $helper = new LayoutHelper(
            $this->config,
            $context,
            $this->eventDispatcherStub,
            $template,
            $request,
            $theme,
            $cryptPKI,
            $uriContext,
            $acl
        );

        $helper->initBody();

        self::assertSame('es', $assigned['lang'] ?? null);
    }
}
