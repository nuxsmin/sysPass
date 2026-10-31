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

namespace SP\Modules\Web\Controllers\PublicLink;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\PublicLinkDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the public link area.
 */
#[Group('integration')]
final class PublicLinkTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkSearch')]
    public function search(): void
    {
        $this->runRoute('get', 'publicLink/search');
    }

    #[Test]
    #[BodyChecker('checkCreate')]
    public function create(): void
    {
        $this->runRoute('get', 'publicLink/create');
    }

    #[Test]
    #[BodyChecker('checkEdit')]
    public function edit(): void
    {
        $this->runRoute('get', 'publicLink/edit/100', $this->serviceStub());
    }

    #[Test]
    #[BodyChecker('checkView')]
    public function view(): void
    {
        $this->runRoute('get', 'publicLink/view/100', $this->serviceStub());
    }

    #[Test]
    #[BodyChecker('checkCreated')]
    public function saveCreate(): void
    {
        $this->runRoute(
            'post',
            'publicLink/saveCreate',
            $this->noopService(),
            [],
            ['accountId' => 100]
        );
    }

    #[Test]
    #[BodyChecker('checkCreated')]
    public function saveCreateFromAccount(): void
    {
        $this->runRoute(
            'get',
            'publicLink/saveCreateFromAccount/100/1',
            $this->noopService()
        );
    }

    #[Test]
    #[BodyChecker('checkRefreshed')]
    public function refresh(): void
    {
        $this->runRoute('get', 'publicLink/refresh/100', $this->noopService());
    }

    #[Test]
    #[BodyChecker('checkDeleted')]
    public function deleteSingle(): void
    {
        $this->runRoute('get', 'publicLink/delete/100', $this->noopService());
    }

    #[Test]
    #[BodyChecker('checkDeletedMultiple')]
    public function deleteMultiple(): void
    {
        $this->runRoute('get', 'publicLink/delete', $this->noopService(), ['items' => [100, 200]]);
    }

    public function checkSearch(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertNotEmpty($response['data']['html']);
    }

    public function checkCreate(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertStringContainsString('saveCreate', $response['data']['html']);
    }

    public function checkEdit(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertStringContainsString('saveEdit/100', $response['data']['html']);
    }

    public function checkView(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertNotEmpty($response['data']['html']);
    }

    public function checkCreated(string $output): void
    {
        self::assertSame('Link created', $this->decode($output)['description']);
    }

    public function checkRefreshed(string $output): void
    {
        self::assertSame('Link updated', $this->decode($output)['description']);
    }

    public function checkDeleted(string $output): void
    {
        self::assertSame('Link deleted', $this->decode($output)['description']);
    }

    public function checkDeletedMultiple(string $output): void
    {
        self::assertSame('Links deleted', $this->decode($output)['description']);
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
        return [PublicLinkService::class => $this->createStub(PublicLinkService::class)];
    }

    private function serviceStub(): array
    {
        $service = $this->createStub(PublicLinkService::class);
        $service->method('getById')->willReturn(
            PublicLinkDataGenerator::factory()->buildPublicLinkList()
        );

        return [PublicLinkService::class => $service];
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
