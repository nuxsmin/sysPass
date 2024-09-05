<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\ConfigAccount;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\IntegrationTestCase;

/**
 * Class ConfigAccountControllerTest
 */
#[Group('integration')]
class ConfigAccountControllerTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function save()
    {
        $data = [
            'publiclinks_enabled' => true,
            'publiclinks_image_enabled' => self::$faker->boolean(),
            'publiclinks_maxtime' => self::$faker->randomNumber(4),
            'publiclinks_maxviews' => self::$faker->randomNumber(4),
            'files_enabled' => true,
            'files_allowed_size' => self::$faker->randomNumber(3),
            'files_allowed_mimetypes' => [self::$faker->mimeType(), self::$faker->mimeType()],
            'account_globalsearch_enabled' => self::$faker->boolean(),
            'account_passtoimage_enabled' => self::$faker->boolean(),
            'account_link_enabled' => self::$faker->boolean(),
            'account_fullgroup_access_enabled' => self::$faker->boolean(),
            'account_count' => self::$faker->randomNumber(3),
            'account_resultsascards_enabled' => self::$faker->boolean(),
            'account_expire_enabled' => self::$faker->boolean(),
            'account_expire_time' => self::$faker->randomNumber(8),
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configAccount/save'], $data)
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Configuration updated","data":null}');
    }
}
