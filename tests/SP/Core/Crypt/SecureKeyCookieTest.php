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

namespace SP\Tests\SP\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use SP\Core\Crypt\SecureKeyCookie;
use SP\Http\Request;

/**
 * Class SecureKeyCookieTest
 *
 * @package SP\Tests\SP\Core\Crypt
 */
class SecureKeyCookieTest extends TestCase
{
    const FAKE_PROVIDERS = ['text', 'email', 'password', 'url', 'ipv4', 'ipv6', 'creditCardDetails'];

    /**
     * @var SecureKeyCookie
     */
    protected $cookie;

    public function testGetCookieData()
    {
        $faker = Factory::create();

        $_SERVER['HTTP_USER_AGENT'] = $faker->userAgent;

        $cypher = $this->cookie->getCypher();

        foreach (self::FAKE_PROVIDERS as $provider) {
            $text = $faker->$provider;

            if (!is_scalar($text)) {
                $text = serialize($text);
            }

            $data = $this->cookie->sign($text, $cypher);

            $this->assertNotEmpty($data);
            $this->assertContains(';', $data);
            $this->assertEquals($text, $this->cookie->getCookieData($data, $cypher));
        }
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     */
    public function testGetKey()
    {
        $_COOKIE[SecureKeyCookie::COOKIE_NAME] = $this->cookie->sign($this->cookie->generateSecuredData()->getSerialized(), $this->cookie->getCypher());

        $this->assertNotEmpty($this->cookie->getKey());
        $this->assertInstanceOf(Key::class, $this->cookie->getSecuredKey());
    }

    /**
     * testSaveKey
     */
    public function testSaveKey()
    {
        $this->assertTrue($this->cookie->saveKey());
        $this->assertInstanceOf(Key::class, $this->cookie->getSecuredKey());
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->cookie = SecureKeyCookie::factory(new Request(\Klein\Request::createFromGlobals()));
    }
}
