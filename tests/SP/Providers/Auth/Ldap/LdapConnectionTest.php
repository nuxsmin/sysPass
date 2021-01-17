<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Providers\Auth\Ldap;

use PHPUnit\Framework\TestCase;
use SP\Core\Events\EventDispatcher;
use SP\Providers\Auth\Ldap\LdapConnection;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapTypeInterface;

/**
 * Class LdapConnectionTest
 *
 * @package SP\Tests\Providers\Auth\Ldap
 */
class LdapConnectionTest extends TestCase
{
    /**
     * @throws LdapException
     */
    public function testCheckParams()
    {
        $ldapConnection = $this->getLdapConnection();

        $ldapConnection->checkParams();

        $this->assertTrue(true);
    }

    /**
     * @param LdapParams|null $params
     *
     * @return LdapConnection
     */
    public function getLdapConnection(LdapParams $params = null)
    {
        $ev = new EventDispatcher();

        if ($params === null) {
            $params = new LdapParams();
            $params->setServer('test.example.com');
            $params->setPort(10389);
            $params->setBindDn('cn=test,dc=example,dc=com');
            $params->setBindPass('testpass');
            $params->setGroup('cn=Test Group,ou=Groups,dc=example,dc=con');
            $params->setSearchBase('dc=example,dc=com');
            $params->setTlsEnabled(true);
            $params->setType(LdapTypeInterface::LDAP_STD);
        }

        return new LdapConnection($params, $ev);
    }

    /**
     * @throws LdapException
     */
    public function testCheckParamsNoSearchBase()
    {
        $ldapConnection = $this->getLdapConnection();

        $params = $ldapConnection->getLdapParams();
        $params->setSearchBase('');

        $this->expectException(LdapException::class);
        $ldapConnection->checkParams();
    }

    /**
     * @throws LdapException
     */
    public function testCheckParamsNoServer()
    {
        $ldapConnection = $this->getLdapConnection();

        $params = $ldapConnection->getLdapParams();
        $params->setServer('');

        $this->expectException(LdapException::class);
        $ldapConnection->checkParams();
    }

    /**
     * @throws LdapException
     */
    public function testCheckParamsNoBindDn()
    {
        $ldapConnection = $this->getLdapConnection();

        $params = $ldapConnection->getLdapParams();
        $params->setBindDn('');

        $this->expectException(LdapException::class);
        $ldapConnection->checkParams();
    }

    public function testGetServerUri()
    {
        $ldapConnection = $this->getLdapConnection();

        $this->assertEquals('ldap://test.example.com:10389', $ldapConnection->getServerUri());
    }

    public function testGetServerUriNoSchema()
    {
        $ldapConnection = $this->getLdapConnection();

        $params = $ldapConnection->getLdapParams();
        $params->setServer('test.example.com');
        $params->setPort(389);

        $this->assertEquals('ldap://test.example.com', $ldapConnection->getServerUri());

        $params->setPort(10389);
        $this->assertEquals('ldap://test.example.com:10389', $ldapConnection->getServerUri());
    }

    public function testGetServerUriLdaps()
    {
        $ldapConnection = $this->getLdapConnection();

        $params = $ldapConnection->getLdapParams();
        $params->setServer('ldaps://test.example.com');
        $params->setPort(10636);

        $this->assertEquals('ldaps://test.example.com:10636', $ldapConnection->getServerUri());
    }
}
