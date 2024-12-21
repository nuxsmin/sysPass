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

namespace SP\Tests\Domain\User\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Core\Crypt\Hash;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Services\UserPass;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Tests\UnitaryTestCase;

/**+
 * Class UserPassTest
 */
#[Group('unitary')]
class UserPassTest extends UnitaryTestCase
{

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testMigrateUserPassById()
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
                       ->method('updatePassById')
                       ->with(
                           self::callback(static function (UserModel $user) {
                               return $user->getId() === 100
                                      && $user->isChangePass() === false
                                      && $user->isChangedPass() === true
                                      && $user->isMigrate() == false
                                      && Hash::checkHashKey('a_password', $user->getPass());
                           })
                       )
                       ->willReturn(1);

        $usePass = new UserPass($this->application, $userRepository);
        $usePass->migrateUserPassById(100, 'a_password');
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testMigrateUserPassByIdWithException()
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
                       ->method('updatePassById')
                       ->willReturn(0);

        $usePass = new UserPass($this->application, $userRepository);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('User does not exist');

        $usePass->migrateUserPassById(100, 'a_password');
    }
}
