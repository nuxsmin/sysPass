<?php
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

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Application;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Context\ContextException;
use SP\Core\Context\Stateless;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\User;
use SP\Tests\Generators\ConfigDataGenerator;

/**
 * A class to test using a mocked Dependency Injection Container
 */
abstract class UnitaryTestCase extends TestCase
{
    use PHPUnitHelper;

    protected static Generator $faker;

    protected readonly ConfigFileService|MockObject $config;
    protected readonly Application                  $application;
    protected readonly Context                      $context;
    protected readonly PathsContext $pathsContext;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$faker = Factory::create();
        self::setLocales();
    }

    private static function setLocales(): void
    {
        $lang = 'en_US.utf8';

        putenv('LANG=' . $lang);
        putenv('LANGUAGE=' . $lang);
        setlocale(LC_MESSAGES, $lang);
        setlocale(LC_ALL, $lang);

        bindtextdomain('messages', APP_PATH . DIRECTORY_SEPARATOR . 'locales');
        textdomain('messages');
        bind_textdomain_codeset('messages', 'UTF-8');
    }

    public static function getRandomNumbers(int $count): array
    {
        return array_map(static fn() => self::$faker->randomNumber(), range(0, $count - 1));
    }

    /**
     * @throws Exception
     * @throws ContextException
     * @throws SPException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->buildApplication();

        $this->getPathsContext();
    }

    /**
     * @return Application
     * @throws Exception
     * @throws ContextException
     * @throws SPException
     */
    private function buildApplication(): Application
    {
        $this->context = $this->buildContext();
        $this->config = $this->buildConfig();

        return new Application(
            $this->config,
            $this->createStub(EventDispatcherInterface::class),
            $this->context
        );
    }

    /**
     * @throws ContextException
     * @throws SPException
     */
    protected function buildContext(): Context
    {
        $context = new Stateless();
        $context->initialize();
        $context->setUserData($this->buildUserDataDto());
        $context->setUserProfile(new ProfileData());

        return $context;
    }

    /**
     * @return UserDto
     * @throws SPException
     */
    private function buildUserDataDto(): UserDto
    {
        return UserDto::fromModel(
            new User(
                [
                    'login' => self::$faker->userName,
                    'name' => self::$faker->userName,
                    'id' => self::$faker->randomNumber(2),
                    'userGroupId' => self::$faker->randomNumber(2),
                    'userProfileId' => self::$faker->randomNumber(2)
                ]
            )
        );
    }

    /**
     * @throws Exception
     */
    protected function buildConfig(): ConfigFileService
    {
        $configData = ConfigDataGenerator::factory()->buildConfigData();

        $config = $this->createMock(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        return $config;
    }
}
