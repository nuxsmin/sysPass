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

namespace SP\Tests\Modules\Web\Controllers\ConfigWiki;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Characterisation tests for the ConfigWiki web area.
 */
#[Group('integration')]
final class ConfigWikiTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('outputCheckerSave')]
    public function save(): void
    {
        $this->runRoute('post', 'configWiki/save', [
            'wiki_enabled' => 1,
            'wiki_searchurl' => 'https://example.com/search',
            'wiki_pageurl' => 'https://example.com/page',
            'wiki_filter' => 'filter1,filter2',
        ]);
    }

    #[Test]
    #[BodyChecker('outputCheckerMissingParams')]
    public function saveMissingParams(): void
    {
        $this->runRoute('post', 'configWiki/save', ['wiki_enabled' => 1]);
    }

    #[Test]
    #[BodyChecker('outputCheckerNoChanges')]
    public function saveNoChanges(): void
    {
        $this->runRoute('post', 'configWiki/save', []);
    }

    private function outputCheckerSave(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertSame('Configuration updated', $json['description']);
    }

    private function outputCheckerMissingParams(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Missing Wiki parameters', $json['description']);
    }

    private function outputCheckerNoChanges(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertSame('No changes', $json['description']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(string $method, string $route, array $paramsPost): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route], $paramsPost)
        );

        IntegrationTestCase::runApp($container);
    }
}
