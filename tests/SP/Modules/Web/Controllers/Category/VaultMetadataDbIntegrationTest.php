<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Tests\DatabaseIntegrationTestCase;

use function SP\Tests\getDbHandler;

/**
 * DB-backed HTTP characterization tests for Category, Client, Tag, CustomField, and ItemTrait:
 * - Hash=null duplicate-check bypass (`Client` persists normalized duplicate; `Category`/`Tag` hit DB `uk_*_01` -> HTTP 500 JSON; legacy fixture `Category` id=1 has MD5 hash so `'W.e.b'` persists before `'W_e_b'` collides)
 * - `ClientForm` reading `isglobal` instead of `isGlobal` (failing to persist `Client.isGlobal`)
 * - Missing try/catch on `Category\DeleteController` & `Client\DeleteController` returning HTTP 500 JSON on FK/not-found
 * - `Category\DeleteController` / `Client\DeleteController` batch delete with missing ID succeeds (`affectedNumRows > 0`)
 * - `Tag\DeleteController` succeeding on in-use tag via `AccountToTag` `ON DELETE CASCADE`
 * - `CustomField\SaveEditController` changing `moduleId` deletes old definition & cascades `CustomFieldData`
 * - `ItemTrait::updateCustomFieldsForItem()` calling `CustomFieldDataService::delete([$itemId], $moduleId)` on empty fields, which silently fails via `CustomFieldDataRepository::deleteBatch()` binding array `:itemIds` as `'Array'` (`0` rows deleted)
 */
#[Group('integration')]
final class VaultMetadataDbIntegrationTest extends DatabaseIntegrationTestCase
{
    #[Test]
    public function normalizedDuplicatesBypassFormHashCheckWithClientPersistingAndCategoryTagThrowing500(): void
    {
        $pdo = getDbHandler()->getConnection();

        // 1. Client has non-unique KEY uk_Client_01 (hash) and ClientForm reads 'isglobal' instead of 'isGlobal'
        $dupClient = $this->dispatch('post', 'client/saveCreate', [], [
            'name' => 'G.o.o.g.l.e',
            'description' => 'Normalized collision with Google (id=1)',
            'isGlobal' => 1,
        ]);

        $this->assertJsonOk($dupClient, 'Client added');
        self::assertSame(
            2,
            (int)$pdo->query("SELECT COUNT(*) FROM Client WHERE name IN ('Google', 'G.o.o.g.l.e')")->fetchColumn()
        );
        self::assertSame(
            0,
            (int)$this->fetchValue("SELECT isGlobal FROM Client WHERE name = 'G.o.o.g.l.e'")
        );

        // 2. Fixture Category id=1 ('Web') stores a legacy 32-char MD5 hash, whereas makeItemHash() computes 40-char SHA-1.
        // Thus 'W.e.b' persists first, then 'W_e_b' bypasses checkDuplicatedOnAdd() (hash=null) and hits uk_Category_01 -> 500 JSON.
        $firstWebNormalized = $this->dispatch('post', 'category/saveCreate', [], [
            'name' => 'W.e.b',
            'description' => 'Does not collide with legacy MD5 hash on fixture id=1',
        ]);
        $this->assertJsonOk($firstWebNormalized, 'Category added');

        $dupCat = $this->dispatch('post', 'category/saveCreate', [], [
            'name' => 'W_e_b',
            'description' => 'Normalized SHA-1 collision with W.e.b',
        ]);
        self::assertSame(500, $dupCat['code']);
        $dupCatJson = $this->decodeJson($dupCat['body']);
        self::assertSame('ERROR', $dupCatJson['status']);
        self::assertSame('Integrity constraint', $dupCatJson['description']);

        // 3. Fixture Tag id=1 ('www') also stores legacy 32-char MD5 hash; 'w.w.w' persists first, then 'w_w_w' hits uk_Tag_01
        // and Tag\SaveCreateController catches SPException -> 200 JSON ERROR
        $firstTagNormalized = $this->dispatch('post', 'tag/saveCreate', [], [
            'name' => 'w.w.w',
        ]);
        $this->assertJsonOk($firstTagNormalized, 'Tag added');

        $dupTag = $this->dispatch('post', 'tag/saveCreate', [], [
            'name' => 'w_w_w',
        ]);
        self::assertSame(200, $dupTag['code']);
        $dupTagJson = $this->decodeJson($dupTag['body']);
        self::assertSame('ERROR', $dupTagJson['status']);
        self::assertSame('Integrity constraint', $dupTagJson['description']);
    }

