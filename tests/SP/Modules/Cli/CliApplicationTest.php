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

namespace SP\Tests\Modules\Cli;

use PHPUnit\Framework\Attributes\Group;
use SP\Modules\Cli\CliCommandHelper;
use Symfony\Component\Console\Application;

/**
 * Class CliApplicationTest
 */
#[Group('integration')]
final class CliApplicationTest extends CliTestCase
{
    public function testCommandsAreRegisteredWithTheirNames(): void
    {
        $application = self::$dic->get(Application::class);
        $application->addCommands(self::$dic->get(CliCommandHelper::class)->getCommands());

        self::assertTrue($application->has('sp:install'));
        self::assertTrue($application->has('sp:backup'));
        self::assertTrue($application->has('sp:crypt:update-master-password'));
    }
}
