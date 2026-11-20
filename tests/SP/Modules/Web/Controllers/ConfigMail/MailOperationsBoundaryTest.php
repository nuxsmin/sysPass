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

namespace SP\Tests\Modules\Web\Controllers\ConfigMail;

use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Messages\TextFormatter;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Dtos\ConfigInput;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Notification\Dtos\MailParams;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\IntegrationTestCase;

use function DI\factory;

/**
 * HTTP dispatch & DTO characterization tests for ConfigMail controllers & ConfigInput:
 * - Anonymous & incomplete-session rejection across configMail/{save,check}
 * - Real Acl denial (HTTP 403 JSON) when configGeneral permission is missing
 * - ConfigInput::mailAddresses() & ConfigInput::events() sparse array keys + untrimmed space rejection
 * - ConfigMail\CheckController missing demo check, null optional fields TypeError, sparse key 0 crash when first
 *   recipient invalid, raw '***' password forwarding, and MailService::check() ServiceException envelope
 * - ConfigMail\SaveController ConfigSaveControllerBase demo guard, asymmetric disable clearing mailRequests/mailAuth
 */
#[Group('integration')]
final class MailOperationsBoundaryTest extends IntegrationTestCase
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
            'isMailEnabled' => true,
            'getMailServer' => 'smtp.saved.local',
            'getMailFrom' => 'saved@example.com',
            'getMailPass' => 'real-secret',
            'isMailRequestsEnabled' => true,
            'isMailAuthenabled' => true,
        ];
    }

    public static function mailRoutesProvider(): array
    {
        return [
            'configMail/save' => ['configMail/save'],
            'configMail/check' => ['configMail/check'],
        ];
    }

    #[Test]
    #[DataProvider('mailRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossMailRoutes(string $route): void
    {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $mailService = $this->createMock(MailService::class);
            $mailService->expects(self::never())->method('check');

            $result = $this->dispatchWeb('post', $route, [], [MailService::class => $mailService]);

            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    #[DataProvider('mailRoutesProvider')]
    public function unprivilegedUserReceives403AclDenial(string $route): void
    {
        $this->sessionUser = $this->makeUserDto(21, false);
        $this->sessionProfile = (new ProfileData())->setConfigImport(true); // lacks configGeneral (CONFIG_MAIL)

        $result = $this->dispatchWeb('post', $route);

        self::assertSame(403, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame("You don't have permission to access this page", $json['description']);
    }

    /**
     * Characterization: ConfigInput::mailAddresses() & ConfigInput::events() omit array_values() and trim(),
     * returning sparse arrays and rejecting space-padded comma items.
     */
    #[Test]
    public function configInputProducesSparseKeysAndRejectsCommaSpaceSeparatedAddresses(): void
    {
        $addresses = ConfigInput::mailAddresses('not-an-email, alice@example.com,bob@example.com');
        // ' alice@example.com' has leading whitespace so filter_var rejects it; 'bob@example.com' keeps index 2
        self::assertSame([2 => 'bob@example.com'], $addresses);

        $events = ConfigInput::events(['invalid event!', 'create.account', 'bad_2', 'edit.account']);
        self::assertSame([1 => 'create.account', 3 => 'edit.account'], $events);
    }

    /**
     * Characterization: ConfigMail\CheckController has no demo check, passes literal '***' if masked,
     * crashes with HTTP 500 TypeError if mail_user/mail_pass/mail_security are omitted or when sparse recipients
     * omit index 0, and maps MailService::check() ServiceException to HTTP 500 JSON ERROR.
     */
    #[Test]
    public function checkControllerLacksDemoGuardForwardsLiteralMaskAndHandlesErrors(): void
    {
        $this->configOverrides['isDemoEnabled'] = true;

        $capturedPass = null;
        $mailService = $this->createMock(MailService::class);
        $mailService->expects(self::exactly(2))
            ->method('check')
            ->willReturnCallback(function (MailParams $params, string $to) use (&$capturedPass): void {
                $capturedPass = $params->getPass();
                if ($to === 'fail@example.com') {
                    throw ServiceException::error('Error while sending the email', 'SMTP failed');
                }
            });

        // 1. Runs in demo mode with literal '***' password
        $ok = $this->dispatchWeb('post', 'configMail/check', [
            'mail_server' => 'smtp.test.local',
            'mail_port' => 587,
            'mail_user' => 'mailer',
            'mail_pass' => '***',
            'mail_security' => 'tls',
            'mail_from' => 'noreply@example.com',
            'mail_auth_enabled' => 1,
            'mail_recipients' => 'admin@example.com',
        ], [
            MailService::class => $mailService,
        ]);
        self::assertSame('***', $capturedPass);
        self::assertSame('OK', json_decode($ok['body'], true, 512, JSON_THROW_ON_ERROR)['status']);

        // 2. ServiceException produces HTTP 500 + JSON ERROR with its message
        $failed = $this->dispatchWeb('post', 'configMail/check', [
            'mail_server' => 'smtp.test.local',
            'mail_port' => 25,
            'mail_user' => '',
            'mail_pass' => '',
            'mail_security' => '',
            'mail_from' => 'noreply@example.com',
            'mail_recipients' => 'fail@example.com',
        ], [
            MailService::class => $mailService,
        ]);
        self::assertSame(500, $failed['code']);
        $failedJson = json_decode($failed['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $failedJson['status']);
        self::assertSame('Error while sending the email', $failedJson['description']);

        // 3. Invalid first recipient followed by valid second produces sparse key [1 => ...] -> missing key 0 HTTP 500
        $sparse = $this->dispatchWeb('post', 'configMail/check', [
            'mail_server' => 'smtp.test.local',
            'mail_port' => 25,
            'mail_user' => '',
            'mail_pass' => '',
            'mail_security' => '',
            'mail_from' => 'noreply@example.com',
            'mail_recipients' => 'bad,ok@example.com',
        ], [
            MailService::class => $mailService,
        ]);
        self::assertSame(500, $sparse['code']);

        // 4. Omitting nullable mail_user/mail_pass/mail_security causes TypeError -> HTTP 500 Internal error
        $omittedOptionals = $this->dispatchWeb('post', 'configMail/check', [
            'mail_server' => 'smtp.test.local',
            'mail_from' => 'noreply@example.com',
            'mail_recipients' => 'ok@example.com',
        ], [
            MailService::class => $mailService,
        ]);
        self::assertSame(500, $omittedOptionals['code']);
        self::assertSame(
            'Internal error',
            json_decode($omittedOptionals['body'], true, 512, JSON_THROW_ON_ERROR)['description']
        );
    }

    /**
     * Characterization: ConfigMail\SaveController enforces demo mode inside ConfigSaveControllerBase::saveConfig(),
     * disables both mailRequestsEnabled and mailAuthenabled when mail_enabled=0 (ignoring missing server/from),
     * preserves masked '***' password, and stores sparse non-zero-indexed mailRecipients/mailEvents arrays.
     */
    #[Test]
    public function saveControllerDemoGuardAndDisableClearsSubflagsAndPreservesSparseKeys(): void
    {
        $this->configOverrides['isDemoEnabled'] = true;

        $demoRes = $this->dispatchWeb('post', 'configMail/save', [
            'mail_enabled' => 0,
        ]);
        self::assertSame(
            '{"status":"WARNING","description":"Ey, this is a DEMO!!","data":null}',
            $demoRes['body']
        );

        $this->configOverrides['isDemoEnabled'] = false;

        $savedEvents = [];
        $formatter = new TextFormatter();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('notify')->willReturnCallback(
            static function (string $name, Event $event) use (&$savedEvents, $formatter): void {
                if ($name === 'save.config.mail') {
                    $savedEvents[] = $event->getEventMessage()?->getDescription($formatter, false);
                }
            }
        );

        $disableRes = $this->dispatchWeb('post', 'configMail/save', [
            'mail_enabled' => 0,
            'mail_server' => '',
            'mail_from' => '',
        ], [
            EventDispatcherInterface::class => $dispatcher,
        ]);

        $json = json_decode($disableRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status']);
        self::assertSame('Configuration updated', $json['description']);
        self::assertSame(['Mail disabled'], $savedEvents);

        $capturedConfig = null;
        $configService = $this->createMock(ConfigFileService::class);
        $configData = $this->createConfigFromOverrides(['isMailEnabled' => false]);
        $configService->method('getConfigData')->willReturn($configData);
        $configService->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (ConfigDataInterface $saved) use (&$capturedConfig): bool {
                $capturedConfig = $saved;

                return true;
            }))
            ->willReturnSelf();

        $enableRes = $this->dispatchWeb('post', 'configMail/save', [
            'mail_enabled' => 1,
            'mail_server' => 'smtp.new.local',
            'mail_from' => 'sender@example.com',
            'mail_recipients' => 'bad,alice@example.com',
            'mail_events' => ['invalid!', 'create.account'],
            'mail_auth_enabled' => 1,
            'mail_user' => 'mailer',
            'mail_pass' => '***',
        ], [
            ConfigFileService::class => $configService,
            EventDispatcherInterface::class => $dispatcher,
        ]);

        $enableJson = json_decode($enableRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $enableJson['status'], $enableRes['body']);
        self::assertSame([1 => 'alice@example.com'], $capturedConfig->getMailRecipients());
        self::assertSame([1 => 'create.account'], $capturedConfig->getMailEvents());
        self::assertSame('real-secret', $capturedConfig->getMailPass());
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

    private function createConfigFromOverrides(array $extra): ConfigDataInterface
    {
        $merged = array_merge($this->getConfigData(), $extra);

        return new \SP\Domain\Config\Adapters\ConfigData([
            ConfigDataInterface::INSTALLED => true,
            ConfigDataInterface::DB_NAME => 'syspass_test',
            ConfigDataInterface::PASSWORD_SALT => 'salt',
            ConfigDataInterface::AUTH_BASIC_ENABLED => false,
            ConfigDataInterface::HTTPS_ENABLED => false,
            ConfigDataInterface::MAINTENANCE => false,
            ConfigDataInterface::DEMO_ENABLED => false,
            ConfigDataInterface::MAIL_ENABLED => $merged['isMailEnabled'] ?? false,
            ConfigDataInterface::MAIL_PASS => $merged['getMailPass'] ?? 'real-secret',
        ]);
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
