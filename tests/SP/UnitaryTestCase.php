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


use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use SP\Config\Config;
use SP\Core\Context\StatelessContext;
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

    public static function setUpBeforeClass(): void
    {
        self::$faker = Factory::create();

        parent::setUpBeforeClass();
    }

    /**
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Context\ContextException
     */
    protected function setUp(): void
    {
        $this->config = $this->getConfig();

        parent::setUp();
    }

    /**
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Context\ContextException
     */
    private function getConfig(): Config
    {
        $userLogin = new UserLoginResponse();
        $userLogin->setLogin(self::$faker->userName);

        $context = new StatelessContext();
        $context->initialize();
        $context->setUserData($userLogin);

        return new Config(
            $this->createStub(XmlHandler::class),
            $this->createStub(FileCache::class),
            $context,
            $this->createStub(ConfigBackupService::class)
        );
    }
}