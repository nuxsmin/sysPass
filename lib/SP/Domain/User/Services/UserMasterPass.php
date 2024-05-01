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

namespace SP\Domain\User\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Dtos\UserMasterPassDto;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Ports\UserRepository;

use function SP\__u;

/**
 * Class UserMasterPass
 */
final class UserMasterPass extends Service implements UserMasterPassService
{
//    public const SESSION_MASTERPASS_UPDATED = 'mpass_updated';

    private const PARAM_MASTER_PWD      = 'masterPwd';
    private const PARAM_LASTUPDATEMPASS = 'lastupdatempass';

    public function __construct(
        Application                     $application,
        private readonly UserRepository $userRepository,
        private readonly ConfigService  $configService,
        private readonly CryptInterface $crypt
    ) {
        parent::__construct($application);
    }

    /**
     * @inheritDoc
     */
    public function updateFromOldPass(
        string       $oldUserPass,
        UserLoginDto $userLoginDto,
        UserDataDto  $userDataDto
    ): UserMasterPassDto {
        $response = $this->load($userLoginDto, $userDataDto, $oldUserPass);

        if ($response->getUserMasterPassStatus() === UserMasterPassStatus::Ok) {
            return $this->updateOnLogin($response->getClearMasterPass(), $userLoginDto, $userDataDto->getId());
        }

        return new UserMasterPassDto(UserMasterPassStatus::Invalid);
    }

    /**
     * @inheritDoc
     */
    public function load(
        UserLoginDto $userLoginDto,
        UserDataDto  $userDataDto,
        ?string      $userPass = null
    ): UserMasterPassDto {
        try {
            if (empty($userDataDto->getMPass())
                || empty($userDataDto->getMKey())
                || empty($systemMasterPassHash = $this->configService->getByParam(self::PARAM_MASTER_PWD))
            ) {
                return new UserMasterPassDto(UserMasterPassStatus::NotSet);
            }

            if ($userDataDto->getLastUpdateMPass() <
                (int)$this->configService->getByParam(self::PARAM_LASTUPDATEMPASS, 0)
            ) {
                return new UserMasterPassDto(UserMasterPassStatus::Changed);
            }

            if ($userPass === null && $userDataDto->getIsChangedPass()) {
                return new UserMasterPassDto(UserMasterPassStatus::CheckOld);
            }


            $key = $this->makeKeyForUser($userLoginDto->getLoginUser(), $userPass ?? $userLoginDto->getLoginPass());

            $userMasterPass = $this->crypt->decrypt($userDataDto->getMPass(), $userDataDto->getMKey(), $key);

            // Comprobamos el hash de la clave del usuario con la guardada
            if (Hash::checkHashKey($userMasterPass, $systemMasterPassHash)) {
                $this->setMasterKeyInContext($userMasterPass);

                return new UserMasterPassDto(
                    UserMasterPassStatus::Ok,
                    $userMasterPass,
                    $userDataDto->getMPass(),
                    $userDataDto->getMKey()
                );
            }
        } catch (CryptException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            return new UserMasterPassDto(UserMasterPassStatus::CheckOld);
        } catch (Exception $e) {
            throw ServiceException::from($e);
        }

        return new UserMasterPassDto(UserMasterPassStatus::Invalid);
    }

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @return string con la clave de cifrado
     */
    private function makeKeyForUser(string $userLogin, string $userPass): string
    {
        return trim($userPass . $userLogin . $this->config->getConfigData()->getPasswordSalt());
    }

    /**
     * @inheritDoc
     */
    public function updateOnLogin(string $userMasterPass, UserLoginDto $userLoginDto, int $userId): UserMasterPassDto
    {
        try {
            $systemMasterPassHash = $this->configService->getByParam(self::PARAM_MASTER_PWD);

            if (null === $systemMasterPassHash) {
                $systemMasterPassHash = Hash::hashKey($userMasterPass);

                $this->configService->save(self::PARAM_MASTER_PWD, $systemMasterPassHash);
            }

            if (Hash::checkHashKey($userMasterPass, $systemMasterPassHash)) {
                $response = $this->create(
                    $userMasterPass,
                    $userLoginDto->getLoginUser(),
                    $userLoginDto->getLoginPass()
                );

                $this->userRepository->updateMasterPassById(
                    $userId,
                    $response->getCryptMasterPass(),
                    $response->getCryptSecuredKey()
                );

//            $this->context->setTrasientKey(self::SESSION_MASTERPASS_UPDATED, true);

                $this->setMasterKeyInContext($userMasterPass);

                return $response;
            }

            return new UserMasterPassDto(UserMasterPassStatus::Invalid);
        } catch (Exception $e) {
            throw ServiceException::from($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function create(string $masterPass, string $userLogin, string $userPass): UserMasterPassDto
    {
        $key = $this->makeKeyForUser($userLogin, $userPass);

        try {
            $securedKey = $this->crypt->makeSecuredKey($key);

            if (strlen($securedKey) > 1000) {
                throw ServiceException::error(__u('Internal error'), null, Service::STATUS_INTERNAL_ERROR);
            }

            $encryptedMasterPass = $this->crypt->encrypt($masterPass, $securedKey, $key);

            if (strlen($encryptedMasterPass) > 1000) {
                throw ServiceException::error(__u('Internal error'), null, Service::STATUS_INTERNAL_ERROR);
            }

            return new UserMasterPassDto(UserMasterPassStatus::Ok, $masterPass, $encryptedMasterPass, $securedKey);
        } catch (CryptException $e) {
            throw ServiceException::from($e);
        }
    }
}
