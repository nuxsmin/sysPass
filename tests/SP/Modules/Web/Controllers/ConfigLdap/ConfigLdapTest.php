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

use ArrayIterator;
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
        $results = array_map(
            fn() => [
                'count' => 5,
                'dn' => self::$faker->userName(),
                'email' => [self::$faker->email(), self::$faker->email()],
                'member' => self::$faker->userName(),
                'memberUid' => self::$faker->uuid(),
                'uniqueMember' => self::$faker->uuid()
            ],
            range(0, 4)
        );

        $iterator = new ArrayIterator($results);

        $ldap = self::createStub(Ldap::class);
        $ldap->method('search')
            ->willReturn($iterator);

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    #[BodyChecker('outputCheckerCheckImport')]
    public function checkImport()
    {
        $results = array_map(
            fn() => [
                'count' => 5,
                'dn' => self::$faker->userName(),
                'email' => [self::$faker->email(), self::$faker->email()],
                'member' => self::$faker->userName(),
                'memberUid' => self::$faker->uuid(),
                'uniqueMember' => self::$faker->uuid()
            ],
            range(0, 4)
        );

        $iterator = new ArrayIterator($results);

        $ldap = self::createStub(Ldap::class);
        $ldap->method('search')
             ->willReturn($iterator);

        $data = [
            'ldap_server' => self::$faker->domainName(),
            'ldap_server_type' => 1,
            'ldap_binduser' => self::$faker->userName(),
            'ldap_bindpass' => self::$faker->password(),
            'ldap_base' => 'dc=test',
            'ldap_group' => 'cn=group,dc=test',
            'ldap_tls_enabled' => self::$faker->boolean(),
            'ldap_import_groups' => true,
            'ldap_import_filter' => ''
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configLdap/checkImport'], $data),
            [
                Ldap::class => $ldap
            ]
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    #[BodyChecker('outputCheckerCheckImportWithFilter')]
    public function checkImportWithFilter()
    {
        $results = array_map(
            fn() => [
                'count' => 5,
                'dn' => self::$faker->userName(),
                'email' => [self::$faker->email(), self::$faker->email()],
                'member' => self::$faker->userName(),
                'memberUid' => self::$faker->uuid(),
                'uniqueMember' => self::$faker->uuid()
            ],
            range(0, 4)
        );

        $iterator = new ArrayIterator($results);

        $ldap = self::createStub(Ldap::class);
        $ldap->method('search')
             ->willReturn($iterator);

        $data = [
            'ldap_server' => self::$faker->domainName(),
            'ldap_server_type' => 1,
            'ldap_binduser' => self::$faker->userName(),
            'ldap_bindpass' => self::$faker->password(),
            'ldap_base' => 'dc=test',
            'ldap_group' => 'cn=group,dc=test',
            'ldap_tls_enabled' => self::$faker->boolean(),
            'ldap_import_groups' => true,
            'ldap_import_filter' => 'a_filter'
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configLdap/checkImport'], $data),
            [
                Ldap::class => $ldap
            ]
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    #[BodyChecker('outputCheckerImport')]
    public function import()
    {
        $results = array_map(
            fn() => [
                'count' => 5,
                'dn' => self::$faker->userName(),
                'mail' => [self::$faker->email(), self::$faker->email()],
                'login' => self::$faker->colorName(),
                'username' => self::$faker->userName(),
                'member' => self::$faker->userName(),
                'memberUid' => self::$faker->uuid(),
                'uniqueMember' => self::$faker->uuid()
            ],
            range(0, 4)
        );

        $iterator = new ArrayIterator($results);

        $ldap = self::createStub(Ldap::class);
        $ldap->method('search')
             ->willReturn($iterator);

        $data = [
            'ldap_server' => self::$faker->domainName(),
            'ldap_server_type' => 1,
            'ldap_binduser' => self::$faker->userName(),
            'ldap_bindpass' => self::$faker->password(),
            'ldap_base' => 'dc=test',
            'ldap_group' => 'cn=group,dc=test',
            'ldap_tls_enabled' => self::$faker->boolean(),
            'ldap_import_groups' => true,
            'ldap_import_filter' => 'a_filter',
            'ldap_defaultgroup' => 100,
            'ldap_defaultprofile' => 200,
            'ldap_login_attribute' => 'login',
            'ldap_username_attribute' => 'username',
            'ldap_groupname_attribute' => 'dn',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configLdap/import'], $data),
            [
                Ldap::class => $ldap
            ]
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function save()
    {
        $data = [
            'ldap_enabled' => true,
            'ldap_server' => self::$faker->domainName(),
            'ldap_server_type' => 1,
            'ldap_binduser' => self::$faker->userName(),
            'ldap_bindpass' => self::$faker->password(),
            'ldap_base' => 'dc=test',
            'ldap_group' => 'cn=group,dc=test',
            'ldap_tls_enabled' => self::$faker->boolean(),
            'ldap_defaultgroup' => 100,
            'ldap_defaultprofile' => 200,
            'ldap_filter_user_object' => 'a_filter',
            'ldap_filter_group_object' => 'a_filter',
            'ldap_filter_user_attributes' => 'a_filter',
            'ldap_filter_group_attributes' => 'a_filter',
            'ldap_database_enabled' => self::$faker->boolean(),
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configLdap/save'], $data)
        );

        $this->expectOutputString('{"status":"OK","description":"Configuration updated","data":null}');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function saveWithNoChanges()
    {
        $data = [
            'ldap_enabled' => false
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configLdap/save'], $data)
        );

        $this->expectOutputString('{"status":"OK","description":"Configuration updated","data":null}');

        IntegrationTestCase::runApp($container);
    }

    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isLdapEnabled'] = true;

        return $configData;
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
        self::assertEquals(['LDAP connection OK', 'Objects found: 5'], $json->description);
        self::assertCount(5, $json->data->items[0]->items);
        self::assertNotEmpty($json->data->items[0]->items[0]);
        self::assertEquals('person', $json->data->items[0]->type);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCheckImport(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->template);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]/table[@class="popup-data"]/tbody/tr[@id="ldap-results"]'
        )->extract(['id']);

        self::assertCount(1, $filter);
        self::assertEquals('OK', $json->status);
        self::assertEquals(['LDAP connection OK', 'Objects found: 10'], $json->description);
        self::assertCount(5, $json->data->items[0]->items);
        self::assertNotEmpty($json->data->items[0]->items[0]);
        self::assertEquals('person', $json->data->items[0]->type);
        self::assertCount(5, $json->data->items[1]->items);
        self::assertNotEmpty($json->data->items[1]->items[0]);
        self::assertEquals('group', $json->data->items[1]->type);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCheckImportWithFilter(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->template);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]/table[@class="popup-data"]/tbody/tr[@id="ldap-results"]'
        )->extract(['id']);

        self::assertCount(1, $filter);
        self::assertEquals('OK', $json->status);
        self::assertEquals(['LDAP connection OK', 'Objects found: 5'], $json->description);
        self::assertCount(5, $json->data->items[0]->items);
        self::assertNotEmpty($json->data->items[0]->items[0]);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerImport(string $output): void
    {
        $json = json_decode($output);

        self::assertEquals('OK', $json->status);
        self::assertEquals('LDAP users import finished', $json->description);
        self::assertEquals(['Imported users: 5 / 5', 'Errors: 0'], $json->data);
    }
}
