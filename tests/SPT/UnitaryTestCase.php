<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Config\Ports\ConfigInterface;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\User\Services\UserLoginResponse;
use SPT\Generators\ConfigDataGenerator;

/**
 * A class to test using a mocked Dependency Injection Container
 */
abstract class UnitaryTestCase extends TestCase
{
    use PHPUnitHelper;

    protected static Generator $faker;
    protected ConfigInterface  $config;
    protected Application      $application;
    protected ContextInterface $context;

    public static function setUpBeforeClass(): void
    {
        self::$faker = Factory::create();

        parent::setUpBeforeClass();
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
        $userLogin = new UserLoginResponse();
        $userLogin
            ->setLogin(self::$faker->userName)
            ->setName(self::$faker->userName)
            ->setId(self::$faker->randomNumber(2))
            ->setUserGroupId(self::$faker->randomNumber(2));

        $this->context = new StatelessContext();
        $this->context->initialize();
        $this->context->setUserData($userLogin);
        $this->context->setUserProfile(new ProfileData());

        $configData = ConfigDataGenerator::factory()->buildConfigData();

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getConfigData')->willReturn($configData);

        return new Application(
            $config,
            $this->createStub(EventDispatcherInterface::class),
            $this->context
        );
    }
}
