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

namespace SP\Tests\Modules\Web\Controllers\AuthToken;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\File\FileException;
use SP\Tests\IntegrationTestCase;
use SP\Tests\OutputChecker;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AuthTokenTest
 */
#[Group('integration')]
class AuthTokenTest extends IntegrationTestCase
{
    private array $definitions;

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerCreate')]
    public function create()
    {
        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'authToken/create'])
        );

        $this->runApp($container);

        $this->expectOutputRegex('/\{"status":"OK","description":"","data":\{"html":".*"\}\}/');
    }

    /**
     * @throws FileException
     * @throws InvalidClassException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->definitions = $this->getModuleDefinitions();
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCreate(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmTokens"]//select|//input'
        )->extract(['_name']);

        self::assertNotEmpty($output);
        self::assertCount(5, $filter);
    }
}
