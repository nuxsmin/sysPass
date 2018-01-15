<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\UserPassRecover;

use SP\Core\Traits\InjectableTrait;
use SP\Repositories\UserPassRecover\UserPassRecoverRepository;

/**
 * Class UserPassRecoverService
 *
 * @package SP\Services\UserPassRecover
 */
class UserPassRecoverService
{
    use InjectableTrait;

    /**
     * Tiempo máximo para recuperar la clave
     */
    const MAX_PASS_RECOVER_TIME = 3600;
    /**
     * Número de intentos máximos para recuperar la clave
     */
    const MAX_PASS_RECOVER_LIMIT = 3;
    const USER_LOGIN_EXIST = 1;
    const USER_MAIL_EXIST = 2;

    /**
     * @var UserPassRecoverRepository
     */
    protected $userPassRecoverRepository;

    /**
     * UserPassRecoverService constructor.
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param UserPassRecoverRepository $userPassRecoverRepository
     */
    public function inject(UserPassRecoverRepository $userPassRecoverRepository)
    {
        $this->userPassRecoverRepository = $userPassRecoverRepository;
    }

    /**
     * Comprobar el límite de recuperaciones de clave.
     *
     * @param int $userId
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkAttemptsByUserId($userId)
    {
        return $this->userPassRecoverRepository->getAttemptsByUserId($userId, time() - self::MAX_PASS_RECOVER_TIME) >= self::MAX_PASS_RECOVER_LIMIT;
    }

    /**
     * @param $userId
     * @param $hash
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add($userId, $hash)
    {
        return $this->userPassRecoverRepository->add($userId, $hash);
    }

    /**
     * @param $hash
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function toggleUsedByHash($hash)
    {
        return $this->userPassRecoverRepository->toggleUsedByHash($hash, time() - self::MAX_PASS_RECOVER_TIME);
    }
}