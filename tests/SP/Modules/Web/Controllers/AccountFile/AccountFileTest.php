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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\File;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\FileDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AccountFileTest
 */
#[Group('integration')]
class AccountFileTest extends IntegrationTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteSingleFile()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'accountFile/delete/100'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"File deleted","data":null}');
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
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'accountFile/delete'],
                ['items' => [100, 200, 300]]
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Files deleted","data":null}');
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountFile/download/100'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString($fileData['content']);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerList')]
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountFile/list/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSearch')]
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountFile/search'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerUpload')]
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
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'accountFile/upload/100'], [], $files)
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerView')]
    public function view()
    {
        $fileDataGenerator = FileDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            File::class,
            new QueryResult([File::buildFromSimpleModel($fileDataGenerator->buildFileData())])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'accountFile/view/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isFilesEnabled'] = true;
        $configData['getFilesAllowedMime'] = ['text/plain'];
        $configData['getFilesAllowedSize'] = 1000;

        return $configData;
    }

    /**
     * @param string $output
     * @return void
     */

    private function outputCheckerUpload(string $output): void
    {
        $json = json_decode($output);

        self::assertEquals('OK', $json->status);
        self::assertEquals('File saved', $json->description);
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

        self::assertCount(2, $filter);
    }
}
