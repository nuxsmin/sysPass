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

namespace SP\Tests\Modules\Web\Controllers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Mvc\View\OutputHandlerInterface;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class SearchControllerTest
 */
#[Group('integration')]
class SearchControllerTest extends IntegrationTestCase
{

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidClassException
     * @throws FileException
     * @throws ContainerExceptionInterface
     */
    public function testSearchAction()
    {
        $accountSearchView = AccountDataGenerator::factory()->buildAccountSearchView();

        $this->addDatabaseResolver(
            AccountSearchView::class,
            QueryResult::withTotalNumRows([$accountSearchView], 1)
        );

        $definitions = $this->getModuleDefinitions();

        $definitions[OutputHandlerInterface::class] = $this->setupOutputHandler(function (string $output): void {
            $crawler = new Crawler($output);
            $filter = $crawler->filterXPath(
                '//div[@id="res-content"]/div'
            )->extract(['id']);

            assert(!empty($output));
            assert(count($filter) === 4);

            $this->assertTrue(true);
        });

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest(
                'post',
                'index.php',
                ['r' => 'account/search'],
                ['search' => $accountSearchView->getName()]
            )
        );

        $this->expectOutputRegex(
            '/\{"status":0,"description":null,"data":\{"html":".*"\},"messages":\[\]\}/'
        );

        $this->runApp($container);
    }

    protected function getUserDataDto(): UserDataDto
    {
        $userPreferences = UserDataGenerator::factory()->buildUserPreferencesData()->mutate(['topNavbar' => true]);
        return parent::getUserDataDto()->set('preferences', $userPreferences);
    }
}
