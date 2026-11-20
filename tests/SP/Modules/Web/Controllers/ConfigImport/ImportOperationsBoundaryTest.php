<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Modules\Web\Controllers\ConfigImport;

use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportService;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\IntegrationTestCase;

use function DI\factory;

/**
 * HTTP dispatch characterization tests for ConfigImport\ImportController:
 * - Anonymous & incomplete-session rejection with no ImportService invocation
 * - Real Acl denial (HTTP 403 JSON error) when configImport permission is absent
 * - Demo mode warning short-circuit
 * - Missing `inFile` upload error text bug ("File successfully uploaded")
 * - Zero-imported-accounts warning response vs positive counter OK response
 */
#[Group('integration')]
final class ImportOperationsBoundaryTest extends IntegrationTestCase
{
    private string $tempDir;
    private bool $sessionLoggedIn = true;
    private bool $sessionAuthCompleted = true;
    private ?UserDto $sessionUser = null;
    private ?ProfileData $sessionProfile = null;
    private array $configOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-web-import-ops-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);

        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(1, false);
        $this->sessionProfile = (new ProfileData())->setConfigImport(true);
        $this->configOverrides = [];
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function anonymousAndIncompleteSessionsAreBlockedWithoutCallingImportService(): void
    {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $importService = $this->createMock(ImportService::class);
            $importService->expects(self::never())->method('doImport');

            $result = $this->dispatchImport([], [], [ImportService::class => $importService]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    public function unprivilegedUserReceives403AclDenial(): void
    {
        $this->sessionUser = $this->makeUserDto(12, false);
        $this->sessionProfile = (new ProfileData())->setConfigGeneral(true); // lacks configImport

        $importService = $this->createMock(ImportService::class);
        $importService->expects(self::never())->method('doImport');

        $result = $this->dispatchImport([], [], [ImportService::class => $importService]);

        self::assertSame(403, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame("You don't have permission to access this page", $json['description']);
    }

    #[Test]
    public function demoModeReturnsWarningBeforeFileExtraction(): void
    {
        $this->configOverrides = ['isDemoEnabled' => true];

        $importService = $this->createMock(ImportService::class);
        $importService->expects(self::never())->method('doImport');

        $result = $this->dispatchImport([], [], [ImportService::class => $importService]);

        self::assertSame(200, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('WARNING', $json['status']);
        self::assertSame('Ey, this is a DEMO!!', $json['description']);
    }

    /**
     * Characterization: ConfigImport\ImportController::getFileFromRequest() throws FileException::error()
     * with the misleading description "File successfully uploaded" when no `inFile` upload exists.
     */
    #[Test]
    public function missingUploadedFileReturnsMisleadingSuccessTextInErrorResponse(): void
    {
        $importService = $this->createMock(ImportService::class);
        $importService->expects(self::never())->method('doImport');

        $result = $this->dispatchImport(['csvDelimiter' => ';'], [], [ImportService::class => $importService]);

        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('File successfully uploaded', $json['description']);
    }

    #[Test]
    public function zeroImportedAccountsReturnsWarningWhilePositiveCountReturnsOk(): void
    {
        $tmpCsv = $this->tempDir . DIRECTORY_SEPARATOR . 'import.csv';
        file_put_contents($tmpCsv, "acc;cli;cat;https://x;u;p;n\n");

        $files = [
            'inFile' => [
                'name' => 'import.csv',
                'tmp_name' => $tmpCsv,
                'size' => filesize($tmpCsv),
                'type' => 'text/csv',
            ],
        ];

        $itemsImportZero = $this->createStub(ItemsImportService::class);
        $itemsImportZero->method('getCounter')->willReturn(0);

        $importServiceZero = $this->createMock(ImportService::class);
        $importServiceZero->expects(self::once())
            ->method('doImport')
            ->with(self::callback(static fn(ImportParamsDto $dto): bool => $dto->getDelimiter() === '|'))
            ->willReturn($itemsImportZero);

        $zeroResult = $this->dispatchImport(
            ['csvDelimiter' => '|'],
            $files,
            [ImportService::class => $importServiceZero]
        );
        $zeroJson = json_decode($zeroResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('WARNING', $zeroJson['status']);
        self::assertSame('No accounts were imported', $zeroJson['description']);
        self::assertSame('Please check out the event log for more details', $zeroJson['data']);

        $itemsImportTwo = $this->createStub(ItemsImportService::class);
        $itemsImportTwo->method('getCounter')->willReturn(2);

        $importServiceTwo = $this->createMock(ImportService::class);
        $importServiceTwo->expects(self::once())->method('doImport')->willReturn($itemsImportTwo);

        $okResult = $this->dispatchImport(
            ['csvDelimiter' => ';'],
            $files,
            [ImportService::class => $importServiceTwo]
        );
        $okJson = json_decode($okResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $okJson['status']);
        self::assertSame('Import finished', $okJson['description']);
    }

    /**
     * @return array{code: int, body: string}
     */
    private function dispatchImport(array $post, array $files = [], array $overrides = []): array
    {
        $request = IntegrationTestCase::buildRequest(
            'post',
            'index.php',
            ['r' => 'configImport/import'],
            $post,
            $files
        );

        $user = $this->sessionUser ?? $this->makeUserDto(1, false);
        $profile = $this->sessionProfile ?? new ProfileData();
        $loggedIn = $this->sessionLoggedIn;
        $authCompleted = $this->sessionAuthCompleted;

        $sessionStub = $this->createStub(SessionContext::class);
        $sessionStub->method('isLoggedIn')->willReturn($loggedIn);
        $sessionStub->method('getAuthCompleted')->willReturn($authCompleted);
        $sessionStub->method('getUserData')->willReturn($user);
        $sessionStub->method('getUserProfile')->willReturn($profile);
        $sessionStub->method('getLocale')->willReturn('en_US');
        $sessionStub->method('getCSRF')->willReturn(null);

        $baseOverrides = [
            Context::class => $sessionStub,
            SessionContext::class => $sessionStub,
            AclInterface::class => factory(static fn(ContainerInterface $c): Acl => new Acl(
                $c->get(Context::class),
                $c->get(EventDispatcherInterface::class),
                $c->get(ActionsInterface::class)
            )),
        ];

        $container = $this->buildContainer($request, array_merge($baseOverrides, $overrides));

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
            $output = (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        /** @var Response $response */
        $response = $container->get(Response::class);

        return [
            'code' => $response->status()->getCode(),
            'body' => $output,
        ];
    }

    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), $this->configOverrides);
    }

    private function makeUserDto(int $id, bool $isAdminApp): UserDto
    {
        return new UserDto(
            id: $id,
            login: 'user_' . $id,
            userGroupId: 1,
            userGroupName: 'Admins',
            userProfileId: 1,
            isAdminApp: $isAdminApp,
            lastUpdateMPass: 1000
        );
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
