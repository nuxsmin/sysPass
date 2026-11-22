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

namespace SP\Tests\Modules\Web\Controllers\ConfigLdap;

use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Bootstrap\Path;
use SP\Core\Messages\TextFormatter;
use SP\Domain\Auth\Dtos\LdapCheckResults;
use SP\Domain\Auth\Dtos\LdapParams;
use SP\Domain\Auth\Ports\LdapCheckService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Import\Dtos\LdapImportParamsDto;
use SP\Domain\Import\Dtos\LdapImportResultsDto;
use SP\Domain\Import\Ports\LdapImportService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\File\FileSystem;
use SP\Tests\IntegrationTestCase;
use TypeError;

use function DI\decorate;
use function DI\factory;

/**
 * Boundary & HTTP dispatch characterization tests for ConfigLdap controllers & LdapParams:
 * - Anonymous & incomplete-session rejection across configLdap/{save,check,checkImport,import}
 * - Real Acl denial (HTTP 403 JSON) when configGeneral permission is missing
 * - LdapParams::fromArray() required-param bug checking intersected key strings instead of values,
 *   while passing null `type` (e.g. omitted ldap_server_type) throws TypeError at LdapTypeEnum::tryFrom()
 * - ConfigLdap\SaveController ConfigSaveControllerBase demo guard, pre-mutating setLdapEnabled(true) before
 *   description check (dead "LDAP enabled" text), and disable path emitting "LDAP disabled"
 * - ConfigLdap\CheckController defaulting $useConfigParams=true + omitting template `results` assign
 * - ConfigLdap\CheckImportController `||` validation bug accepting single-field partials
 * - ConfigLdap\ImportController `&&` validation bypass + omitting group sync/error counts in JSON payload
 */
