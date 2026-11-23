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

namespace SP\Tests\Core\Definitions;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SP\Core\Definitions\ContainerFactory;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Import\Ports\XmlFileService;
use SP\Domain\Import\Services\XmlFile;
use SP\Domain\Notification\Ports\MailerInterface;
use SP\Infrastructure\File\FileSystem;
use SplFileInfo;

#[Group('unitary')]
final class CompiledContainerTest extends TestCase
{
    private string $compilationPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compilationPath = sys_get_temp_dir()
                                 . DIRECTORY_SEPARATOR
                                 . 'syspass-compiled-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->compilationPath)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->compilationPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->compilationPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function compiledContainerBuildsWithLoggerDefaults(): void
    {
        $moduleDefinitions = FileSystem::require(
            FileSystem::buildPath(APP_ROOT, 'app', 'modules', 'web', 'module.php')
        );

        $container = ContainerFactory::createBuilder(
            APP_ROOT,
            'web',
            is_array($moduleDefinitions) ? $moduleDefinitions : [],
            $this->compilationPath
        )->build();

        self::assertFileExists(
            FileSystem::buildPath($this->compilationPath, 'CompiledContainer.php')
        );
        self::assertInstanceOf(XmlFile::class, $container->get(XmlFileService::class));
    }

    #[Test]
    public function compiledContainerBootsMailerWithoutMailServer(): void
    {
        $moduleDefinitions = FileSystem::require(
            FileSystem::buildPath(APP_ROOT, 'app', 'modules', 'web', 'module.php')
        );

        $configData = (new ConfigData())
            ->setInstalled(true)
            ->setMailServer(null)
            ->setMailFrom(null);

        $builder = ContainerFactory::createBuilder(
            APP_ROOT,
            'web',
            is_array($moduleDefinitions) ? $moduleDefinitions : [],
            $this->compilationPath
        );

        $builder->addDefinitions([ConfigDataInterface::class => $configData]);

        $container = $builder->build();

        self::assertInstanceOf(MailerInterface::class, $container->get(MailerInterface::class));
    }

    #[Test]
    public function moduleCompilationPathsDoNotCollide(): void
    {
        $cacheRoot = FileSystem::buildPath($this->compilationPath, 'cache');
        $runRoot = FileSystem::buildPath($this->compilationPath, 'run');

        $cliPath = $this->compileModule('cli', $cacheRoot, $runRoot);
        $cliContents = (string)file_get_contents(FileSystem::buildPath($cliPath, 'CompiledContainer.php'));

        $webPath = $this->compileModule('web', $cacheRoot, $runRoot);
        $webContents = (string)file_get_contents(FileSystem::buildPath($webPath, 'CompiledContainer.php'));

        self::assertNotSame($cliPath, $webPath);
        self::assertStringContainsString('SP\\Modules\\Cli\\Init', $cliContents);
        self::assertStringContainsString('SP\\Modules\\Web\\Init', $webContents);
        self::assertStringNotContainsString('SP\\Modules\\Web\\Init', $cliContents);

        $reusedCliPath = $this->compileModule('cli', $cacheRoot, $runRoot);

        self::assertSame($cliPath, $reusedCliPath);
        self::assertSame(
            $cliContents,
            (string)file_get_contents(FileSystem::buildPath($reusedCliPath, 'CompiledContainer.php'))
        );
    }

    private function compileModule(string $module, string $cacheRoot, string $runRoot): string
    {
        putenv('SP_COMPILE_MODULE=' . $module);
        putenv('SP_COMPILE_CACHE_ROOT=' . $cacheRoot);
        putenv('SP_COMPILE_RUN_ROOT=' . $runRoot);

        $command = escapeshellarg(PHP_BINARY)
                   . ' '
                   . escapeshellarg(FileSystem::buildPath(APP_ROOT, 'tests', 'Fixtures', 'compile-container.php'));

        $process = proc_open(
            $command,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        self::assertIsResource($process);

        $stdout = (string)stream_get_contents($pipes[1]);
        $stderr = (string)stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        putenv('SP_COMPILE_MODULE');
        putenv('SP_COMPILE_CACHE_ROOT');
        putenv('SP_COMPILE_RUN_ROOT');

        self::assertSame(0, $exitCode, $stderr);

        return trim($stdout);
    }
}
