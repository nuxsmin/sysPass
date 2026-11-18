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

namespace SP\Tests\Modules\Web\Controllers\Bootstrap;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Class BootstrapTest
 */
#[Group('integration')]
class BootstrapTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('getEnvironmentOutputChecker')]
    public function getEnvironment()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'bootstrap/getEnvironment'])
        );

        IntegrationTestCase::runApp($container);
    }

    public function getEnvironmentOutputChecker(string $output): void
    {
        $json = json_decode($output);

        $properties = [
            'lang',
            'locale',
            'app_root',
            'max_file_size',
            'check_updates',
            'check_notices',
            'check_notifications',
            'timezone',
            'debug',
            'cookies_enabled',
            'plugins',
            'loggedin',
            'authbasic_autologin',
            'pki_key',
            'pki_max_size',
            'import_allowed_mime',
            'files_allowed_mime',
            'session_timeout',
            'csrf',
        ];

        self::assertCount(count($properties), (array)$json->data);

        foreach ($properties as $property) {
            self::assertObjectHasProperty($property, $json->data);
        }
    }

    #[Test]
    public function getEnvironmentAnonymousVsIncompleteVsAdminVsDemoAndFallbacks(): void
    {
        // 1. Anonymous visitor with PKI failure + PluginManager exception
        $anonCtx = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $anonCtx->method('isLoggedIn')->willReturn(false);
        $anonCtx->method('getAuthCompleted')->willReturn(false);
        $anonCtx->method('getUserData')->willReturn(new \SP\Domain\User\Dtos\UserDto());
        $anonCtx->method('getCSRF')->willReturn('anon-csrf-ignored');

        $failingPki = $this->createStub(\SP\Domain\Core\Crypt\CryptPKIHandler::class);
        $failingPki->method('getPublicKey')->willThrowException(new \SP\Domain\Core\Exceptions\FileException('pki-missing'));

        $failingPluginMgr = $this->createStub(\SP\Domain\Plugin\Ports\PluginManagerService::class);
        $failingPluginMgr->method('getEnabled')->willThrowException(new \RuntimeException('plugin-db-down'));

        $anonData = $this->dispatchEnvironment([
            \SP\Domain\Core\Context\Context::class => $anonCtx,
            \SP\Domain\Core\Crypt\CryptPKIHandler::class => $failingPki,
            \SP\Domain\Plugin\Ports\PluginManagerService::class => $failingPluginMgr,
        ]);
        self::assertFalse($anonData['loggedin']);
        self::assertFalse($anonData['check_updates']);
        self::assertFalse($anonData['check_notices']);
        self::assertFalse($anonData['check_notifications']);
        self::assertSame([], $anonData['plugins']);
        self::assertSame('', $anonData['pki_key']);
        self::assertSame('anon-csrf-ignored', $anonData['csrf']);
        self::assertFalse($anonData['debug']);

        // 2. Incomplete session (isLoggedIn=true, getAuthCompleted=false) exposes check_notifications and csrf, while suppressing update/notice flags
        $baseUser = $this->getUserDataDto();
        $incompleteUser = $baseUser->mutate([
            'isAdminApp' => true,
            'preferences' => $baseUser->preferences->mutate(['checkNotifications' => true]),
        ]);
        $incompleteCtx = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $incompleteCtx->method('isLoggedIn')->willReturn(true);
        $incompleteCtx->method('getAuthCompleted')->willReturn(false);
        $incompleteCtx->method('getUserData')->willReturn($incompleteUser);
        $incompleteCtx->method('getUserProfile')->willReturn($this->getUserProfile());
        $incompleteCtx->method('getCSRF')->willReturn('incomplete-csrf');

        $incompleteData = $this->dispatchEnvironment(
            [\SP\Domain\Core\Context\Context::class => $incompleteCtx],
            ['isCheckUpdates' => true, 'isCheckNotices' => true]
        );
        self::assertTrue($incompleteData['loggedin']);
        self::assertFalse($incompleteData['check_updates']);
        self::assertFalse($incompleteData['check_notices']);
        self::assertTrue($incompleteData['check_notifications']);
        self::assertSame('incomplete-csrf', $incompleteData['csrf']);

        // 3. Completed normal non-admin user: updates/notices false unless demo mode enabled
        $normalUser = $this->getUserDataDto()->mutate(['isAdminApp' => false]);
        $normalCtx = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $normalCtx->method('isLoggedIn')->willReturn(true);
        $normalCtx->method('getAuthCompleted')->willReturn(true);
        $normalCtx->method('getUserData')->willReturn($normalUser);
        $normalCtx->method('getUserProfile')->willReturn($this->getUserProfile());
        $normalCtx->method('getCSRF')->willReturn('user-csrf');

        $normalData = $this->dispatchEnvironment(
            [\SP\Domain\Core\Context\Context::class => $normalCtx],
            ['isCheckUpdates' => true, 'isCheckNotices' => true, 'isDemoEnabled' => false, 'isDebug' => false]
        );
        self::assertTrue($normalData['loggedin']);
        self::assertFalse($normalData['check_updates']);
        self::assertFalse($normalData['check_notices']);
        self::assertFalse($normalData['debug']);

        // 4. Non-admin in demo mode + debug mode enabled
        $demoData = $this->dispatchEnvironment(
            [\SP\Domain\Core\Context\Context::class => $normalCtx],
            ['isCheckUpdates' => true, 'isCheckNotices' => true, 'isDemoEnabled' => true, 'isDebug' => true]
        );
        self::assertTrue($demoData['check_updates']);
        self::assertTrue($demoData['check_notices']);
        self::assertTrue($demoData['debug']);

        // 5. Completed admin user with checkUpdates/checkNotices
        $adminUser = $this->getUserDataDto()->mutate(['isAdminApp' => true]);
        $adminCtx = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $adminCtx->method('isLoggedIn')->willReturn(true);
        $adminCtx->method('getAuthCompleted')->willReturn(true);
        $adminCtx->method('getUserData')->willReturn($adminUser);
        $adminCtx->method('getUserProfile')->willReturn($this->getUserProfile());
        $adminCtx->method('getCSRF')->willReturn('admin-csrf');

        $adminData = $this->dispatchEnvironment(
            [\SP\Domain\Core\Context\Context::class => $adminCtx],
            ['isCheckUpdates' => true, 'isCheckNotices' => true, 'isDemoEnabled' => false]
        );
        self::assertTrue($adminData['check_updates']);
        self::assertTrue($adminData['check_notices']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<string, mixed> $configOverrides
     * @return array<string, mixed>
     */
    private function dispatchEnvironment(array $overrides, array $configOverrides = []): array
    {
        if ($configOverrides !== []) {
            $configData = self::createConfiguredStub(
                \SP\Domain\Config\Ports\ConfigDataInterface::class,
                array_merge($this->getConfigData(), $configOverrides)
            );
            $configService = $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class);
            $configService->method('getConfigData')->willReturn($configData);
            $overrides[\SP\Domain\Config\Ports\ConfigFileService::class] = $configService;
            $overrides[\SP\Domain\Config\Ports\ConfigDataInterface::class] = $configData;
        }

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'bootstrap/getEnvironment']),
            $overrides
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $decoded['status']);

        return $decoded['data'];
    }
}
