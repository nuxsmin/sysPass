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

namespace SP\Tests\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\TestCase;
use SP\Core\Crypt\Vault;
use SP\Util\PasswordUtil;

/**
 * Class VaultTest
 *
 * @package SP\Tests
 */
class VaultTest extends TestCase
{
    /**
     * @var string
     */
    private $key;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws EnvironmentIsBrokenException
     */
    protected function setUp()
    {
        $this->key = PasswordUtil::generateRandomBytes();
    }

    /**
     * @throws CryptoException
     */
    public function testGetData()
    {
        $vault = new Vault();
        $vault->saveData('prueba', $this->key);
        $this->assertEquals('prueba', $vault->getData($this->key));

        $randomData = PasswordUtil::generateRandomBytes();

        $vault = new Vault();
        $vault->saveData($randomData, $this->key);
        $this->assertEquals($randomData, $vault->getData($this->key));
    }


    /**
     * @throws CryptoException
     */
    public function testGetTimeSet()
    {
        $vault = new Vault();
        $vault->saveData('test', $this->key);
        $this->assertTrue($vault->getTimeSet() !== 0);
    }

    /**
     * @throws CryptoException
     */
    public function testReKey()
    {
        $vault = new Vault();
        $vault->saveData('prueba', $this->key);

        $this->assertEquals('prueba', $vault->getData($this->key));

        $vault->reKey(1234, $this->key);

        $this->assertEquals('prueba', $vault->getData(1234));
    }

    /**
     * @throws CryptoException
     */
    public function testGetTimeUpdated()
    {
        $vault = new Vault();
        $vault->saveData('test', $this->key);

        $this->assertTrue($vault->getTimeUpdated() === 0);

        $vault->reKey(1234, $this->key);

        $this->assertTrue(is_int($vault->getTimeUpdated()));
        $this->assertTrue($vault->getTimeUpdated() > 0);
    }
}
