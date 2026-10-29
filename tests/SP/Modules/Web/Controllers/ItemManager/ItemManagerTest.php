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

namespace SP\Tests\Modules\Web\Controllers\ItemManager;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Characterisation tests for the ItemManager web area.
 */
#[Group('integration')]
final class ItemManagerTest extends IntegrationTestCase
{
    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isFilesEnabled'] = true;
        $configData['getAccountCount'] = 10;

        return $configData;
    }

    #[Test]
    #[BodyChecker('outputCheckerIndex')]
    public function index(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'itemManager/index'])
        );

        IntegrationTestCase::runApp($container);
    }

    private function outputCheckerIndex(string $output): void
    {
        self::assertStringContainsString('tblCategories', $output);
        self::assertStringContainsString('tblTags', $output);
        self::assertStringContainsString('tblClients', $output);
        self::assertStringContainsString('tblAccounts', $output);
    }
}
