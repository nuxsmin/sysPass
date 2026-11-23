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

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[Group('unitary')]
final class LayerRulesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/syspass-layer-rules-' . uniqid('', true);
        mkdir($this->root . '/app/modules/web', 0777, true);
        mkdir($this->root . '/lib', 0777, true);
        mkdir($this->root . '/lib/SP/Domain', 0777, true);
        mkdir($this->root . '/lib/SP/Core/Definitions', 0777, true);
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    }

    #[Test]
    public function testStaticCallOnDomainServiceIsAViolation(): void
    {
        file_put_contents(
            $this->root . '/app/modules/web/Foo.php',
            <<<'PHP'
<?php
namespace SP\Modules\Web;

use SP\Domain\Tag\Services\Tag;
use SP\Domain\User\Services\UpdatedMasterPassException;

final class Foo
{
    public function run(): void
    {
        Tag::build();
        UpdatedMasterPassException::info('message');
    }
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::MODULES_CONCRETE_SERVICES];

        self::assertContains(
            'app/modules/web/Foo.php => SP\Domain\Tag\Services\Tag::build()',
            $violations
        );
        self::assertNotContains(
            'app/modules/web/Foo.php => SP\Domain\User\Services\UpdatedMasterPassException::info()',
            $violations
        );
    }

    #[Test]
    public function testInfrastructureImportOutsideTheCompositionRootIsAViolation(): void
    {
        file_put_contents(
            $this->root . '/lib/SP/Core/Foo.php',
            <<<'PHP'
<?php
namespace SP\Core;

use SP\Infrastructure\File\FileSystem;

final class Foo
{
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::CORE_INFRASTRUCTURE];

        self::assertContains(
            'lib/SP/Core/Foo.php => SP\Infrastructure\File\FileSystem',
            $violations
        );
    }

    #[Test]
    public function testInfrastructureImportInTheCompositionRootIsAllowed(): void
    {
        file_put_contents(
            $this->root . '/lib/SP/Core/Definitions/Foo.php',
            <<<'PHP'
<?php
namespace SP\Core\Definitions;

use SP\Infrastructure\File\FileSystem;

final class Foo
{
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::CORE_INFRASTRUCTURE];

        self::assertSame([], $violations);
    }

    #[Test]
    public function testModuleImportOutsideTheCompositionRootIsAViolation(): void
    {
        file_put_contents(
            $this->root . '/lib/SP/Core/Foo.php',
            <<<'PHP'
<?php
namespace SP\Core;

use SP\Modules\Web\Mvc\View\Template;

final class Foo
{
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::LIB_MODULES];

        self::assertContains('lib/SP/Core/Foo.php => SP\Modules', $violations);
    }

    #[Test]
    public function testModuleImportInTheCompositionRootIsAllowed(): void
    {
        file_put_contents(
            $this->root . '/lib/SP/Core/Definitions/Foo.php',
            <<<'PHP'
<?php
namespace SP\Core\Definitions;

use SP\Modules\Web\Mvc\View\Template;

final class Foo
{
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::LIB_MODULES];

        self::assertSame([], $violations);
    }

    #[Test]
    public function testStaticCallThroughImportAliasIsAViolation(): void
    {
        file_put_contents(
            $this->root . '/app/modules/web/Foo.php',
            <<<'PHP'
<?php
namespace SP\Modules\Web;

use SP\Domain\Tag\Services\Tag as TagService;

final class Foo
{
    public function run(): void
    {
        TagService::build();
    }
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::MODULES_CONCRETE_SERVICES];

        self::assertContains(
            'app/modules/web/Foo.php => SP\Domain\Tag\Services\Tag::build()',
            $violations
        );
    }

    #[Test]
    public function testTranslationMarkerInDomainIsAllowed(): void
    {
        file_put_contents(
            $this->root . '/lib/SP/Domain/Foo.php',
            <<<'PHP'
<?php
namespace SP\Domain;

use function SP\__u;

final class Foo
{
    public function run(): string
    {
        return __u('Untranslated key');
    }
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::DOMAIN_PRESENTATION_CALLS];

        self::assertSame([], $violations);
    }

    #[Test]
    public function testTranslationCallInDomainIsAViolation(): void
    {
        file_put_contents(
            $this->root . '/lib/SP/Domain/Foo.php',
            <<<'PHP'
<?php
namespace SP\Domain;

use function SP\__;

final class Foo
{
    public function run(): string
    {
        return __('Translated text');
    }
}
PHP
        );

        $violations = LayerRules::collect($this->root)[LayerRules::DOMAIN_PRESENTATION_CALLS];

        self::assertContains('lib/SP/Domain/Foo.php => __() x1', $violations);
    }
}
