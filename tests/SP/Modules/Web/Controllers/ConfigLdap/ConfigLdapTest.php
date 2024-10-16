<?php
/**
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

namespace SP\Tests\Modules\Web\Controllers\ConfigLdap;

use Laminas\Ldap\Collection;
use Laminas\Ldap\Ldap;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ConfigLdapTest
 */
#[Group('integration')]
class ConfigLdapTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    #[BodyChecker('outputCheckerCheck')]
    public function check()
    {
        $collection = self::createStub(Collection::class);
        $collection->method('count')
                   ->willReturn(2);
        $collection->method('valid')
                   ->willReturn(true, true, false);
        $collection->method('current')
                   ->willReturn([
                                    'count' => self::$faker->randomNumber(2),
                                    'dn' => self::$faker->userName(),
                                    'email' => [self::$faker->email(), self::$faker->email()],
                                    'member' => self::$faker->userName(),
                                    'memberUid' => self::$faker->uuid(),
                                    'uniqueMember' => self::$faker->uuid()
                                ]);

        $ldap = self::createStub(Ldap::class);
        $ldap->method('search')
             ->willReturn($collection);

        $data = [
            'ldap_server' => self::$faker->domainName(),
            'ldap_server_type' => 1,
            'ldap_binduser' => self::$faker->userName(),
            'ldap_bindpass' => self::$faker->password(),
            'ldap_base' => 'dc=test',
            'ldap_group' => 'cn=group,dc=test',
            'ldap_tls_enabled' => self::$faker->boolean()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configLdap/check/false'], $data),
            [
                Ldap::class => $ldap
            ]
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCheck(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->template);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]/table[@class="popup-data"]/tbody/tr[@id="ldap-results"]'
        )->extract(['id']);

        self::assertCount(1, $filter);
        self::assertEquals('OK', $json->status);
        self::assertEquals(['LDAP connection OK', 'Objects found: 1'], $json->description);
        self::assertNotEmpty($json->data->items[0]->items[0]);
        self::assertEquals('person', $json->data->items[0]->type);
    }
}
