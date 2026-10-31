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

namespace SP\Modules\Web\Controllers\Notification;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\NotificationDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the notification area.
 */
#[Group('integration')]
final class NotificationTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'notification/index');
    }

    #[Test]
    #[BodyChecker('checkSearch')]
    public function search(): void
    {
        $this->runRoute('get', 'notification/search');
    }

    #[Test]
    #[BodyChecker('checkCreate')]
    public function create(): void
    {
        $this->runRoute('get', 'notification/create');
    }

    #[Test]
    #[BodyChecker('checkEdit')]
    public function edit(): void
    {
        $this->runRoute('get', 'notification/edit/100', $this->serviceStub());
    }

    #[Test]
    #[BodyChecker('checkView')]
    public function view(): void
    {
        $this->runRoute('get', 'notification/view/100', $this->serviceStub());
    }

    #[Test]
    #[BodyChecker('checkCreated')]
    public function saveCreate(): void
    {
        $this->runRoute(
            'post',
            'notification/saveCreate',
            [],
            [],
            [
                'notification_type' => 'notice',
                'notification_component' => 'test',
                'notification_description' => 'A notification',
                'notification_user' => 1,
            ]
        );
    }

    #[Test]
    #[BodyChecker('checkUpdated')]
    public function saveEdit(): void
    {
        $this->runRoute(
            'post',
            'notification/saveEdit/100',
            $this->noopService(),
            [],
            [
                'notification_type' => 'notice',
                'notification_component' => 'test',
                'notification_description' => 'A notification',
                'notification_user' => 1,
            ]
        );
    }

    #[Test]
    #[BodyChecker('checkChecked')]
    public function check(): void
    {
        $this->runRoute('get', 'notification/check/100', $this->noopService());
    }

    #[Test]
    #[BodyChecker('checkDeleted')]
    public function deleteSingle(): void
    {
        $this->runRoute('get', 'notification/delete/100', $this->noopService());
    }

    #[Test]
    #[BodyChecker('checkDeletedMultiple')]
    public function deleteMultiple(): void
    {
        $this->runRoute('get', 'notification/delete', $this->noopService(), ['items' => [100, 200]]);
    }

    public function checkIndex(string $output): void
    {
        self::assertStringContainsString('Notifications', $output);
    }

    public function checkSearch(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertNotEmpty($response['data']['html']);
    }

    public function checkCreate(string $output): void
    {
        self::assertStringContainsString('notification_component', $output);
    }

    public function checkEdit(string $output): void
    {
        self::assertStringContainsString('notification_component', $output);
    }

    public function checkView(string $output): void
    {
        self::assertStringContainsString('notification_component', $output);
    }

    public function checkCreated(string $output): void
    {
        self::assertSame('Notification created', $this->decode($output)['description']);
    }

    public function checkUpdated(string $output): void
    {
        self::assertSame('Notification updated', $this->decode($output)['description']);
    }

    public function checkChecked(string $output): void
    {
        self::assertSame('Notification read', $this->decode($output)['description']);
    }

    public function checkDeleted(string $output): void
    {
        self::assertSame('Notification deleted', $this->decode($output)['description']);
    }

    public function checkDeletedMultiple(string $output): void
    {
        self::assertSame('Notifications deleted', $this->decode($output)['description']);
    }

    private function decode(string $output): array
    {
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            self::fail('Invalid JSON: '.substr($output, 0, 400));
        }

        return $decoded;
    }

    private function noopService(): array
    {
        return [NotificationService::class => $this->createStub(NotificationService::class)];
    }

    private function serviceStub(): array
    {
        $service = $this->createStub(NotificationService::class);
        $service->method('getById')->willReturn(
            NotificationDataGenerator::factory()->buildNotification()
        );

        return [NotificationService::class => $service];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(
        string $method,
        string $route,
        array $definitions = [],
        array $paramsGet = [],
        array $paramsPost = []
    ): void {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                $method,
                'index.php',
                array_merge(['r' => $route], $paramsGet),
                $paramsPost
            ),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }
}
