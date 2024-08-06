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

namespace SP\Tests\Modules\Web\Controllers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Class SaveCopyControllerTest
 */
#[Group('integration')]
class SaveCopyControllerTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws InvalidClassException
     * @throws FileException
     */
    public function testSaveCopyAction()
    {
        $crypt = $this->createStub(CryptInterface::class);
        $crypt->method('decrypt')->willReturn('some_data');
        $crypt->method('encrypt')->willReturn('some_data');

        $definitions = $this->getModuleDefinitions();
        $definitions[CryptInterface::class] = $crypt;

        $account = AccountDataGenerator::factory()->buildAccount();

        $paramsPost = [
            'name' => $account->getName(),
            'login' => $account->getLogin(),
            'client_id' => $account->getClientId(),
            'category_id' => $account->getCategoryId(),
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
            'owner_id' => $account->getUserId(),
            'notes' => $account->getNotes(),
            'private_enabled' => $account->getIsPrivate(),
            'private_group_enabled' => $account->getIsPrivateGroup(),
            'password_date_expire_unix' => $account->getPassDate(),
            'parent_account_id' => $account->getParentId(),
            'main_usergroup_id' => $account->getUserGroupId(),
            'other_users_view_update' => 1,
            'other_users_view' => [1, 2, 3],
            'other_users_edit_update' => 1,
            'other_users_edit' => [4, 5, 6],
            'other_usergroups_view_update' => 1,
            'other_usergroups_view' => [8, 9, 10],
            'other_usergroups_edit_update' => 1,
            'other_usergroups_edit' => [11, 12, 13],
            'tags_update' => 1,
            'tags' => [15, 16, 17],
        ];

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveCopy'], $paramsPost)
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account created","data":{"itemId":100,"nextAction":""},"messages":[]}'
        );
    }

    protected function getDatabaseReturn(): callable
    {
        return function (QueryData $queryData): QueryResult {
            if (!empty($queryData->getMapClassName())) {
                $reflection = new ReflectionClass($queryData->getMapClassName());
                return new QueryResult([$reflection->newInstance()], 0, 100);
            }

            return new QueryResult([], 0, 100);
        };
    }

    protected function getContext(): SessionContext|Stub
    {
        $vault = self::createStub(VaultInterface::class);
        $vault->method('getData')
              ->willReturn('some_data');

        $context = parent::getContext();
        $context->method('getVault')->willReturn($vault);

        return $context;
    }
}
