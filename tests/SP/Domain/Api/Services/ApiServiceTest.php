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

namespace SP\Tests\Domain\Api\Services;

use Exception;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\CryptException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\Domain\Api\Ports\ApiRequestInterface;
use SP\Domain\Api\Services\ApiService;
use SP\Domain\Auth\Ports\AuthTokenServiceInterface;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Security\Ports\TrackServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Security\Repositories\TrackRequest;
use SP\Modules\Api\Controllers\Help\AccountHelp;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\Generators\UserProfileDataGenerator;
use SP\Tests\UnitaryTestCase;
use stdClass;

use function PHPUnit\Framework\onConsecutiveCalls;

/**
 * Class ApiServiceTest
 *
 * @group unitary
 */
class ApiServiceTest extends UnitaryTestCase
{

    private TrackServiceInterface|MockObject       $trackService;
    private ApiRequestInterface|MockObject         $apiRequest;
    private AuthTokenServiceInterface|MockObject   $authTokenService;
    private UserServiceInterface|MockObject        $userService;
    private MockObject|UserProfileServiceInterface $userProfileService;
    private ApiService                             $apiService;
    private TrackRequest                           $trackRequest;

    public static function getParamIntDataProvider(): array
    {
        $faker = Factory::create();
        $number = $faker->randomNumber();

        return [
            [$number, $number, false, true],
            [$number, $number, true, true],
            [$number, $number, true, false],
            [(string)$number, $number, false, true],
            [$faker->colorName, null, false, true],
            [null, $faker->randomNumber(), false, true],
        ];
    }

    public static function getParamStringDataProvider(): array
    {
        $faker = Factory::create();
        $string = $faker->colorName;

        // mixed $value, mixed $expected, bool $required, bool $present
        return [
            [$string, $string, false, true],
            [$string, $string, true, true],
            [$string, $string, true, false],
            [null, null, false, true],
            [null, $faker->colorName, false, true],
        ];
    }

    public static function getParamDataProvider(): array
    {
        $faker = Factory::create();
        $string = $faker->colorName;

        // mixed $value, mixed $expected, bool $required, bool $present
        return [
            [$string, $string, false, true],
            [$string, $string, true, true],
            [$string, $string, true, false],
            [$string, $string, false, false],
        ];
    }

    public static function getParamArrayDataProvider(): array
    {
        $faker = Factory::create();
        $numbers = array_map(fn() => $faker->randomNumber(), range(0, 4));
        $strings = array_map(fn() => $faker->colorName, range(0, 4));

        // mixed $value, mixed $expected, bool $required, bool $present
        return [
            [$numbers, $numbers, false, true],
            [$strings, $strings, false, true],
            [$numbers, $numbers, true, true],
            [$strings, $strings, true, true],
            [$numbers, $numbers, true, false],
            [$strings, $strings, true, false],
            [$numbers, $numbers, false, false],
            [$strings, $strings, false, false],
            [null, null, false, false],
        ];
    }

    public static function getParamRawDataProvider(): array
    {
        $faker = Factory::create();
        $password = $faker->password;

        // mixed $value, mixed $expected, bool $required, bool $present
        return [
            [$password, $password, false, true],
            [$password, $password, true, true],
            [$password, $password, true, false],
            [$password, $password, false, false],
            [null, null, false, false],
        ];
    }

    /**
     * @dataProvider getParamDataProvider
     *
     * @param  mixed  $value
     * @param  mixed  $expected
     * @param  bool  $required
     * @param  bool  $present
     */
    public function testGetParam(mixed $value, mixed $expected, bool $required, bool $present)
    {
        $this->checkParam([$this->apiService, 'getParam'], ...func_get_args());
    }

    private function checkParam(
        callable $callable,
        mixed $value,
        mixed $expected,
        bool $required,
        bool $present
    ): void {
        $param = self::$faker->colorName;

        if ($required) {
            $this->apiRequest->expects(self::once())->method('exists')->with($param)->willReturn($present);
        }

        if (!$present) {
            $this->expectException(ServiceException::class);
            $this->expectExceptionMessage('Wrong parameters');

            $callable($param, true);
        } else {
            $this->apiRequest->expects(self::once())->method('get')->with($param)->willReturn($value);

            $out = $callable($param, $required, $expected);

            $this->assertEquals($expected, $out);
        }
    }

