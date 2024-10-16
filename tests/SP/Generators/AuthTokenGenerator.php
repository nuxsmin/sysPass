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

namespace SP\Tests\Generators;

use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Domain\Auth\Models\AuthToken;
use SP\Domain\Core\Exceptions\CryptException;

/**
 * Class AuthTokenGenerator
 */
final class AuthTokenGenerator extends DataGenerator
{
    public function buildAuthToken(): AuthToken
    {
        return new AuthToken($this->authTokenProperties());
    }

    private function authTokenProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'userId' => $this->faker->randomNumber(3),
            'token' => $this->faker->sha1(),
            'createdBy' => $this->faker->randomNumber(3),
            'startDate' => $this->faker->unixTime(),
            'actionId' => $this->faker->randomNumber(3),
            'hash' => $this->faker->sha1(),
            'vault' => serialize($this->getVault())
        ];
    }

    private function getVault(): ?Vault
    {
        try {
            return Vault::factory(new Crypt())->saveData($this->faker->text(), $this->faker->sha1());
        } catch (CryptException) {
            return null;
        }
    }
}
