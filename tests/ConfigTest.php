<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;

/**
 * Class ConfigTest
 *
 * Test de integración para comprobar el funcionamiento de la clase SP\Config\Config y sus utilidades
 *
 * @package SP\Tests
 */
class ConfigTest extends TestCase
{
    /**
     * @var Container
     */
    protected static $dic;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        self::$dic = setupContext();
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass()
    {
        @unlink(CONFIG_FILE);
    }

    /**
     * Comprobar la carga de la configuración
     *
     * @covers \SP\Config\ConfigUtil::checkConfigDir()
     * @covers \SP\Config\Config::loadConfigFromFile()
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testLoadClass()
    {
        $config = self::$dic->get(Config::class);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertFileExists(CONFIG_FILE);

        return $config;
    }

    /**
     * Comprobar que la configuración se guarda correctamente
     *
     * @depends testLoadClass
     * @param Config $config
     */
    public function testSaveConfig($config)
    {
        $config->saveConfig(new ConfigData(), false);

        $this->assertFileExists(CONFIG_FILE);
    }


    /**
     * Comprobar la carga de la configuración en el contexto
     *
     * @depends testLoadClass
     * @param Config $config
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testLoadConfig($config)
    {
        $context = self::$dic->get(ContextInterface::class);

        $config->loadConfig($context);

        $this->assertInstanceOf(ConfigData::class, $context->getConfig());
    }

    /**
     * Comprobar la actualización de la configuración
     *
     * @depends testLoadClass
     * @param Config $config
     */
    public function testUpdateConfig($config)
    {
        $config->updateConfig(new ConfigData());

        $this->assertEquals(Config::getTimeUpdated(), $config->getConfigData()->getConfigDate());
    }

    /**
     * Comprobar la generación de una clave de actualización y que su longitud es correcta
     *
     * @depends testLoadClass
     * @param Config $config
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function testGenerateUpgradeKey($config)
    {
        $config->generateUpgradeKey();

        $this->assertEquals(32, strlen($config->getConfigData()->getUpgradeKey()));
    }
}
