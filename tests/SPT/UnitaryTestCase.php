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

namespace SPT;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SP\Core\Application;
use SP\Core\Context\ContextException;
use SP\Core\Context\StatelessContext;
use SP\DataModel\ProfileData;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Models\User;
use SPT\Generators\ConfigDataGenerator;

/**
 * A class to test using a mocked Dependency Injection Container
 */
abstract class UnitaryTestCase extends TestCase
{
    use PHPUnitHelper;

    protected static Generator           $faker;
    protected readonly ConfigFileService $config;
    protected readonly Application       $application;
    protected readonly ContextInterface  $context;

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

        bindtextdomain('messages', LOCALES_PATH);
        textdomain('messages');
        bind_textdomain_codeset('messages', 'UTF-8');
    }

    public static function getRandomNumbers(int $count): array
    {
        return array_map(static fn() => self::$faker->randomNumber(), range(0, $count - 1));
    }

    /**
     * @throws ContextException
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->application = $this->mockApplication();
        $this->config = $this->application->getConfig();

        parent::setUp();
    }

    /**
     * @return Application
     * @throws ContextException|Exception
     */
    private function mockApplication(): Application
    {
        $this->context = new StatelessContext();
        $this->context->initialize();
        $this->context->setUserData($this->getUserDataDto());
        $this->context->setUserProfile(new ProfileData());

        return new Application(
            $this->getConfig(),
            $this->createStub(EventDispatcherInterface::class),
            $this->context
        );
    }

    /**
     * @return UserDataDto
     */
    private function getUserDataDto(): UserDataDto
    {
        return new UserDataDto(
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
    protected function getConfig(): ConfigFileService
    {
        $configData = ConfigDataGenerator::factory()->buildConfigData();

        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        return $config;
    }
}
