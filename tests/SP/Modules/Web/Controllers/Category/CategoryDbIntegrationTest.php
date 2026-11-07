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

namespace SP\Tests\Modules\Web\Controllers\Category;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Tests\DatabaseIntegrationTestCase;

/**
 * DB-backed HTTP integration tests for the category area.
 */
#[Group('integration')]
final class CategoryDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const DENIED_MESSAGE = 'You don\'t have permission to do this operation';

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function viewRendersFixtureCategoryAndCustomFields(): void
    {
        $result = $this->dispatch('get', 'category/view/1');

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('Web', $html);
        self::assertStringContainsString('Web sites', $html);
        self::assertStringContainsString('RSA', $html);
        self::assertStringContainsString('Lorem ipsum dolor sit amet', $html);
        self::assertStringContainsString('frmCategories', $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function editRendersFixtureCategoryForm(): void
    {
        $result = $this->dispatch('get', 'category/edit/1');

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('Web', $html);
        self::assertStringContainsString('Web sites', $html);
        self::assertStringContainsString('category/saveEdit/1', $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEditPersistsCategoryAndCustomFieldChanges(): void
    {
        $result = $this->dispatch(
            'post',
            'category/saveEdit/1',
            [],
            [
                'name' => 'DB Category',
                'description' => 'Updated by integration test',
                'customfield' => ['2' => 'Updated RSA value'],
            ]
        );

        $this->assertJsonOk($result, 'Category updated');

        self::assertSame('DB Category', $this->fetchValue('SELECT name FROM Category WHERE id = 1'));
        self::assertSame(
            'Updated by integration test',
            $this->fetchValue('SELECT description FROM Category WHERE id = 1')
        );
        self::assertSame(
            'Updated RSA value',
            $this->fetchValue(
                'SELECT data FROM CustomFieldData WHERE moduleId = 101 AND itemId = 1 AND definitionId = 2'
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function viewIsDeniedWithoutCategoryViewAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'category/view/1',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::CATEGORY_VIEW)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function editIsDeniedWithoutCategoryEditAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'category/edit/1',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::CATEGORY_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEditIsDeniedWithoutCategoryEditAccessAndDoesNotMutateData(): void
    {
        $result = $this->dispatch(
            'post',
            'category/saveEdit/1',
            [],
            ['name' => 'Hacked', 'description' => 'Hacked'],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::CATEGORY_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);

        self::assertSame('Web', $this->fetchValue('SELECT name FROM Category WHERE id = 1'));
        self::assertSame('Web sites', $this->fetchValue('SELECT description FROM Category WHERE id = 1'));
    }
}
