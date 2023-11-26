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

namespace SP\Tests\Providers\Auth\Ldap;

use Laminas\Ldap\Ldap;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Providers\Auth\Ldap\LdapConnection;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapTypeEnum;
use SP\Tests\UnitaryTestCase;

use function PHPUnit\Framework\once;

/**
 * Class LdapConnectionTest
 *
 * @group unitary
 */
class LdapConnectionTest extends UnitaryTestCase
{
    private LdapConnection                      $ldapConnection;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private Ldap|MockObject                     $ldap;
    private LdapParams                          $ldapParams;

    /**
     * @throws LdapException
     */
    public function testCheckConnection(): void
    {
        $this->ldap
            ->expects(self::once())
            ->method('bind')
            ->with($this->ldapParams->getBindDn(), $this->ldapParams->getBindPass());

        $this->eventDispatcher
            ->expects(once())
            ->method('notify')
            ->with('ldap.check.connection');

        $this->ldapConnection->checkConnection();
    }

    /**
     * @throws LdapException
     */
    public function testCheckConnectionError(): void
    {
        $this->expectConnectError();

        $this->ldapConnection->checkConnection();
    }

    /**
     * @return void
     */
    private function expectConnectError(): void
    {
        $this->ldap
            ->expects(self::once())
            ->method('bind')
            ->with($this->ldapParams->getBindDn(), $this->ldapParams->getBindPass())
            ->willThrowException(new \Laminas\Ldap\Exception\LdapException());

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('notify')
            ->with(...self::withConsecutive(['exception'], ['ldap.bind']));

        $this->ldap
            ->expects(self::exactly(2))
            ->method('getLastError')
            ->willReturn('error');

        $errorCode = self::$faker->randomNumber();

        $this->ldap
            ->expects(self::once())
            ->method('getLastErrorCode')
            ->willReturn($errorCode);

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage('LDAP connection error');
        $this->expectExceptionCode($errorCode);
    }

    /**
     * @throws LdapException
     */
    public function testConnect(): void
    {
        $this->ldap
            ->expects(self::once())
            ->method('bind')
            ->with($this->ldapParams->getBindDn(), $this->ldapParams->getBindPass());

        $this->ldapConnection->connect();
    }

    /**
     * @throws LdapException
     */
    public function testConnectError(): void
    {
        $this->expectConnectError();

        $this->ldapConnection->connect();
    }

    /**
     * @throws LdapException
     */
    public function testMutate(): void
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            'cn=test1,dc=example,dc=com',
            self::$faker->password
        );

        $ldapConnection = $this->ldapConnection->mutate($ldapParams);

        $this->ldap
            ->expects(self::once())
            ->method('bind')
            ->with($ldapParams->getBindDn(), $ldapParams->getBindPass());

        $ldapConnection->connect();
    }

    /**
     * @throws LdapException
     */
    public function testCreateInstanceError(): void
    {
        $message = self::$faker->colorName;
        $errorCode = self::$faker->randomNumber();

        $this->ldap
            ->expects(self::once())
            ->method('setOptions')
            ->willThrowException(
                new \Laminas\Ldap\Exception\LdapException(null, $message, $errorCode)
            );

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($errorCode);

        new LdapConnection($this->ldap, $this->ldapParams, $this->eventDispatcher, true);
    }

    /**
     * @throws Exception
     * @throws ContextException
     * @throws LdapException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            'cn=test,dc=example,dc=com',
            self::$faker->password
        );
        $this->ldapParams->setPort(10389);
        $this->ldapParams->setGroup('cn=Test Group,ou=Groups,dc=example,dc=con');
        $this->ldapParams->setSearchBase('dc=example,dc=com');
        $this->ldapParams->setTlsEnabled(true);

        $this->ldap = $this->createMock(Ldap::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->ldapConnection =
            new LdapConnection($this->ldap, $this->ldapParams, $this->eventDispatcher, true);
    }

}
