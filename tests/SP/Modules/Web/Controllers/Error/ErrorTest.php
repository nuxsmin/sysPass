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

namespace SP\Modules\Web\Controllers\Error;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the error area.
 */
#[Group('integration')]
final class ErrorTest extends IntegrationTestCase
{
    #[Test]
    public function index(): void
    {
        $this->checkPage(IndexController::class, 'indexAction');
    }

    #[Test]
    public function databaseConnection(): void
    {
        $this->checkPage(DatabaseConnectionController::class, 'databaseConnectionAction');
    }

    #[Test]
    public function databaseError(): void
    {
        $this->checkPage(DatabaseErrorController::class, 'databaseErrorAction');
    }

    #[Test]
    public function maintenanceError(): void
    {
        $this->checkPage(MaintenanceErrorController::class, 'maintenanceErrorAction');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkPage(string $controller, string $method): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'error/index'])
        );

        $response = $container->get($controller)->{$method}();

        self::assertSame(ResponseStatus::OK, $response->status);
        self::assertNotEmpty($response->subject);
    }
}
