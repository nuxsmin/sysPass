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

namespace SPT\Domain\Crypt\Services;

use PHPMailer\PHPMailer\Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\DataModel\Dto\ConfigRequest;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Services\TemporaryMasterPass;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SPT\UnitaryTestCase;

/**
 * Class TemporaryMasterPassTest
 *
 */
#[Group('unitary')]
class TemporaryMasterPassTest extends UnitaryTestCase
{

    private ConfigService|MockObject $configService;
    private UserService|MockObject $userService;
    private MailService|MockObject $mailService;
    private CryptInterface|MockObject $crypt;
    private TemporaryMasterPass $temporaryMasterPass;

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSendByEmailForAllUsers()
    {
        $key = self::$faker->sha1();
        $emails = array_map(static fn() => self::$faker->email(), range(0, 4));

        $this->userService
            ->expects(self::once())
            ->method('getUserEmailForAll')
            ->willReturn(array_map(static fn($email) => (object)['email' => $email], $emails));

        $this->mailService
            ->expects(self::once())
            ->method('send')
            ->with(self::anything(), $emails, self::anything());

        $this->temporaryMasterPass->sendByEmailForAllUsers($key);
    }

    /**
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function testGetUsingKey()
    {
        $masterPass = self::$faker->password();
        $masterKey = self::$faker->sha1();
        $key = self::$faker->sha1();

        $this->configService
            ->expects(self::exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['tempmaster_pass'], ['tempmaster_passkey']))
            ->willReturn($masterPass, $masterKey);

        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->with($masterPass, $masterKey, $key)
            ->willReturn('test');

        self::assertEquals('test', $this->temporaryMasterPass->getUsingKey($key));
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSendByEmailForGroup()
    {
        $groupId = self::$faker->randomNumber();
        $key = self::$faker->sha1();
        $emails = array_map(static fn() => self::$faker->email(), range(0, 4));

        $this->userService
            ->expects(self::once())
            ->method('getUserEmailForGroup')
            ->with($groupId)
            ->willReturn(array_map(static fn($email) => (object)['email' => $email], $emails));

        $this->mailService
            ->expects(self::once())
            ->method('send')
            ->with(self::anything(), $emails, self::anything());

        $this->temporaryMasterPass->sendByEmailForGroup($groupId, $key);
    }

    /**
     * @throws ServiceException
     */
    public function testCheckTempMasterPass()
    {
        $now = time();
        $pass = self::$faker->password();
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $this->configService
            ->expects(self::exactly(4))
            ->method('getByParam')
            ->with(
                ...
                self::withConsecutive(
                    ['tempmaster_maxtime'],
                    ['tempmaster_passtime'],
                    ['tempmaster_attempts'],
                    ['tempmaster_passhash']
                )
            )
            ->willReturn(
                (string)($now + 3600),
                (string)$now,
                (string)self::$faker->numberBetween(0, 49),
                $hash
            );

        $this->configService
            ->expects(self::never())
            ->method('save');

        self::assertTrue($this->temporaryMasterPass->checkKey($pass));
    }

    /**
     * @throws ServiceException
     */
    public function testCheckTempMasterPassWithZeroMaxTime()
    {
        $pass = self::$faker->password();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('tempmaster_maxtime')
            ->willReturn('0');

        $this->configService
            ->expects(self::never())
            ->method('save');

        self::assertFalse($this->temporaryMasterPass->checkKey($pass));
    }

    /**
     * @throws ServiceException
     */
    public function testCheckTempMasterPassWithWrongKey()
    {
        $now = time();
        $pass = self::$faker->password();
        $hash = password_hash(self::$faker->sha1(), PASSWORD_BCRYPT);
        $attempts = self::$faker->numberBetween(0, 49);

        $this->configService
            ->expects(self::exactly(4))
            ->method('getByParam')
            ->with(
                ...
                self::withConsecutive(
                    ['tempmaster_maxtime'],
                    ['tempmaster_passtime'],
                    ['tempmaster_attempts'],
                    ['tempmaster_passhash']
                )
            )
            ->willReturn(
                (string)$now,
                (string)($now + 3600),
                (string)$attempts,
                $hash
            );

        $this->configService
            ->expects(self::once())
            ->method('save')
            ->with('tempmaster_attempts', $attempts + 1);

        self::assertFalse($this->temporaryMasterPass->checkKey($pass));
    }

