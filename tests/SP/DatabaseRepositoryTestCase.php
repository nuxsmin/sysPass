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

namespace SP\Tests;

use Aura\SqlQuery\QueryFactory;
use SP\Core\Context\Stateless;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Infrastructure\Database\Database;

/**
 * Base class for repository tests that run real SQL against the test database.
 */
abstract class DatabaseRepositoryTestCase extends DatabaseTestCase
{
    protected DatabaseInterface          $db;
    protected Stateless                  $context;
    protected QueryFactory               $queryFactory;
    protected EventDispatcherInterface   $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        self::loadFixtures();

        $this->eventDispatcher = self::createStub(EventDispatcherInterface::class);
        $this->db = new Database(getDbHandler(), $this->eventDispatcher);
        $this->queryFactory = new QueryFactory('mysql');
        $this->context = new Stateless();
        $this->context->initialize();
        $this->context->setUserData(new UserDto(isAdminApp: true));
    }

    protected function asNonAdminUser(): void
    {
        $this->context->setUserData(new UserDto(isAdminApp: false));
    }

    protected function executeSql(string $sql): void
    {
        getDbHandler()->getConnection()->exec($sql);
    }
}
