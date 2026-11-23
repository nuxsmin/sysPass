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

namespace SP\Modules\Web\Controllers\User;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Models\User;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the User web area.
 */
#[Group('integration')]
final class UserTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function create(): void
    {
        $this->runRoute('get', 'user/create');
    }

    #[Test]
    #[BodyChecker('outputCheckerDeleteMultiple')]
    public function deleteMultiple(): void
    {
        $this->databaseQueryResolver = fn() => new QueryResult(null, 2);

        $this->runRoute('get', 'user/delete', ['items' => [100, 200]]);
    }

    #[Test]
    #[BodyChecker('outputCheckerDeleteSingle')]
    public function deleteSingle(): void
    {
        $this->runRoute('get', 'user/delete/100');
    }

    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function edit(): void
    {
        $this->addUserResolver();

        $this->runRoute('get', 'user/edit/100');
    }

    #[Test]
    #[BodyChecker('outputCheckerPassForm')]
    public function editPass(): void
    {
        $this->addUserResolver();

        $this->runRoute('get', 'user/editPass/100');
    }

    #[Test]
    #[BodyChecker('outputCheckerSaveCreate')]
    public function saveCreate(): void
    {
        $this->runRoute('post', 'user/saveCreate', [], $this->buildUserPayload());
    }

    #[Test]
    #[BodyChecker('outputCheckerSaveEdit')]
    public function saveEdit(): void
    {
        $this->runRoute('post', 'user/saveEdit/100', [], $this->buildUserPayload());
    }

    #[Test]
    #[BodyChecker('outputCheckerSaveEditPass')]
    public function saveEditPass(): void
    {
        $this->runRoute(
            'post',
            'user/saveEditPass/100',
            [],
            [
                'password' => 'new-password',
                'password_repeat' => 'new-password',
            ]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerSearch')]
    public function search(): void
    {
        $generator = UserDataGenerator::factory();

        $this->addDatabaseMapperResolver(
            User::class,
            QueryResult::withTotalNumRows(
                [
                    $generator->buildUserData(),
                    $generator->buildUserData(),
                ],
                2
            )
        );

        $this->runRoute('get', 'user/search', ['search' => 'test']);
    }

    #[Test]
    #[BodyChecker('outputCheckerForm')]
    public function view(): void
    {
        $this->addUserResolver();

        $this->runRoute('get', 'user/view/100');
    }

    private function addUserResolver(): void
    {
        $this->addDatabaseMapperResolver(
            User::class,
            new QueryResult([$this->buildUser()])
        );
    }

    private function buildUser(): User
    {
        return UserDataGenerator::factory()
                                ->buildUserData()
                                ->mutate(
                                    [
                                        'isLdap' => false,
                                        'isDisabled' => false,
                                        'isChangePass' => false,
                                    ]
                                );
    }

    private function buildUserPayload(): array
    {
        return [
            'name' => 'Test User',
            'login' => 'test.user',
            'email' => 'test.user@example.com',
            'usergroup_id' => 100,
            'userprofile_id' => 200,
            'isLdap' => 0,
            'password' => 'new-password',
            'password_repeat' => 'new-password',
        ];
    }

    private function outputCheckerForm(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        $crawler = new Crawler($json['data']['html']);
        $filter = $crawler->filterXPath('//div[@id="box-popup"]//form[@name="frmUsers"]//input[@name="name"]');

        self::assertCount(1, $filter);
    }

    private function outputCheckerPassForm(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        $crawler = new Crawler($json['data']['html']);
        $filter = $crawler->filterXPath('//div[@id="box-popup"]//form[@name="updUsrPass"]//input[@name="password"]');

        self::assertCount(1, $filter);
    }

    private function outputCheckerDeleteMultiple(string $output): void
    {
        $this->assertSuccessDescription($output, 'Users deleted');
    }

    private function outputCheckerDeleteSingle(string $output): void
    {
        $this->assertSuccessDescription($output, 'User deleted');
    }

    private function outputCheckerSaveCreate(string $output): void
    {
        $this->assertSuccessDescription($output, 'User added');
    }

    private function outputCheckerSaveEdit(string $output): void
    {
        $this->assertSuccessDescription($output, 'User updated');
    }

    private function outputCheckerSaveEditPass(string $output): void
    {
        $this->assertSuccessDescription($output, 'Password updated');
    }

    private function outputCheckerSearch(string $output): void
    {
        $json = $this->decodeSuccessfulResponse($output);

        self::assertStringContainsString('tblUsers', $json['data']['html']);
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(string $method, string $route, array $paramsGet = [], array $paramsPost = []): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                $method,
                'index.php',
                array_merge(['r' => $route], $paramsGet),
                $paramsPost
            )
        );

        IntegrationTestCase::runApp($container);
    }
}