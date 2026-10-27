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

namespace SP\Modules\Web\Controllers\Items;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the items area.
 */
#[Group('integration')]
final class ItemsTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkNotifications')]
    public function notifications(): void
    {
        $this->runRoute('items/notifications');
    }

    #[Test]
    #[BodyChecker('checkAccountsUser')]
    public function accountsUser(): void
    {
        $this->runRoute('items/accountsUser');
    }

    #[Test]
    #[BodyChecker('checkCategories')]
    public function categories(): void
    {
        $this->runRoute('items/categories');
    }

    #[Test]
    #[BodyChecker('checkClients')]
    public function clients(): void
    {
        $this->runRoute('items/clients');
    }

    #[Test]
    #[BodyChecker('checkTags')]
    public function tags(): void
    {
        $this->runRoute('items/tags');
    }

    public function checkNotifications(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertSame(0, $response['data']['count']);
        self::assertSame([], $response['data']['notifications']);
    }

    public function checkAccountsUser(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertSame([], $response['data']);
    }

    public function checkCategories(string $output): void
    {
        self::assertSame([], $this->decode($output));
    }

    public function checkClients(string $output): void
    {
        self::assertSame([], $this->decode($output));
    }

    public function checkTags(string $output): void
    {
        self::assertSame([], $this->decode($output));
    }

    private function decode(string $output): array
    {
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            self::fail('Invalid JSON: '.substr($output, 0, 400));
        }

        return $decoded;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(string $route): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => $route])
        );

        IntegrationTestCase::runApp($container);
    }
}
