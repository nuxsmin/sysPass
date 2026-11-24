<?php
declare(strict_types=1);

namespace SP\Tests\Infrastructure\Database;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Tests\DatabaseTrait;
use function SP\Tests\getDbHandler;

#[Group('integration')]
final class DatabaseFixtureLockTest extends TestCase
{
    use DatabaseTrait;

    #[Test]
    public function fixtureReloadReleasesLocksHeldByUnreachableRequestCycles(): void
    {
        self::loadFixtures();
        $connection = getDbHandler()->getConnection();
        $connection->beginTransaction();
        $connection->query("SELECT value FROM Config WHERE parameter = 'masterPwd' LOCK IN SHARE MODE")->fetchColumn();

        $request = new class ($connection) {
            public mixed $cycle;

            public function __construct(public PDO $connection)
            {
                $this->cycle = $this;
            }
        };
        $reference = \WeakReference::create($request);
        unset($request, $connection);

        self::loadFixtures();

        self::assertNull($reference->get());
        self::assertGreaterThan(0, self::getRowCount('Config'));
    }
}
