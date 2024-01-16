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

namespace SPT\Domain\Account\Services;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Services\AccountCrypt;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\Task\Ports\TaskInterface;
use SP\Domain\Task\Services\TaskFactory;
use SP\Infrastructure\File\FileException;
use SPT\Generators\AccountDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class AccountCryptServiceTest
 *
 * @group unitary
 */
class AccountCryptServiceTest extends UnitaryTestCase
{

    private MockObject|AccountService        $account;
    private MockObject|AccountHistoryService $accountHistory;
    private AccountCrypt                     $accountCrypt;
    private MockObject|CryptInterface        $crypt;

    /**
     * @throws ServiceException
     * @throws FileException
     * @throws Exception
     */
    public function testUpdateMasterPassword(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getUid')
             ->willReturn(self::$faker->uuid);
        $task->method('getTaskId')
             ->willReturn((string)self::$faker->randomNumber());
        $task->method('registerSession')
             ->willReturnSelf();

        $request =
            new UpdateMasterPassRequest(
                self::$faker->password,
                self::$faker->password,
                self::$faker->sha1,
                TaskFactory::register($task)
            );
        $accountData = array_map(static fn() => AccountDataGenerator::factory()->buildAccount(), range(0, 9));

        $this->account->expects(self::once())
                      ->method('getAccountsPassData')
                      ->willReturn($accountData);
        $this->account->expects(self::exactly(10))
                      ->method('updatePasswordMasterPass');
        $this->crypt->expects(self::exactly(10))
                    ->method('decrypt');
        $this->crypt->expects(self::exactly(10))
                    ->method('makeSecuredKey')
                    ->willReturn(self::$faker->password);
        $this->crypt->expects(self::exactly(10))
                    ->method('encrypt')
                    ->willReturn(self::$faker->password);
        $task->expects(self::exactly(2))
             ->method('writeJsonStatusAndFlush');

        $this->accountCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateMasterPasswordWithNoAccounts(): void
    {
        $request =
            new UpdateMasterPassRequest(
                self::$faker->password,
                self::$faker->password,
                self::$faker->sha1
            );

        $this->account->expects(self::once())
                      ->method('getAccountsPassData')
                      ->willReturn([]);
        $this->account->expects(self::never())
                      ->method('updatePasswordMasterPass');
        $this->crypt->expects(self::never())
                    ->method('decrypt');
        $this->crypt->expects(self::never())
                    ->method('makeSecuredKey');
        $this->crypt->expects(self::never())
                    ->method('encrypt');

        $this->accountCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateMasterPasswordDoesNotThrowException(): void
    {
        $request = new UpdateMasterPassRequest(self::$faker->password, self::$faker->password, self::$faker->sha1);
        $accountData = array_map(static fn() => AccountDataGenerator::factory()->buildAccount(), range(0, 9));

        $this->account->expects(self::once())
                      ->method('getAccountsPassData')
                      ->willReturn($accountData);
        $this->crypt->expects(self::exactly(10))
                    ->method('decrypt')
                    ->willThrowException(new SPException('test'));

        $this->accountCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateMasterPasswordThrowException(): void
    {
        $request = new UpdateMasterPassRequest(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $this->account->expects(self::once())
                      ->method('getAccountsPassData')
                      ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the accounts\' passwords');

        $this->accountCrypt->updateMasterPassword($request);
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    public function testUpdateHistoryMasterPassword(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getUid')
             ->willReturn(self::$faker->uuid);
        $task->method('getTaskId')
             ->willReturn((string)self::$faker->randomNumber());
        $task->method('registerSession')
             ->willReturnSelf();

        $request = new UpdateMasterPassRequest(
            self::$faker->password,
            self::$faker->password,
            self::$faker->sha1,
            TaskFactory::register($task)
        );
        $accountData = array_map(static fn() => AccountDataGenerator::factory()->buildAccount(), range(0, 9));

        $this->accountHistory->expects(self::once())
                             ->method('getAccountsPassData')
                             ->willReturn($accountData);
        $this->accountHistory->expects(self::exactly(10))
                             ->method('updatePasswordMasterPass');
        $this->crypt->expects(self::exactly(10))
                    ->method('decrypt');
        $task->expects(self::exactly(2))
             ->method('writeJsonStatusAndFlush');

        $this->accountCrypt->updateHistoryMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateHistoryMasterPasswordWithNoAccounts(): void
    {
        $request =
            new UpdateMasterPassRequest(
                self::$faker->password,
                self::$faker->password,
                self::$faker->sha1
            );

        $this->accountHistory->expects(self::once())
                             ->method('getAccountsPassData')
                             ->willReturn([]);
        $this->accountHistory->expects(self::never())
                             ->method('updatePasswordMasterPass');
        $this->crypt->expects(self::never())
                    ->method('decrypt');
        $this->crypt->expects(self::never())
                    ->method('makeSecuredKey');
        $this->crypt->expects(self::never())
                    ->method('encrypt');

        $this->accountCrypt->updateHistoryMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateHistoryMasterPasswordThrowException(): void
    {
        $request = new UpdateMasterPassRequest(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $this->accountHistory->expects(self::once())
                             ->method('getAccountsPassData')
                             ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the accounts\' passwords in history');

        $this->accountCrypt->updateHistoryMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateHistoryMasterPasswordDoesNotThrowException(): void
    {
        $request = new UpdateMasterPassRequest(self::$faker->password, self::$faker->password, self::$faker->sha1);
        $accountData = array_map(static fn() => AccountDataGenerator::factory()->buildAccount(), range(0, 9));

        $this->accountHistory->expects(self::once())
                             ->method('getAccountsPassData')
                             ->willReturn($accountData);
        $this->crypt->expects(self::exactly(10))
                    ->method('decrypt')
                    ->willThrowException(new SPException('test'));

        $this->accountCrypt->updateHistoryMasterPassword($request);
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncrypted(): void
    {
        $pass = self::$faker->password;
        $key = self::$faker->password;
        $masterPass = self::$faker->password;

        $this->crypt->expects(self::once())
                    ->method('makeSecuredKey')
                    ->with($masterPass)
                    ->willReturn($key);

        $this->crypt->expects(self::once())
                    ->method('encrypt')
                    ->with($pass)
                    ->willReturn($pass);

        $out = $this->accountCrypt->getPasswordEncrypted($pass, $masterPass);

        $this->assertEquals($pass, $out->getPass());
        $this->assertEquals($key, $out->getKey());
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithNoMasterPassword(): void
    {
        $this->context->setTrasientKey('_masterpass', '');

        $this->crypt->expects(self::never())
                    ->method('makeSecuredKey');

        $this->crypt->expects(self::never())
                    ->method('encrypt');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while retrieving master password from context');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password);
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithEmptyMasterPassword(): void
    {
        $this->crypt->expects(self::never())
                    ->method('makeSecuredKey');

        $this->crypt->expects(self::never())
                    ->method('encrypt');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Master password not set');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password, '');
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithLongPass(): void
    {
        $this->crypt->expects(self::once())
                    ->method('makeSecuredKey')
                    ->willReturn(self::$faker->password);

        $this->crypt->expects(self::once())
                    ->method('encrypt')
                    ->willReturn(self::$faker->text(1500));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password, self::$faker->password);
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithLongKey(): void
    {
        $this->crypt->expects(self::once())
                    ->method('makeSecuredKey')
                    ->willReturn(self::$faker->text(1500));

        $this->crypt->expects(self::once())
                    ->method('encrypt')
                    ->willReturn(self::$faker->password);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password, self::$faker->password);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = $this->createMock(AccountService::class);
        $this->accountHistory = $this->createMock(AccountHistoryService::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->accountCrypt =
            new AccountCrypt(
                $this->application,
                $this->account,
                $this->accountHistory,
                $this->crypt
            );
    }
}
