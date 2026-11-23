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

    #[Test]
    public function signedJsGroupsAndOutOfRangeGroupThroughDispatch(): void
    {
        // g=0 (vendor JS)
        $uri0 = new Uri('http://localhost/index.php');
        $uri0->addParams(['r' => 'resource/js', 'g' => 0]);
        $query0 = (string) parse_url($uri0->getUriSigned($this->passwordSalt), PHP_URL_QUERY);
        parse_str($query0, $params0);

        $c0 = $this->buildContainer(self::buildRequest('get', 'index.php', $params0, [], [], ['QUERY_STRING' => $query0]));
        ob_start();
        try {
            self::runApp($c0);
        } finally {
            $out0 = (string) ob_get_clean();
        }
        self::assertSame(200, $c0->get(Response::class)->code());
        self::assertStringContainsString('jQuery', $out0);

        // g=1 (app JS)
        $uri1 = new Uri('http://localhost/index.php');
        $uri1->addParams(['r' => 'resource/js', 'g' => 1]);
        $query1 = (string) parse_url($uri1->getUriSigned($this->passwordSalt), PHP_URL_QUERY);
        parse_str($query1, $params1);

        $c1 = $this->buildContainer(self::buildRequest('get', 'index.php', $params1, [], [], ['QUERY_STRING' => $query1]));
        ob_start();
        try {
            self::runApp($c1);
        } finally {
            $out1 = (string) ob_get_clean();
        }
        self::assertSame(200, $c1->get(Response::class)->code());
        self::assertStringContainsString('sysPass', $out1);

        // Out-of-range group g=99 returns empty 200 because ActionResponse::ok('') has string subject, not Closure
        $uri99 = new Uri('http://localhost/index.php');
        $uri99->addParams(['r' => 'resource/js', 'g' => 99]);
        $query99 = (string) parse_url($uri99->getUriSigned($this->passwordSalt), PHP_URL_QUERY);
        parse_str($query99, $params99);

        $c99 = $this->buildContainer(self::buildRequest('get', 'index.php', $params99, [], [], ['QUERY_STRING' => $query99]));
        ob_start();
        try {
            self::runApp($c99);
        } finally {
            $out99 = (string) ob_get_clean();
        }
        self::assertSame(200, $c99->get(Response::class)->code());
        self::assertSame('', $out99);
    }

    #[Test]
    public function layoutHelperSignedResourceLinksDispatchSuccessfully(): void
    {
        $indexContainer = $this->buildContainer(self::buildRequest('get', 'index.php', ['r' => 'index/index']));
        ob_start();
        try {
            self::runApp($indexContainer);
        } finally {
            $html = (string) ob_get_clean();
        }
        self::assertSame(200, $indexContainer->get(Response::class)->code());

        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
        $cssHrefs = $crawler->filterXPath('//link[@rel="stylesheet"]/@href')->each(static fn($n) => $n->text());
        $jsSrcs = $crawler->filterXPath('//script[@src]/@src')->each(static fn($n) => $n->text());

        self::assertNotEmpty($cssHrefs);
        self::assertNotEmpty($jsSrcs);

        foreach ([$cssHrefs[0], $jsSrcs[0]] as $resourceUrl) {
            $query = (string) parse_url($resourceUrl, PHP_URL_QUERY);
            parse_str($query, $params);
            $dispatchContainer = $this->buildContainer(
                self::buildRequest('get', 'index.php', $params, [], [], ['QUERY_STRING' => $query])
            );
            ob_start();
            try {
                self::runApp($dispatchContainer);
            } finally {
                $out = (string) ob_get_clean();
            }
            self::assertSame(200, $dispatchContainer->get(Response::class)->code());
            self::assertNotEmpty($out);
        }
    }

    #[Test]
    public function traversalBaseOrMissingFileInValidBaseFailsCallbackSafely(): void
    {
        // Signed traversal base b=../app/config resolves to empty base; missing file fails inside callback -> HTTP 500 empty body
        $uriTraversal = new Uri('http://localhost/index.php');
        $uriTraversal->addParams(['r' => 'resource/css', 'b' => '../app/config', 'f' => 'config.xml']);
        $qTraversal = (string) parse_url($uriTraversal->getUriSigned($this->passwordSalt), PHP_URL_QUERY);
        parse_str($qTraversal, $pTraversal);

        $cTraversal = $this->buildContainer(
            self::buildRequest('get', 'index.php', $pTraversal, [], [], ['QUERY_STRING' => $qTraversal])
        );
        ob_start();
        try {
            self::runApp($cTraversal);
        } finally {
            $outTraversal = (string) ob_get_clean();
        }
        self::assertSame(500, $cTraversal->get(Response::class)->code());
        self::assertStringNotContainsString('passwordSalt', $outTraversal);
        self::assertSame('', $outTraversal);

        // Signed request with valid allowed base (theme css dir) and nonexistent file -> HTTP 500 empty body
        $validCssBase = 'app/modules/web/themes/material-blue/css';
        $uriMissing = new Uri('http://localhost/index.php');
        $uriMissing->addParams(['r' => 'resource/css', 'b' => $validCssBase, 'f' => 'nonexistent-synthetic.css']);
        $qMissing = (string) parse_url($uriMissing->getUriSigned($this->passwordSalt), PHP_URL_QUERY);
        parse_str($qMissing, $pMissing);

        $cMissing = $this->buildContainer(
            self::buildRequest('get', 'index.php', $pMissing, [], [], ['QUERY_STRING' => $qMissing])
        );
        ob_start();
        try {
            self::runApp($cMissing);
        } finally {
            $outMissing = (string) ob_get_clean();
        }
        self::assertSame(500, $cMissing->get(Response::class)->code());
        self::assertSame('', $outMissing);
    }
}
