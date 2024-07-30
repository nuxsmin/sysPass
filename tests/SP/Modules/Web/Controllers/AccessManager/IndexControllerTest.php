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

namespace SP\Tests\Modules\Web\Controllers\AccessManager;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Bootstrap;
use SP\Mvc\View\OutputHandlerInterface;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IndexControllerTest
 */
#[Group('integration')]
class IndexControllerTest extends IntegrationTestCase
{
    /**
     * @throws Exception
     * @throws FileException
     * @throws InvalidClassException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testIndexAction()
    {
        $definitions = FileSystem::require(FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'web', 'module.php'));

        $definitions[OutputHandlerInterface::class] = $this->setupOutputHandler(static function (string $output) {
            $crawler = new Crawler($output);
            $filter = $crawler->filterXPath(
                '//div[contains(@id, \'tabs-\')]//form'
            )->extract(['id']);

            return !empty($output) && count($filter) === 5;
        });

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'accessManager/index'])
        );

        Bootstrap::run($container->get(BootstrapInterface::class), $container->get(ModuleInterface::class));
    }

    protected function getUserProfile(): ProfileData
    {
        return new ProfileData(
            [
                'mgmUsers' => true,
                'mgmGroups' => true,
                'mgmProfiles' => true,
                'mgmApiTokens' => true,
                'mgmPublicLinks' => true
            ]
        );
    }

    protected function getConfigData(): ConfigDataInterface|Stub
    {
        $configData = parent::getConfigData();
        $configData->method('isPublinksEnabled')->willReturn(true);

        return $configData;
    }
}
