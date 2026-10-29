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

namespace SP\Tests\Modules\Web\Controllers\UserSettingsGeneral;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\User\Ports\UserService;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Characterisation tests for the UserSettingsGeneral web area.
 */
#[Group('integration')]
final class UserSettingsGeneralTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('outputCheckerSave')]
    public function save(): void
    {
        $userService = $this->createStub(UserService::class);
        $userService->method('updatePreferencesById')->willReturn(1);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'userSettingsGeneral/save'],
                [
                    'userlang' => 'en_US',
                    'usertheme' => 'material-blue',
                    'resultsperpage' => 12,
                    'account_link' => 1,
                    'sort_views' => 1,
                    'top_navbar' => 1,
                    'optional_actions' => 1,
                    'resultsascards' => 1,
                    'check_notifications' => 1,
                    'show_account_search_filters' => 1,
                ]
            ),
            [UserService::class => $userService]
        );

        IntegrationTestCase::runApp($container);
    }

    private function outputCheckerSave(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertSame('Preferences updated', $json['description']);
    }
}
