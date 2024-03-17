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

namespace SPT\Core\Context;

use PHPUnit\Framework\Attributes\Group;
use SP\Core\Context\ContextFactory;
use SP\Core\Context\SessionContext;
use SP\Core\Context\StatelessContext;
use SPT\UnitaryTestCase;

/**
 * Class ContextFactoryTest
 */
#[Group('unitary')]
class ContextFactoryTest extends UnitaryTestCase
{
    public function testGetForModuleWithWeb()
    {
        $out = ContextFactory::getForModule('web');

        $this->assertInstanceOf(SessionContext::class, $out);
    }

    public function testGetForModuleWithOther()
    {
        $out = ContextFactory::getForModule(self::$faker->colorName);

        $this->assertInstanceOf(StatelessContext::class, $out);
    }
}