#[Group('integration')]
final class LdapOperationsBoundaryTest extends IntegrationTestCase
{
    private bool $sessionLoggedIn = true;
    private bool $sessionAuthCompleted = true;
    private ?UserDto $sessionUser = null;
    private ?ProfileData $sessionProfile = null;
    private array $configOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(1, false);
        $this->sessionProfile = (new ProfileData())->setConfigGeneral(true);
        $this->configOverrides = [
            'isLdapEnabled' => true,
            'getLdapServer' => 'ldap.saved.local',
            'getLdapType' => 1,
            'getLdapBindUser' => 'cn=saved,dc=example,dc=com',
            'getLdapBindPass' => 'saved-pass',
            'getLdapBase' => 'dc=example,dc=com',
            'getLdapGroup' => 'cn=users,dc=example,dc=com',
        ];
    }

    public static function ldapRoutesProvider(): array
    {
        return [
            'configLdap/save' => ['configLdap/save'],
            'configLdap/check' => ['configLdap/check'],
            'configLdap/checkImport' => ['configLdap/checkImport'],
            'configLdap/import' => ['configLdap/import'],
        ];
    }

    #[Test]
    #[DataProvider('ldapRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossLdapRoutes(string $route): void
    {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $ldapImport = $this->createMock(LdapImportService::class);
            $ldapImport->expects(self::never())->method('importUsers');

            $ldapCheck = $this->createMock(LdapCheckService::class);
            $ldapCheck->expects(self::never())->method('getObjects');

            $result = $this->dispatchWeb('post', $route, [], [
                LdapImportService::class => $ldapImport,
                LdapCheckService::class => $ldapCheck,
            ]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    #[DataProvider('ldapRoutesProvider')]
    public function unprivilegedUserReceives403AclDenial(string $route): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = (new ProfileData())->setConfigImport(true); // lacks configGeneral (CONFIG_LDAP)

        $result = $this->dispatchWeb('post', $route);

        self::assertSame(403, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame("You don't have permission to access this page", $json['description']);
    }

    /**
     * Characterization:
     * 1) LdapParams::fromArray() filters `array_intersect(REQUIRED_PARAMS, array_keys($params))` with `!empty($v)` —
     *    validating key name strings instead of values, so empty bindUser/bindPass pass required-param checks.
     * 2) Passing null `type` passes the REQUIRED_PARAMS check and then crashes at LdapTypeEnum::tryFrom(null) with TypeError.
     */
    #[Test]
    public function ldapParamsFromArrayChecksKeyNamesInsteadOfValuesAndCrashesOnNullType(): void
    {
        $params = LdapParams::fromArray([
            'server' => 'ldap.example.org',
            'type' => 1,
            'bindUser' => '',
            'bindPass' => '',
            'searchBase' => null,
            'group' => null,
            'tlsEnabled' => false,
            'filterUserObject' => null,
            'filterGroupObject' => null,
            'filterUserAttributes' => null,
            'filterGroupAttributes' => null,
        ]);

        self::assertSame('ldap.example.org', $params->getServer());
        self::assertSame('', $params->getBindDn());
        self::assertSame('', $params->getBindPass());

        try {
            LdapParams::fromArray([
                'server' => 'ldap.example.org',
                'type' => null,
                'bindUser' => 'cn=bind',
                'bindPass' => 'secret',
                'searchBase' => null,
                'group' => null,
                'tlsEnabled' => false,
                'filterUserObject' => null,
                'filterGroupObject' => null,
                'filterUserAttributes' => null,
                'filterGroupAttributes' => null,
            ]);
            self::fail('Expected TypeError from LdapTypeEnum::tryFrom(null)');
        } catch (TypeError $e) {
            self::assertStringContainsString('LdapTypeEnum::tryFrom', $e->getMessage());
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Wrong LDAP parameters');

        LdapParams::fromArray([
            'server' => '',
            'type' => 1,
            'bindUser' => '',
            'bindPass' => '',
            'searchBase' => null,
            'group' => null,
            'tlsEnabled' => false,
            'filterUserObject' => null,
            'filterGroupObject' => null,
            'filterUserAttributes' => null,
            'filterGroupAttributes' => null,
        ]);
    }

    /**
     * Characterization:
     * - ConfigLdap\SaveController enforces demo mode inside ConfigSaveControllerBase::saveConfig() (WARNING).
     * - Outside demo mode, line 67 calls `$configData->setLdapEnabled(true)` BEFORE line 88 checks
     *   `if ($configData->isLdapEnabled() === false)`, making the "LDAP enabled" event description dead code;
     *   disabling emits "LDAP disabled".
     */
    #[Test]
    public function saveControllerDemoGuardAndDeadEnabledDescription(): void
    {
        $this->configOverrides = [
            'isDemoEnabled' => true,
            'isLdapEnabled' => false,
        ];

        $demoRes = $this->dispatchWeb('post', 'configLdap/save', [
            'ldap_enabled' => 1,
            'ldap_server' => 'ldap.demo.local',
            'ldap_server_type' => 1,
            'ldap_binduser' => 'cn=admin',
            'ldap_bindpass' => 'pass',
        ]);
        self::assertSame(
            '{"status":"WARNING","description":"Ey, this is a DEMO!!","data":null}',
            $demoRes['body']
        );

        $realConfig = new \SP\Domain\Config\Adapters\ConfigData([
            \SP\Domain\Config\Ports\ConfigDataInterface::INSTALLED => true,
            \SP\Domain\Config\Ports\ConfigDataInterface::DB_NAME => 'syspass_test',
            \SP\Domain\Config\Ports\ConfigDataInterface::PASSWORD_SALT => 'salt',
            \SP\Domain\Config\Ports\ConfigDataInterface::AUTH_BASIC_ENABLED => false,
            \SP\Domain\Config\Ports\ConfigDataInterface::HTTPS_ENABLED => false,
            \SP\Domain\Config\Ports\ConfigDataInterface::MAINTENANCE => false,
            \SP\Domain\Config\Ports\ConfigDataInterface::DEMO_ENABLED => false,
            \SP\Domain\Config\Ports\ConfigDataInterface::LDAP_ENABLED => false,
            \SP\Domain\Config\Ports\ConfigDataInterface::LDAP_BIND_PASS => 'existing-secret',
        ]);
        $configFileService = $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($realConfig);
        $configFileService->method('save')->willReturnSelf();

        $dispatchedEvents = [];
        $formatter = new TextFormatter();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('notify')->willReturnCallback(
            static function (string $name, Event $event) use (&$dispatchedEvents, $formatter): void {
                $dispatchedEvents[] = [
                    'name' => $name,
                    'description' => $event->getEventMessage()?->getDescription($formatter, false),
                ];
            }
        );

        $enableRes = $this->dispatchWeb('post', 'configLdap/save', [
            'ldap_enabled' => 1,
            'ldap_server' => 'ldap.new.local',
            'ldap_server_type' => 1,
            'ldap_binduser' => 'cn=admin',
            'ldap_bindpass' => '***',
            'ldap_base' => 'dc=new,dc=local',
        ], [
            \SP\Domain\Config\Ports\ConfigFileService::class => $configFileService,
            EventDispatcherInterface::class => $dispatcher,
        ]);

        self::assertSame(
            '{"status":"OK","description":"Configuration updated","data":null}',
            $enableRes['body']
        );
        // Masked password '***' preserves saved LDAP_BIND_PASS
        self::assertSame('existing-secret', $realConfig->getLdapBindPass());

        $saveEvents = array_values(array_filter(
            $dispatchedEvents,
            static fn(array $e): bool => $e['name'] === 'save.config.ldap'
        ));
        self::assertCount(1, $saveEvents);
        // Line 67 overwrote isLdapEnabled=true before line 88 checked it, leaving description empty
        self::assertSame('', $saveEvents[0]['description']);

        $dispatchedEvents = [];

        $disableRes = $this->dispatchWeb('post', 'configLdap/save', [
            'ldap_enabled' => 0,
        ], [
            \SP\Domain\Config\Ports\ConfigFileService::class => $configFileService,
            EventDispatcherInterface::class => $dispatcher,
        ]);
        self::assertSame(
            '{"status":"OK","description":"Configuration updated","data":null}',
            $disableRes['body']
        );
        self::assertFalse($realConfig->isLdapEnabled());
        $disableEvents = array_values(array_filter(
            $dispatchedEvents,
            static fn(array $e): bool => $e['name'] === 'save.config.ldap'
        ));
        self::assertSame('LDAP disabled', $disableEvents[0]['description']);
    }

    /**
     * Characterization: ConfigLdap\CheckController::checkAction(bool $useConfigParams = true) defaults to saved
     * ConfigData when invoked at route `configLdap/check` (ignoring posted form parameters), and never assigns
     * template variable `results` before rendering `itemshow`.
     */
    #[Test]
    public function checkControllerDefaultsToSavedConfigInsteadOfRequestAndOmitsTemplateResults(): void
    {
        $capturedParams = null;
        $ldapCheck = $this->createMock(LdapCheckService::class);
        $ldapCheck->expects(self::once())
            ->method('getObjects')
            ->with(
                self::callback(static function (LdapParams $params) use (&$capturedParams): bool {
                    $capturedParams = $params;

                    return true;
                }),
                false
            )
            ->willReturn(new LdapCheckResults(['cn=alice,dc=example,dc=com'], 'person'));

        $result = $this->dispatchWeb('post', 'configLdap/check', [
            'ldap_server' => 'ldap.form.override',
            'ldap_server_type' => 2,
            'ldap_binduser' => 'cn=form',
            'ldap_bindpass' => 'form-pass',
        ], [
            LdapCheckService::class => $ldapCheck,
        ]);

        self::assertInstanceOf(LdapParams::class, $capturedParams);
        self::assertSame('ldap.saved.local', $capturedParams->getServer());

        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status']);
        self::assertSame(['LDAP connection OK', 'Objects found: 1'], $json['description']);
        self::assertSame(['cn=alice,dc=example,dc=com'], $json['data']['items'][0]['items']);
        // itemshow template renders header without the missing 'results' variable entries
        self::assertStringNotContainsString('cn=alice,dc=example,dc=com', $json['data']['template']);
    }

    /**
     * Characterization: ConfigLdap\CheckImportController validates `!($server || $searchBase || $bindDn)`
     * so providing ANY single parameter passes validation while omitting all three fails.
     */
    #[Test]
    public function checkImportAcceptsPartialSingleParamViaOrConditionAndRejectsWhenAllThreeEmpty(): void
    {
        $ldapCheck = $this->createMock(LdapCheckService::class);
        $ldapCheck->expects(self::once())
            ->method('getObjects')
            ->willReturn(new LdapCheckResults(['cn=partial,dc=test'], 'person'));

        // Empty searchBase & binduser, non-empty ldap_server passes `||` check (ldap_server_type=1 required due to tryFrom)
        $partialOk = $this->dispatchWeb('post', 'configLdap/checkImport', [
            'ldap_server' => 'ldap.onlyserver.local',
            'ldap_server_type' => 1,
            'ldap_base' => '',
            'ldap_binduser' => '',
            'ldap_bindpass' => '',
            'ldap_import_groups' => 0,
        ], [
            LdapCheckService::class => $ldapCheck,
        ]);
        $partialJson = json_decode($partialOk['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $partialJson['status']);

        $allEmpty = $this->dispatchWeb('post', 'configLdap/checkImport', [
            'ldap_server' => '',
            'ldap_server_type' => 1,
            'ldap_base' => '',
            'ldap_binduser' => '',
            'ldap_bindpass' => '',
        ]);
        $emptyJson = json_decode($allEmpty['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $emptyJson['status']);
    }

    /**
     * Characterization: ConfigLdap\ImportController joins user-attribute emptiness checks with group-attribute
     * check using `&&` instead of `||`, allowing empty user attributes/group/profile to pass validation
     * whenever `ldap_import_groups` is false (or `ldap_groupname_attribute` is non-empty); also returns only
     * user import counts even when `importGroups()` runs.
     */
    #[Test]
    public function importControllerAndConditionBypassesUserValidationAndHidesGroupCounts(): void
    {
        $userResults = new LdapImportResultsDto(2);
        $userResults->addSyncedObject();
        $userResults->addSyncedObject();

        $groupResults = new LdapImportResultsDto(3);
        $groupResults->addSyncedObject();
        $groupResults->addErrorObject();
        $groupResults->addErrorObject();

        $ldapImport = $this->createMock(LdapImportService::class);
        $ldapImport->expects(self::exactly(2))
            ->method('importUsers')
            ->willReturn($userResults);
        $ldapImport->expects(self::once())
            ->method('importGroups')
            ->with(
                self::isInstanceOf(LdapParams::class),
                self::callback(static fn(LdapImportParamsDto $dto): bool => $dto->getUserGroupNameAttribute() === 'cn')
            )
            ->willReturn($groupResults);

        // 1. All user params omitted/empty, ldap_import_groups=0 -> passes `&&` bug and invokes importUsers()
        $bypassed = $this->dispatchWeb('post', 'configLdap/import', [
            'ldap_server' => 'ldap.test.local',
            'ldap_server_type' => 1,
            'ldap_binduser' => 'cn=bind',
            'ldap_bindpass' => 'pass',
            'ldap_import_groups' => 0,
            'ldap_login_attribute' => '',
            'ldap_username_attribute' => '',
            'ldap_defaultgroup' => 0,
            'ldap_defaultprofile' => 0,
        ], [
            LdapImportService::class => $ldapImport,
        ]);
        $bypassJson = json_decode($bypassed['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $bypassJson['status']);
        self::assertSame(['Imported users: 2 / 2', 'Errors: 0'], $bypassJson['data']);

        // 2. With ldap_import_groups=1 and non-empty groupname_attribute -> importGroups() runs, yet group counts are dropped
        $withGroups = $this->dispatchWeb('post', 'configLdap/import', [
            'ldap_server' => 'ldap.test.local',
            'ldap_server_type' => 1,
            'ldap_binduser' => 'cn=bind',
            'ldap_bindpass' => 'pass',
            'ldap_import_groups' => 1,
            'ldap_login_attribute' => '',
            'ldap_username_attribute' => '',
            'ldap_groupname_attribute' => 'cn',
            'ldap_defaultgroup' => 0,
            'ldap_defaultprofile' => 0,
        ], [
            LdapImportService::class => $ldapImport,
        ]);
        $groupsJson = json_decode($withGroups['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $groupsJson['status']);
        self::assertSame(['Imported users: 2 / 2', 'Errors: 0'], $groupsJson['data']);

        // 3. Only throws ValidationException when BOTH user fields missing AND ldap_import_groups=1 AND empty groupname
        $invalid = $this->dispatchWeb('post', 'configLdap/import', [
            'ldap_import_groups' => 1,
            'ldap_login_attribute' => '',
            'ldap_groupname_attribute' => '',
        ]);
        $invalidJson = json_decode($invalid['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $invalidJson['status']);
        self::assertSame('Wrong LDAP parameters', $invalidJson['description']);
    }

    /**
     * @return array{code: int, body: string}
     */
    private function dispatchWeb(
        string $method,
        string $route,
        array $post = [],
        array $overrides = []
    ): array {
        $request = IntegrationTestCase::buildRequest(
            $method,
            'index.php',
            ['r' => $route],
            $post
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
            'paths' => decorate(static function (array $paths): array {
                $paths[] = [Path::VIEW, FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'web', 'themes')];

                return $paths;
            }),
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
}
