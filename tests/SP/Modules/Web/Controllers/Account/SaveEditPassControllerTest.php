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
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Class SaveCreateControllerTest
 */
#[Group('integration')]
class SaveEditPassControllerTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws InvalidClassException
     * @throws FileException
     */
    public function testSaveEditPassAction()
    {
        $accountDataGenerator = AccountDataGenerator::factory();

        $this->addDatabaseResolver(
            Account::class,
            new QueryResult([$accountDataGenerator->buildAccount()])
        );

        $this->addDatabaseResolver(
            AccountView::class,
            new QueryResult([$accountDataGenerator->buildAccountDataView()])
        );

        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $definitions = $this->getModuleDefinitions();
        $definitions[ConfigService::class] = $configService;

        $account = $accountDataGenerator->buildAccount();

        $paramsPost = [
            'password' => $account->getPass(),
            'password_repeat' => $account->getPass(),
        ];

        $accountId = self::$faker->randomNumber(3);

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveEditPass/' . $accountId], $paramsPost)
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Password updated","data":{"itemId":' . $accountId .
            ',"nextAction":"3"},"messages":[]}'
        );
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
