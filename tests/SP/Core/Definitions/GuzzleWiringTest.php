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

namespace SP\Tests\Core\Definitions;

use DI\ContainerBuilder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Domain\Config\Ports\ConfigDataInterface;

/**
 * Tests the HTTP client bindings used by the status controllers.
 */
#[Group('unitary')]
final class GuzzleWiringTest extends TestCase
{
    #[Test]
    public function containerResolvesGuzzleClientInterface(): void
    {
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isProxyEnabled')->willReturn(false);

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(
            DomainDefinitions::getDefinitions(),
            CoreDefinitions::getDefinitions(TEST_ROOT, 'test'),
            [ConfigDataInterface::class => $configData]
        );

        $client = $containerBuilder->build()->get(ClientInterface::class);

        self::assertInstanceOf(GuzzleClient::class, $client);
        self::assertInstanceOf(Psr18ClientInterface::class, $client);
    }
}
