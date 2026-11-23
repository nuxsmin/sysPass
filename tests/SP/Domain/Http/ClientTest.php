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

namespace SP\Tests\Domain\Http;

use Composer\InstalledVersions;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Http\Client;

/**
 * Tests for the Guzzle client configuration.
 */
#[Group('unitary')]
final class ClientTest extends TestCase
{
    #[Test]
    public function installedGuzzleIsVersion7(): void
    {
        self::assertStringStartsWith(
            '7.',
            (string)InstalledVersions::getPrettyVersion('guzzlehttp/guzzle')
        );
        self::assertSame(7, ClientInterface::MAJOR_VERSION);
    }

    #[Test]
    public function clientImplementsGuzzleAndPsr18Interfaces(): void
    {
        $client = new GuzzleClient(Client::getOptions($this->configData(false)));

        self::assertInstanceOf(ClientInterface::class, $client);
        self::assertInstanceOf(Psr18ClientInterface::class, $client);
    }

    #[Test]
    public function connectExceptionIsNotARequestException(): void
    {
        $exception = new ConnectException(
            'Connection refused',
            new Request('GET', 'https://example.com')
        );

        self::assertNotInstanceOf(RequestException::class, $exception);
        self::assertInstanceOf(NetworkExceptionInterface::class, $exception);
    }

    #[Test]
    public function getOptionsReturnsTimeoutAndProtocolVersion(): void
    {
        self::assertSame(
            ['timeout' => 10, 'version' => 1.1],
            Client::getOptions($this->configData(false))
        );
    }

    #[Test]
    public function getOptionsReturnsProxyAndAuthWhenEnabled(): void
    {
        $options = Client::getOptions($this->configData(true));

        self::assertSame('tcp://proxy.example.com:8080', $options['proxy']);
        self::assertSame(['proxy_user', 'proxy_pass'], $options['auth']);
    }

    private function configData(bool $proxyEnabled): ConfigDataInterface
    {
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isProxyEnabled')->willReturn($proxyEnabled);
        $configData->method('getProxyServer')->willReturn('proxy.example.com');
        $configData->method('getProxyPort')->willReturn(8080);
        $configData->method('getProxyUser')->willReturn('proxy_user');
        $configData->method('getProxyPass')->willReturn('proxy_pass');

        return $configData;
    }
}
