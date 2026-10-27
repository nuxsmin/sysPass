<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
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
}
