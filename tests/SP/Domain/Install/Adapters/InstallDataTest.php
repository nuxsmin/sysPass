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

namespace SP\Tests\Domain\Install\Adapters;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Install\Adapters\InstallData;

#[Group('unitary')]
final class InstallDataTest extends TestCase
{
    #[Test]
    public function siteLanguageStripsEncodingSuffix(): void
    {
        $installData = new InstallData();

        $installData->setSiteLang('en_US.utf8');

        self::assertSame('en_US', $installData->getSiteLang());
    }

    #[Test]
    public function siteLanguageKeepsCanonicalValue(): void
    {
        $installData = new InstallData();

        $installData->setSiteLang('es_ES');

        self::assertSame('es_ES', $installData->getSiteLang());
    }
}
