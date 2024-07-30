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

namespace SP\Tests;

use DI\ContainerBuilder;
use Faker\Factory;
use Faker\Generator;
use Klein\Request;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Core\UI\ThemeContext;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\ArchiveHandler;
use SP\Mvc\View\OutputHandlerInterface;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\Generators\UserProfileDataGenerator;

use function DI\autowire;
use function DI\factory;

/**
 * Class IntegrationTestCase
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static Generator $faker;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$faker = Factory::create();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    protected function buildContainer(array $definitions = [], Request $request = null): ContainerInterface
    {
        $configData = $this->getConfigData();

        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        $databaseUtil = self::createStub(\SP\Infrastructure\Database\DatabaseUtil::class);
        $databaseUtil->method('checkDatabaseConnection')->willReturn(true);
        $databaseUtil->method('checkDatabaseTables')->willReturn(true);

        $database = self::createStub(DatabaseInterface::class);
        $database->method('runQuery')->willReturnCallback($this->getDatabaseReturn());

        $mockedDefinitions = [
            ConfigFileService::class => $configFileService,
            LdapConnectionInterface::class => self::createStub(LdapConnectionInterface::class),
            'backup.dbArchiveHandler' => self::createStub(ArchiveHandler::class),
            'backup.appArchiveHandler' => self::createStub(ArchiveHandler::class),
            \SP\Infrastructure\Database\DatabaseUtil::class => $databaseUtil,
            Context::class => $this->getContext(),
            MailService::class => self::createStub(MailService::class),
            DbStorageHandler::class => self::createStub(DbStorageHandler::class),
            DatabaseInterface::class => $database,
            ThemeContextInterface::class => autowire(ThemeContext::class)
                ->constructorParameter(
                    'basePath',
                    factory(static fn(PathsContext $p) => $p[Path::VIEW])
                )
                ->constructorParameter('baseUri', factory([UriContextInterface::class, 'getWebRoot']))
                ->constructorParameter('module', 'web')
                ->constructorParameter('name', 'material-blue')
        ];


        if ($request) {
            $definitions += [Request::class => $request];
        }

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(
            DomainDefinitions::getDefinitions(),
            CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'web'),
            $definitions,
            $mockedDefinitions
        );

        return $containerBuilder->build();
    }

    /**
     * @throws Exception
     */
    protected function getConfigData(): ConfigDataInterface|Stub
    {
        $configData = self::createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn(true);
        $configData->method('isMaintenance')->willReturn(false);
        $configData->method('getDbName')->willReturn(self::$faker->colorName());
        $configData->method('getPasswordSalt')->willReturn(self::$faker->sha1());

        return $configData;
    }

    protected function getDatabaseReturn(): callable
    {
        return fn() => new QueryResult();
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    protected function getContext(): SessionContext|Stub
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($this->getUserDataDto());
        $context->method('getUserProfile')->willReturn($this->getUserProfile());

        return $context;
    }

    /**
     * @return UserDataDto
     * @throws SPException
     */
    protected function getUserDataDto(): UserDataDto
    {
        return new UserDataDto(UserDataGenerator::factory()->buildUserData());
    }

    /**
     * @return ProfileData
     */
    protected function getUserProfile(): ProfileData
    {
        return UserProfileDataGenerator::factory()->buildProfileData();
    }

    protected function buildRequest(string $method, string $uri, array $params): Request
    {
        $server = array_merge(
            $_SERVER,
            [
                'REQUEST_METHOD' => strtoupper($method),
                'REQUEST_URI' => $uri,
                'HTTP_USER_AGENT' => self::$faker->userAgent()
                //'QUERY_STRING' => $query
            ]
        );

        return new Request(
            array_merge($_GET, $params),
            array_merge($_POST, $params),
            $_COOKIE,
            $server,
            $_FILES,
            null
        );
    }

    /**
     * @param callable $outputChecker
     * @return MockObject|OutputHandlerInterface
     * @throws Exception
     */
    protected function setupOutputHandler(callable $outputChecker): OutputHandlerInterface|MockObject
    {
        $outputHandler = $this->createMock(OutputHandlerInterface::class);
        $outputHandler->expects($this->once())
                      ->method('bufferedContent')
                      ->with(
                          self::callback(static function (callable $callback) use ($outputChecker) {
                              ob_start();
                              $callback();

                              return $outputChecker(ob_get_clean());
                          })
                      );

        return $outputHandler;
    }
}