    /**
     * @throws ServiceException
     */
    public function testCheckTempMasterPassWithMaxAttempts()
    {
        $now = time();
        $pass = self::$faker->password();
        $hash = password_hash(self::$faker->sha1(), PASSWORD_BCRYPT);
        $attempts = self::$faker->numberBetween(0, 49);

        $this->configService
            ->expects(self::exactly(3))
            ->method('getByParam')
            ->with(
                ...
                self::withConsecutive(
                    ['tempmaster_maxtime'],
                    ['tempmaster_passtime'],
                    ['tempmaster_attempts']
                )
            )
            ->willReturn(
                (string)$now,
                (string)($now + 3600),
                '50'
            );

        $configRequest = new ConfigRequest();
        $configRequest->add('tempmaster_pass', '');
        $configRequest->add('tempmaster_passkey', '');
        $configRequest->add('tempmaster_passhash', '');
        $configRequest->add('tempmaster_passtime', 0);
        $configRequest->add('tempmaster_maxtime', 0);
        $configRequest->add('tempmaster_attempts', 0);

        $this->configService
            ->expects(self::once())
            ->method('saveBatch')
            ->with($configRequest);

        self::assertFalse($this->temporaryMasterPass->checkKey($pass));
    }

    /**
     * @throws ServiceException
     */
    public function testCheckTempMasterPassWithNoConfigItem()
    {
        $pass = self::$faker->password();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->willThrowException(NoSuchItemException::error('test'));

        $this->configService
            ->expects(self::never())
            ->method('save');

        self::assertFalse($this->temporaryMasterPass->checkKey($pass));
    }

    /**
     * @throws ServiceException
     */
    public function testCheckTempMasterPassWithError()
    {
        $pass = self::$faker->password();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->willThrowException(new RuntimeException('test'));

        $this->configService
            ->expects(self::never())
            ->method('save');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while checking the temporary password');

        $this->temporaryMasterPass->checkKey($pass);
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     */
    public function testCreate()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'test_master_pass');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->willReturn('super_secure_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with('test_master_pass', self::anything(), self::anything())
            ->willReturn('super_secret');

        $this->configService
            ->expects(self::once())
            ->method('saveBatch')
            ->with(
                new Callback(static function (ConfigRequest $configRequest) {
                    return $configRequest->get('tempmaster_pass') === 'super_secret'
                           && $configRequest->get('tempmaster_passkey') === 'super_secure_key'
                           && !empty($configRequest->get('tempmaster_passhash'))
                           && !empty($configRequest->get('tempmaster_passtime'))
                           && $configRequest->get('tempmaster_maxtime') <= time() + 3600
                           && $configRequest->get('tempmaster_maxtime') > $configRequest->get('tempmaster_passtime')
                           && $configRequest->get('tempmaster_attempts') === '0';
                })
            );

        self::assertNotEmpty($this->temporaryMasterPass->create(3600));
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     */
    public function testCreateWithError()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'test_master_pass');

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->willReturn('super_secure_key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with('test_master_pass', self::anything(), self::anything())
            ->willReturn('super_secret');

        $this->configService
            ->expects(self::once())
            ->method('saveBatch')
            ->with(
                new Callback(static function (ConfigRequest $configRequest) {
                    return $configRequest->get('tempmaster_pass') === 'super_secret'
                           && $configRequest->get('tempmaster_passkey') === 'super_secure_key'
                           && !empty($configRequest->get('tempmaster_passhash'))
                           && !empty($configRequest->get('tempmaster_passtime'))
                           && $configRequest->get('tempmaster_maxtime') <= time() + 3600
                           && $configRequest->get('tempmaster_maxtime') > $configRequest->get('tempmaster_passtime')
                           && $configRequest->get('tempmaster_attempts') === '0';
                })
            )
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while generating the temporary password');

        $this->temporaryMasterPass->create(3600);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->configService = $this->createMock(ConfigService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->temporaryMasterPass = new TemporaryMasterPass(
            $this->application,
            $this->configService,
            $this->userService,
            $this->mailService,
            $this->crypt
        );
    }


}
