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

namespace SP\Tests\Infrastructure\Account\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Models\File as FileModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Infrastructure\Account\Repositories\AccountFile;
use SP\Tests\DatabaseRepositoryTestCase;

#[Group('integration')]
final class AccountFileDbTest extends DatabaseRepositoryTestCase
{
    private AccountFile $accountFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountFile = new AccountFile($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function getByIdReturnsTheFileWithItsAccountAndClient(): void
    {
        $file = $this->accountFile->getById(1)->getData(FileModel::class);

        self::assertSame('sysPass.xml', $file->getName());
        self::assertSame('Google', $file->getAccountName());
        self::assertSame('Google', $file->getClientName());
    }

    #[Test]
    public function searchFiltersByTheAccountAndClientNames(): void
    {
        $result = $this->accountFile->search(new ItemSearchDto('Google'));

        self::assertSame(2, $result->getNumRows());
        self::assertSame(2, $result->getTotalNumRows());
    }
}