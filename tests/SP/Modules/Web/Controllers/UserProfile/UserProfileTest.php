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

namespace SP\Modules\Web\Controllers\UserProfile;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Models\UserProfile;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\UserProfileDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the UserProfile web area.
 */
#[Group('integration')]
class UserProfileTest extends IntegrationTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function create()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userProfile/create'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerDeleteMultiple')]
    public function deleteMultiple()
    {
        $this->databaseQueryResolver = fn($queryData) => new QueryResult(null, 3);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'userProfile/delete', 'items' => [100, 200, 300]]
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerDeleteSingle')]
    public function deleteSingle()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userProfile/delete/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function edit()
    {
        $this->addDatabaseMapperResolver(
            UserProfile::class,
            new QueryResult([UserProfileDataGenerator::factory()->buildUserProfileData()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userProfile/edit/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSaveCreate')]
    public function saveCreate()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'userProfile/saveCreate'],
                $this->buildFormData()
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSaveEdit')]
    public function saveEdit()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'userProfile/saveEdit/100'],
                $this->buildFormData()
            )
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSearch')]
    public function search()
    {
        $generator = UserProfileDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            UserProfile::class,
            QueryResult::withTotalNumRows(
                [
                    $generator->buildUserProfileData(),
                    $generator->buildUserProfileData()
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userProfile/search', 'search' => 'test'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function view()
    {
        $this->addDatabaseMapperResolver(
            UserProfile::class,
            new QueryResult([UserProfileDataGenerator::factory()->buildUserProfileData()])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'userProfile/view/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    private function buildFormData(): array
    {
        return [
            'profile_name' => self::$faker->name(),
            'profile_accadd' => self::$faker->boolean(),
            'profile_accview' => self::$faker->boolean(),
            'profile_accviewpass' => self::$faker->boolean(),
            'profile_accedit' => self::$faker->boolean(),
            'profile_accdel' => self::$faker->boolean(),
            'profile_users' => self::$faker->boolean(),
            'profile_groups' => self::$faker->boolean(),
            'profile_profiles' => self::$faker->boolean(),
            'profile_eventlog' => self::$faker->boolean(),
        ];
    }

    private function outputCheckerForm(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        $crawler = new Crawler($json['data']['html']);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmProfile"]//input[@name="profile_name"]'
        );

        self::assertCount(1, $filter);
    }

    private function outputCheckerDeleteMultiple(string $output): void
    {
        $this->assertSuccessDescription($output, 'Profiles deleted');
    }

    private function outputCheckerDeleteSingle(string $output): void
    {
        $this->assertSuccessDescription($output, 'Profile deleted');
    }

    private function outputCheckerSaveCreate(string $output): void
    {
        $this->assertSuccessDescription($output, 'Profile added');
    }

    private function outputCheckerSaveEdit(string $output): void
    {
        $this->assertSuccessDescription($output, 'Profile updated');
    }

    private function outputCheckerSearch(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertStringContainsString('tblProfiles', $json['data']['html']);
    }

    private function assertSuccessDescription(string $output, string $description): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertSame($description, $json['description']);
    }

    /**
     * Accepts both the legacy integer status and the ActionResponse status name.
     */
    private function decodeSuccessfulResponse(string $output): array
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertTrue(in_array($json['status'] ?? null, [0, 'OK'], true), $output);

        return $json;
    }
}