    #[Test]
    public function categoryAndClientDeleteControllersBubble500OnFkOrMissingIdWhereasInUseTagCascades(): void
    {
        $pdo = getDbHandler()->getConnection();

        // Category 1 ('Web') is referenced by Account 1 & 2; DeleteController bubbles ConstraintException -> 500 JSON
        $inUseCat = $this->dispatch('post', 'category/delete/1');
        self::assertSame(500, $inUseCat['code']);
        $inUseCatJson = $this->decodeJson($inUseCat['body']);
        self::assertSame('ERROR', $inUseCatJson['status']);
        self::assertSame('Integrity constraint', $inUseCatJson['description']);

        // Missing Client id=9999 -> NoSuchItemException bubbles -> 500 JSON
        $missingClient = $this->dispatch('post', 'client/delete/9999');
        self::assertSame(500, $missingClient['code']);
        $missingClientJson = $this->decodeJson($missingClient['body']);
        self::assertSame('ERROR', $missingClientJson['status']);
        self::assertSame('Client not found', $missingClientJson['description']);

        // Tag 1 ('www') is referenced by AccountToTag (1,1) and (2,1), yet ON DELETE CASCADE removes both
        $delTag = $this->dispatch('post', 'tag/delete/1');
        $this->assertJsonOk($delTag, 'Tag removed');
        self::assertFalse($pdo->query('SELECT id FROM Tag WHERE id = 1')->fetchColumn());
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountToTag WHERE tagId = 1')->fetchColumn());
    }

    #[Test]
    public function batchDeleteWithMissingIdsDeletesUnusedRowsWithoutDetectingPartialMismatch(): void
    {
        $pdo = getDbHandler()->getConnection();

        // Category 3 ('SSH') is unused (Categories 1 & 2 are referenced by Accounts); items=[3, 9999] deletes id=3 and returns 200 OK
        $mixedCat = $this->dispatch('post', 'category/delete', [], ['items' => ['3', '9999']]);
        $this->assertJsonOk($mixedCat, 'Categories deleted');
        self::assertFalse($pdo->query('SELECT id FROM Category WHERE id = 3')->fetchColumn());

        // Client 4 ('Amazon') is unused; items=[4, 9999] deletes id=4 and returns 200 OK
        $mixedClient = $this->dispatch('post', 'client/delete', [], ['items' => ['4', '9999']]);
        $this->assertJsonOk($mixedClient, 'Clients deleted');
        self::assertFalse($pdo->query('SELECT id FROM Client WHERE id = 4')->fetchColumn());
    }

    #[Test]
    public function customFieldModuleChangeReplacesDefinitionIdAndCascadesExistingData(): void
    {
        $pdo = getDbHandler()->getConnection();

        // Fixture CustomFieldDefinition id=2 ('RSA') has moduleId=101 (CATEGORY) and CustomFieldData (101, 1, 2)
        self::assertSame(
            1,
            (int)$pdo->query('SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = 101 AND itemId = 1 AND definitionId = 2')->fetchColumn()
        );

        $result = $this->dispatch('post', 'customField/saveEdit/2', [], [
            'name' => 'RSA Moved',
            'type' => 2,
            'module' => AclActionsInterface::ACCOUNT,
            'help' => 'Moved to accounts',
        ]);

        $this->assertJsonOk($result, 'Field updated');

        self::assertFalse($pdo->query('SELECT id FROM CustomFieldDefinition WHERE id = 2')->fetchColumn());
        self::assertSame(
            0,
            (int)$pdo->query('SELECT COUNT(*) FROM CustomFieldData WHERE definitionId = 2')->fetchColumn()
        );
        self::assertSame(
            AclActionsInterface::ACCOUNT,
            (int)$this->fetchValue("SELECT moduleId FROM CustomFieldDefinition WHERE name = 'RSA Moved'")
        );
    }

    /**
     * Characterization: ItemTrait::updateCustomFieldsForItem() calls CustomFieldDataService::delete([$itemId], $moduleId)
     * whenever any submitted custom field is empty, but CustomFieldDataRepository::deleteBatch() binds `:itemIds` as an
     * unexpanded array -> PHP Warning (`Array to string conversion`) -> `'Array'` -> `0` rows deleted, preserving `'keep-this-value'`.
     */
    #[Test]
    public function emptyCustomFieldInCategorySaveEditTriggersArrayToStringDeleteBatchNoop(): void
    {
        $pdo = getDbHandler()->getConnection();

        $warningSeen = false;
        set_error_handler(static function (int $severity, string $message) use (&$warningSeen): bool {
            if ($severity === E_WARNING && str_contains($message, 'Array to string conversion')) {
                $warningSeen = true;
                return true;
            }
            return false;
        });

        try {
            $result = $this->dispatch('post', 'category/saveEdit/1', [], [
                'name' => 'Web',
                'description' => 'Web sites',
                'customfield' => [
                    '2' => 'keep-this-value',
                    '3' => '',
                ],
            ]);
        } finally {
            restore_error_handler();
        }

        $this->assertJsonOk($result, 'Category updated');
        self::assertTrue($warningSeen);
        self::assertSame(
            'keep-this-value',
            $this->fetchValue('SELECT data FROM CustomFieldData WHERE moduleId = 101 AND itemId = 1 AND definitionId = 2')
        );
    }
}
