<?php
declare(strict_types=1);
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

namespace SP\Tests\Domain\Account\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\Group;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Tests\UnitaryTestCase;

/**
 * Class PublicLinkKeyTest
 *
 */
#[Group('unitary')]
class PublicLinkKeyTest extends UnitaryTestCase
{

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function testGetKeyWithoutHash()
    {
        $publicLinkKey = new PublicLinkKey(self::$faker->sha1);

        $this->assertNotEmpty($publicLinkKey->getKey());
        $this->assertNotEmpty($publicLinkKey->getHash());
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function testGetKeyWithHash()
    {
        $hash = self::$faker->sha1;

        $publicLinkKey = new PublicLinkKey(self::$faker->sha1, $hash);

        $this->assertNotEmpty($publicLinkKey->getKey());
        $this->assertEquals($hash, $publicLinkKey->getHash());
    }
}
