<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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


use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use SP\Config\Config;
use SP\Core\Application;
use SP\Core\Context\StatelessContext;
use SP\Core\Events\EventDispatcher;
use SP\Services\Config\ConfigBackupService;
use SP\Services\User\UserLoginResponse;
use SP\Storage\File\FileCache;
use SP\Storage\File\XmlHandler;

/**
 * A class to test using a mocked Dependency Injection Container
 */
abstract class UnitaryTestCase extends TestCase
{
    protected static Generator $faker;
    protected Config           $config;
    protected Application      $application;

    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
        BypassFinals::setWhitelist([APP_ROOT.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'*']);

        self::$faker = Factory::create();

        parent::setUpBeforeClass();
    }

    /**
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Context\ContextException
     */
    protected function setUp(): void
    {
        $this->application = $this->mockApplication();
        $this->config = $this->application->getConfig();

        parent::setUp();
    }

    /**
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Context\ContextException
     */
    private function mockApplication(): Application
    {
        $userLogin = new UserLoginResponse();
        $userLogin->setLogin(self::$faker->userName);

        $context = new StatelessContext();
        $context->initialize();
        $context->setUserData($userLogin);

        $config = new Config(
            $this->createStub(XmlHandler::class),
            $this->createStub(FileCache::class),
            $context,
            $this->createStub(ConfigBackupService::class)
        );

        return new Application($config, $this->createStub(EventDispatcher::class), $context);
    }
}