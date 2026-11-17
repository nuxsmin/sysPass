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

namespace SP\Modules\Web\Controllers\Resource;

use Closure;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Providers\Uri;
use SP\Tests\IntegrationTestCase;
use Klein\Response;

/**
 * Integration tests for the resources area.
 */
#[Group('integration')]
final class ResourceTest extends IntegrationTestCase
{
    private const BASE_PATH = '/tmp/kilo/resources';

    public static function invalidSignatures(): array
    {
        return ['missing' => [null], 'altered' => ['invalid-signature']];
    }

    #[Test]
    public function signedDefaultCssIsDeliveredThroughDispatch(): void
    {
        $uri = new Uri('http://localhost/index.php');
        $uri->addParam('r', 'resource/css');
        $query = (string) parse_url($uri->getUriSigned($this->passwordSalt), PHP_URL_QUERY);
        parse_str($query, $params);
        $container = $this->buildContainer(self::buildRequest(
            'get', 'index.php', $params, [], [], ['QUERY_STRING' => $query]
        ));

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertSame(200, $container->get(Response::class)->code());
        self::assertStringContainsString('html,body,div,span,object,iframe', $output);
    }

    #[Test]
    #[DataProvider('invalidSignatures')]
    public function unsignedOrAlteredResourceRequestIsRejectedThroughDispatch(?string $signature): void
    {
        $params = ['r' => 'resource/css'];
        if ($signature !== null) {
            $params['h'] = $signature;
        }
        $container = $this->buildContainer(self::buildRequest(
            'get', 'index.php', $params, [], [], ['QUERY_STRING' => http_build_query($params)]
        ));

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertSame(500, $container->get(Response::class)->code());
        self::assertStringNotContainsString('body{}', $output);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!is_dir(self::BASE_PATH)) {
            mkdir(self::BASE_PATH, 0777, true);
        }

        file_put_contents(self::BASE_PATH.'/app.css', 'body{}');
        file_put_contents(self::BASE_PATH.'/app.js', 'console.log(1);');
    }

    #[Test]
    public function css(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'resource/css']),
            $this->definitions('app.css')
        );

        $response = $container->get(CssController::class)->cssAction();

        self::assertInstanceOf(Closure::class, $response->subject);
    }

    #[Test]
    public function js(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'resource/js']),
            $this->definitions('app.js')
        );

        $response = $container->get(JsController::class)->jsAction();

        self::assertInstanceOf(Closure::class, $response->subject);
    }

    private function definitions(string $file): array
    {
        $request = $this->createStub(RequestService::class);
        $request->method('analyzeString')->willReturnCallback(
            static fn(string $param, ?string $default = null): ?string => match ($param) {
                'f' => $file,
                'b' => self::BASE_PATH,
                default => $default,
            }
        );
        $request->method('analyzeInt')->willReturn(0);
        $request->method('resolveSecureAppPath')->willReturn(self::BASE_PATH);

        return [RequestService::class => $request];
    }
}
