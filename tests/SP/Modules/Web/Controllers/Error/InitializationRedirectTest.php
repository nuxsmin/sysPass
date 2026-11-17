<?php

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\Error;

use Klein\Response;
use Klein\Exceptions\LockedResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Tests\IntegrationTestCase;

#[Group('integration')]
final class InitializationRedirectTest extends IntegrationTestCase
{
    private bool $maintenance = false;

    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), ['isMaintenance' => $this->maintenance]);
    }

    #[Test]
    public function maintenanceErrorRouteRedirectsBackToItselfInTheHarness(): void
    {
        $this->maintenance = true;
        $container = $this->buildContainer(self::buildRequest('get', 'index.php', ['r' => 'error/maintenanceError']));
        ob_start();
        try {
            self::runApp($container);
            self::fail('Expected the in-process response to be locked');
        } catch (LockedResponseException) {
            // The initializer sends the redirect before bootstrap error handling completes.
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString(
            rawurlencode('error/maintenanceError'),
            (string) $container->get(Response::class)->headers()->get('Location')
        );
    }

    public static function databaseFailures(): array
    {
        return [
            'connection' => [false, true, 'error/databaseConnection'],
            'tables' => [true, false, 'error/databaseError'],
        ];
    }

    #[Test]
    #[DataProvider('databaseFailures')]
    public function databaseErrorTargetRendersWhenFailurePersists(bool $connection, bool $tables, string $route): void
    {
        $database = $this->createStub(DatabaseUtilInterface::class);
        $database->method('checkDatabaseConnection')->willReturn($connection);
        $database->method('checkDatabaseTables')->willReturn($tables);
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => $route]),
            [DatabaseUtilInterface::class => $database]
        );
        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }
        self::assertSame(200, $container->get(Response::class)->code());
        self::assertStringContainsString(
            $connection ? 'Error while checking the database' : 'Unable to connect to DB',
            $output
        );
    }

    #[Test]
    #[DataProvider('databaseFailures')]
    public function databaseFailureSetsErrorRedirect(bool $connection, bool $tables, string $expectedRoute): void
    {
        $database = $this->createStub(DatabaseUtilInterface::class);
        $database->method('checkDatabaseConnection')->willReturn($connection);
        $database->method('checkDatabaseTables')->willReturn($tables);
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/search']),
            [DatabaseUtilInterface::class => $database]
        );

        ob_start();
        try {
            self::runApp($container);
            self::fail('Expected the in-process response to be locked');
        } catch (LockedResponseException) {
            // The initializer sends the redirect before bootstrap error handling completes.
        } finally {
            ob_end_clean();
        }

        $location = (string) $container->get(Response::class)->headers()->get('Location');
        self::assertStringContainsString(rawurlencode($expectedRoute), $location);
    }
}