    /**
     * @throws InvalidClassException
     * @throws InvalidArgumentException
     */
    public function testGetParamWithHelp()
    {
        $apiRequest = $this->createMock(ApiRequestInterface::class);
        $apiRequest->method('exists')->willReturn(false);
        $apiRequest->method('getMethod')->willReturn('account/view');

        $apiService = new ApiService(
            $this->application,
            $this->trackService,
            $apiRequest,
            $this->authTokenService,
            $this->userService,
            $this->userProfileService
        );

        $apiService->setHelpClass(AccountHelp::class);

        try {
            $apiService->getParam(self::$faker->colorName, true);
        } catch (ServiceException $e) {
            $this->assertNotEmpty($e->getHint());
        }
    }

    /**
     * @throws InvalidClassException
     */
    public function testSetHelpClass()
    {
        $this->apiService->setHelpClass(AccountHelp::class);

        $reflection = new ReflectionClass($this->apiService);
        $property = $reflection->getProperty('helpClass');

        $this->assertEquals(AccountHelp::class, $property->getValue($this->apiService));
    }

    /**
     * @throws InvalidClassException
     */
    public function testSetHelpClassError()
    {
        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage('Invalid class for helper');

        $this->apiService->setHelpClass(stdClass::class);
    }

    /**
     * @dataProvider getParamIntDataProvider
     */
    public function testGetParamInt(mixed $value, mixed $expected, bool $required, bool $present)
    {
        $this->checkParam([$this->apiService, 'getParamInt'], ...func_get_args());
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetup()
    {
        $actionId = self::$faker->randomNumber(5);

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(false);

        $authToken = self::$faker->password;

        $this->apiRequest->expects(self::once())->method('get')->with('authToken')->willReturn($authToken);

        $userId = self::$faker->randomNumber();

        $authTokenData = new AuthTokenData(['actionId' => $actionId, 'userId' => $userId]);

        $this->authTokenService
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($actionId, $authToken)
            ->willReturn($authTokenData);

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['isDisabled' => false]);

        $this->userService->expects(self::once())->method('getById')->with($userId)->willReturn($userData);
        $this->userProfileService->expects(self::once())
                                 ->method('getById')
                                 ->with($userData->getUserProfileId())
                                 ->willReturn(UserProfileDataGenerator::factory()->buildUserProfileData());

        $this->apiService->setup($actionId);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trackService = $this->createMock(TrackServiceInterface::class);
        $this->apiRequest = $this->createMock(ApiRequestInterface::class);
        $this->authTokenService = $this->createMock(AuthTokenServiceInterface::class);
        $this->userService = $this->createMock(UserServiceInterface::class);
        $this->userProfileService = $this->createMock(UserProfileServiceInterface::class);

        $this->trackRequest = new TrackRequest(time(), __CLASS__);
        $this->trackService->method('getTrackRequest')->willReturn($this->trackRequest);
        $this->apiRequest->method('getMethod')->willReturn(self::$faker->colorName);

        $this->apiService = new ApiService(
            $this->application,
            $this->trackService,
            $this->apiRequest,
            $this->authTokenService,
            $this->userService,
            $this->userProfileService
        );
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetupAttemptsExceeded()
    {
        $actionId = self::$faker->randomNumber();

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(true);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Attempts exceeded');

        $this->apiService->setup($actionId);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetupTrackingError()
    {
        $actionId = self::$faker->randomNumber();

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(true);

        $this->trackService
            ->expects(self::once())
            ->method('add')
            ->willThrowException(new Exception());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->apiService->setup($actionId);
    }

    /**
     * @throws SPException
     */
    public function testSetupInvalidToken()
    {
        $actionId = self::$faker->randomNumber();

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(false);

        $this->apiRequest
            ->expects(self::once())
            ->method('get')
            ->with('authToken')
            ->willThrowException(new NoSuchItemException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->apiService->setup($actionId);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetupAccessDenied()
    {
        $actionId = self::$faker->randomNumber();

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(false);

        $authToken = self::$faker->password;

        $this->apiRequest->expects(self::once())->method('get')->with('authToken')->willReturn($authToken);

        $userId = self::$faker->randomNumber();

        $authTokenData = new AuthTokenData(['actionId' => self::$faker->randomNumber(), 'userId' => $userId]);

        $this->authTokenService
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($actionId, $authToken)
            ->willReturn($authTokenData);

        $this->trackService->expects(self::once())->method('add')->with($this->trackRequest);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unauthorized access');

        $this->apiService->setup($actionId);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetupWithMasterPass()
    {
        $actionId = AclActionsInterface::ACCOUNT_VIEW_PASS;

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(false);

        $authToken = self::$faker->password;
        $authTokenHash = password_hash($authToken, PASSWORD_BCRYPT);

        $this->apiRequest->expects(self::exactly(3))
                         ->method('get')
                         ->will(onConsecutiveCalls($authToken, $authToken, $authToken));

        $vaultKey = sha1($authToken.$authToken);

        $vault = Vault::factory(new Crypt())->saveData(self::$faker->password, $vaultKey);

        $userId = self::$faker->randomNumber();

        $authTokenData =
            new AuthTokenData(
                ['actionId' => $actionId, 'userId' => $userId, 'hash' => $authTokenHash, 'vault' => serialize($vault)]
            );

        $this->authTokenService
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($actionId, $authToken)
            ->willReturn($authTokenData);

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['isDisabled' => false]);

        $this->userService->expects(self::once())->method('getById')->with($userId)->willReturn($userData);
        $this->userProfileService->expects(self::once())
                                 ->method('getById')
                                 ->with($userData->getUserProfileId())
                                 ->willReturn(UserProfileDataGenerator::factory()->buildUserProfileData());

        $this->apiRequest->expects(self::once())->method('exists')->with('tokenPass')->willReturn(true);

        $this->apiService->setup($actionId);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetupWithMasterPassWrongTokenPass()
    {
        $actionId = AclActionsInterface::ACCOUNT_VIEW_PASS;

        $this->trackService
            ->expects(self::once())
            ->method('checkTracking')
            ->with($this->trackRequest)
            ->willReturn(false);

        $authToken = self::$faker->password;
        $authTokenHash = password_hash($authToken, PASSWORD_BCRYPT);

        $this->apiRequest->expects(self::exactly(3))
                         ->method('get')
                         ->will(onConsecutiveCalls($authToken, $authToken, $authToken));

        $vault = Vault::factory(new Crypt())->saveData(self::$faker->password, sha1(self::$faker->password));

        $userId = self::$faker->randomNumber();

        $authTokenData =
            new AuthTokenData(
                ['actionId' => $actionId, 'userId' => $userId, 'hash' => $authTokenHash, 'vault' => serialize($vault)]
            );

        $this->authTokenService
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($actionId, $authToken)
            ->willReturn($authTokenData);

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['isDisabled' => false]);

        $this->userService->expects(self::once())->method('getById')->with($userId)->willReturn($userData);
        $this->userProfileService->expects(self::once())
                                 ->method('getById')
                                 ->with($userData->getUserProfileId())
                                 ->willReturn(UserProfileDataGenerator::factory()->buildUserProfileData());

        $this->apiRequest->expects(self::once())->method('exists')->with('tokenPass')->willReturn(true);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->apiService->setup($actionId);
    }

    /**
     * @dataProvider getParamStringDataProvider
     *
     * @return void
     */
    public function testGetParamString(mixed $value, mixed $expected, bool $required, bool $present)
    {
        $this->checkParam([$this->apiService, 'getParamString'], ...func_get_args());
    }

    /**
     * @dataProvider getParamArrayDataProvider
     *
     * @return void
     */
    public function testGetParamArray(mixed $value, mixed $expected, bool $required, bool $present)
    {
        $this->checkParam([$this->apiService, 'getParamArray'], ...func_get_args());
    }

    /**
     * @dataProvider getParamRawDataProvider
     *
     * @return void
     */
    public function testGetParamRaw(mixed $value, mixed $expected, bool $required, bool $present)
    {
        $this->checkParam([$this->apiService, 'getParamRaw'], ...func_get_args());
    }

    public function testGetRequestId()
    {
        $this->assertEquals($this->apiRequest->getId(), $this->apiService->getRequestId());
    }

    /**
     * @throws ContextException
     * @throws CryptException
     * @throws ServiceException
     * @throws SPException
     */
    public function testRequireMasterPass()
    {
        $actionId = self::$faker->randomNumber();
        $authToken = self::$faker->password;
        $authTokenHash = password_hash($authToken, PASSWORD_BCRYPT);

        $this->apiRequest->expects(self::exactly(3))
                         ->method('get')
                         ->willReturn($authToken);

        $vaultKey = sha1($authToken.$authToken);

        $masterPass = self::$faker->password;

        $vault = Vault::factory(new Crypt())->saveData($masterPass, $vaultKey);

        $userId = self::$faker->randomNumber();

        $authTokenData =
            new AuthTokenData(
                ['actionId' => $actionId, 'userId' => $userId, 'hash' => $authTokenHash, 'vault' => serialize($vault)]
            );

        $this->authTokenService
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($actionId, $authToken)
            ->willReturn($authTokenData);

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['isDisabled' => false]);

        $this->userService->expects(self::once())->method('getById')->with($userId)->willReturn($userData);
        $this->userProfileService->expects(self::once())
                                 ->method('getById')
                                 ->with($userData->getUserProfileId())
                                 ->willReturn(UserProfileDataGenerator::factory()->buildUserProfileData());

        $this->apiRequest->expects(self::once())->method('exists')->with('tokenPass')->willReturn(true);

        $this->apiService->setup($actionId);
        $this->apiService->requireMasterPass();

        $this->assertEquals($masterPass, $this->context->getTrasientKey(ContextInterface::MASTER_PASSWORD_KEY));
    }

    /**
     * @throws ContextException
     * @throws ServiceException
     * @throws SPException
     */
    public function testRequireMasterPassNotInitialized()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('API not initialized');

        $this->apiService->requireMasterPass();
    }

    /**
     * @throws ServiceException
     * @throws CryptException
     * @throws SPException
     */
    public function testGetMasterPass()
    {
        $actionId = AclActionsInterface::ACCOUNT_VIEW_PASS;
        $authToken = self::$faker->password;
        $authTokenHash = password_hash($authToken, PASSWORD_BCRYPT);

        $this->apiRequest->expects(self::exactly(3))
                         ->method('get')
                         ->willReturn($authToken);

        $vaultKey = sha1($authToken.$authToken);

        $masterPass = self::$faker->password;

        $vault = Vault::factory(new Crypt())->saveData($masterPass, $vaultKey);

        $userId = self::$faker->randomNumber();

        $authTokenData =
            new AuthTokenData(
                ['actionId' => $actionId, 'userId' => $userId, 'hash' => $authTokenHash, 'vault' => serialize($vault)]
            );

        $this->authTokenService
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($actionId, $authToken)
            ->willReturn($authTokenData);

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['isDisabled' => false]);

        $this->userService->expects(self::once())->method('getById')->with($userId)->willReturn($userData);
        $this->userProfileService->expects(self::once())
                                 ->method('getById')
                                 ->with($userData->getUserProfileId())
                                 ->willReturn(UserProfileDataGenerator::factory()->buildUserProfileData());

        $this->apiRequest->expects(self::once())->method('exists')->with('tokenPass')->willReturn(true);

        $this->apiService->setup($actionId);

        $this->assertEquals(
            $this->apiService->getMasterPass(),
            $this->context->getTrasientKey(ContextInterface::MASTER_PASSWORD_KEY)
        );
    }
}
