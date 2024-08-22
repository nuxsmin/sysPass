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

namespace SP\Tests\Modules\Web\Controllers\AccountFile;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\File;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\Generators\FileDataGenerator;
use SP\Tests\IntegrationTestCase;
use SP\Tests\OutputChecker;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountFileTest
 */
#[Group('integration')]
class AccountFileTest extends IntegrationTestCase
{
    private array $moduleDefinitions;

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteSingleFile()
    {
        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountFile/delete/100'])
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"File deleted","data":[],"messages":[]}'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteMultipleFiles()
    {
        $this->databaseQueryResolver = function (QueryData $queryData): QueryResult {
            /** @noinspection SqlWithoutWhere */
            if (str_starts_with($queryData->getQuery()->getStatement(), 'DELETE FROM `AccountFile`')) {
                return new QueryResult([], 3);
            }

            return new QueryResult();
        };

        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountFile/delete'], ['items' => [100, 200, 300]])
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Files deleted","data":[],"messages":[]}'
        );
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function downloadFile()
    {
        $fileData = FileDataGenerator::factory()->buildFileData();

        $this->addDatabaseMapperResolver(
            File::class,
            new QueryResult(
                [File::buildFromSimpleModel($fileData)]
            )
        );

        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountFile/download/100'])
        );

        $this->runApp($container);

        $this->expectOutputString($fileData['content']);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerList')]
    public function listFiles()
    {
        $fileDataGenerator = FileDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            File::class,
            new QueryResult(
                [
                    File::buildFromSimpleModel($fileDataGenerator->buildFileData()),
                    File::buildFromSimpleModel($fileDataGenerator->buildFileData())
                ]
            )
        );

        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountFile/list/100'])
        );

        $this->runApp($container);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerSearch')]
    public function search()
    {
        $fileDataGenerator = FileDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            File::class,
            QueryResult::withTotalNumRows(
                [
                    File::buildFromSimpleModel($fileDataGenerator->buildFileData()),
                    File::buildFromSimpleModel($fileDataGenerator->buildFileData())
                ],
                2
            )
        );

        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountFile/search'])
        );

        $this->runApp($container);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerUpload')]
    public function upload()
    {
        $file = sprintf('%s.txt', self::$faker->filePath());

        file_put_contents($file, self::$faker->text());

        $files = [
            'inFile' => [
                'name' => self::$faker->name(),
                'tmp_name' => $file,
                'size' => filesize($file),
                'type' => 'text/plain'
            ]
        ];

        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountFile/upload/100'], [], $files)
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"File saved","data":[],"messages":[]}'
        );
    }

    /**
     * @param string $output
     * @return void
     */

    private function outputCheckerUpload(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath('//table/tbody//tr[string-length(@data-item-id) > 0]')
                          ->extract(['class']);

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[OutputChecker('outputCheckerView')]
    public function view()
    {
        $fileDataGenerator = FileDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            File::class,
            new QueryResult([File::buildFromSimpleModel($fileDataGenerator->buildFileData())])
        );

        $container = $this->buildContainer(
            $this->moduleDefinitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accountFile/view/100'])
        );

        $this->runApp($container);
    }

    protected function getConfigData(): ConfigDataInterface|Stub
    {
        $configData = parent::getConfigData();
        $configData->method('isFilesEnabled')->willReturn(true);
        $configData->method('getFilesAllowedMime')->willReturn(['text/plain']);
        $configData->method('getFilesAllowedSize')->willReturn(1000);

        return $configData;
    }

    /**
     * @throws FileException
     * @throws InvalidClassException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleDefinitions = $this->getModuleDefinitions();
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerSearch(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath('//table/tbody//tr[string-length(@data-item-id) > 0]')
                          ->extract(['class']);

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerView(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath('//img|//div[@class="title"]')->count();

        self::assertNotEmpty($output);
        self::assertEquals(2, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerList(string $output): void
    {
        $crawler = new Crawler($output);
        $filter = $crawler->filterXPath(
            '//div[@id="files-wrap"]/ul//li'
        )->extract(['class']);

        self::assertNotEmpty($output);
        self::assertCount(2, $filter);
    }
}
