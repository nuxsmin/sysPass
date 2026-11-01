<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Modules\Api\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use JsonException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Infrastructure\File\FileSystem;
use SP\Tests\Modules\Api\ApiTestCase;
use stdClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Class ConfigControllerTest
 *
 * @package SP\Tests\Modules\Api\Controllers
 */
#[Group('integration')]
class ConfigControllerTest extends ApiTestCase
{
    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testExportAction(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CONFIG_EXPORT_RUN,
            []
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertEquals('Export process finished', $response->result->resultMessage);
        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNotEmpty($response->result->result->files->xml);
        $this->assertFileExists($response->result->result->files->xml);

        $this->assertExportResponseStaysInBackupRoot($response);
    }

    /**
     * The API no longer accepts a path, so a traversal value must be ignored
     * instead of creating or writing outside the configured backup directory.
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testExportActionIgnoresTraversalPath(): void
    {
        $escape = 'syspass-escape-' . uniqid('', true);

        $api = $this->callApi(
            AclActionsInterface::CONFIG_EXPORT_RUN,
            [
                'path' => '../' . $escape
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);

        $backupRoot = $this->assertExportResponseStaysInBackupRoot($response);

        $this->assertDirectoryDoesNotExist(dirname(REAL_APP_ROOT) . DIRECTORY_SEPARATOR . $escape);
        $this->assertDirectoryDoesNotExist(FileSystem::buildPath($backupRoot, '..', $escape));
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testExportActionIgnoresAbsolutePath(): void
    {
        $escape = FileSystem::buildPath(TMP_PATH, 'syspass-export-escape-' . uniqid('', true));

        $api = $this->callApi(
            AclActionsInterface::CONFIG_EXPORT_RUN,
            [
                'path' => $escape
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);

        $this->assertExportResponseStaysInBackupRoot($response);

        $this->assertDirectoryDoesNotExist($escape);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testExportActionIgnoresRelativePath(): void
    {
        $subdirectory = 'export-subdir-' . uniqid('', true);

        $api = $this->callApi(
            AclActionsInterface::CONFIG_EXPORT_RUN,
            [
                'path' => $subdirectory
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);

        $backupRoot = $this->assertExportResponseStaysInBackupRoot($response);

        $this->assertDirectoryDoesNotExist(FileSystem::buildPath($backupRoot, $subdirectory));
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testExportActionIgnoresSymlinkPath(): void
    {
        $backupRoot = $this->getBackupRoot();
        $outsidePath = FileSystem::buildPath(TMP_PATH, 'syspass-export-outside-' . uniqid('', true));
        $linkPath = FileSystem::buildPath($backupRoot, 'syspass-export-link-' . uniqid('', true));

        mkdir($outsidePath);
        symlink($outsidePath, $linkPath);

        try {
            $api = $this->callApi(
                AclActionsInterface::CONFIG_EXPORT_RUN,
                [
                    'path' => FileSystem::buildPath($linkPath, 'nested')
                ]
            );

            $response = self::processJsonResponse($api);

            $this->assertEquals(0, $response->result->resultCode);

            $this->assertExportResponseStaysInBackupRoot($response);

            $this->assertDirectoryDoesNotExist(FileSystem::buildPath($outsidePath, 'nested'));
            $this->assertSame([], glob(FileSystem::buildPath($outsidePath, '*')) ?: []);
        } finally {
            @unlink($linkPath);
            @rmdir($outsidePath);
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testBackupAction(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CONFIG_BACKUP_RUN,
            []
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertEquals('Backup process finished', $response->result->resultMessage);

        $backupRoot = $this->assertBackupResponseStaysInBackupRoot($response);

        $this->assertBackupFilesWrittenUnder($backupRoot);
    }

    /**
     * The API no longer accepts a path, so a traversal value must be ignored
     * instead of creating or writing outside the configured backup directory.
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testBackupActionIgnoresTraversalPath(): void
    {
        $escape = 'syspass-escape-' . uniqid('', true);

        $api = $this->callApi(
            AclActionsInterface::CONFIG_BACKUP_RUN,
            [
                'path' => '../' . $escape
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);

        $backupRoot = $this->assertBackupResponseStaysInBackupRoot($response);

        $this->assertDirectoryDoesNotExist(dirname(REAL_APP_ROOT) . DIRECTORY_SEPARATOR . $escape);
        $this->assertDirectoryDoesNotExist(FileSystem::buildPath($backupRoot, '..', $escape));
        $this->assertBackupFilesWrittenUnder($backupRoot);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testBackupActionIgnoresAbsolutePath(): void
    {
        $escape = FileSystem::buildPath(TMP_PATH, 'syspass-escape-' . uniqid('', true));

        $api = $this->callApi(
            AclActionsInterface::CONFIG_BACKUP_RUN,
            [
                'path' => $escape
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);

        $backupRoot = $this->assertBackupResponseStaysInBackupRoot($response);

        $this->assertDirectoryDoesNotExist($escape);
        $this->assertBackupFilesWrittenUnder($backupRoot);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testBackupActionIgnoresSymlinkPath(): void
    {
        $backupRoot = $this->getBackupRoot();
        $outsidePath = FileSystem::buildPath(TMP_PATH, 'syspass-outside-' . uniqid('', true));
        $linkPath = FileSystem::buildPath($backupRoot, 'syspass-link-' . uniqid('', true));

        mkdir($outsidePath);
        symlink($outsidePath, $linkPath);

        try {
            $api = $this->callApi(
                AclActionsInterface::CONFIG_BACKUP_RUN,
                [
                    'path' => FileSystem::buildPath($linkPath, 'nested')
                ]
            );

            $response = self::processJsonResponse($api);

            $this->assertEquals(0, $response->result->resultCode);

            $this->assertBackupResponseStaysInBackupRoot($response);

            $this->assertDirectoryDoesNotExist(FileSystem::buildPath($outsidePath, 'nested'));
            $this->assertSame([], glob(FileSystem::buildPath($outsidePath, '*')) ?: []);
            $this->assertBackupFilesWrittenUnder($backupRoot);
        } finally {
            @unlink($linkPath);
            @rmdir($outsidePath);
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[Test]
    public function testBackupActionIgnoresRelativePath(): void
    {
        $subdirectory = 'subdir-' . uniqid('', true);

        $api = $this->callApi(
            AclActionsInterface::CONFIG_BACKUP_RUN,
            [
                'path' => $subdirectory
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);

        $backupRoot = $this->assertBackupResponseStaysInBackupRoot($response);

        $this->assertDirectoryDoesNotExist(FileSystem::buildPath($backupRoot, $subdirectory));
        $this->assertBackupFilesWrittenUnder($backupRoot);
    }

    private function getBackupRoot(): string
    {
        return FileSystem::buildPath(REAL_APP_ROOT, 'app', 'backup');
    }

    private function assertExportResponseStaysInBackupRoot(stdClass $response): string
    {
        $backupRoot = $this->getBackupRoot();
        $file = $response->result->result->files->xml;

        $this->assertNotEmpty($file);
        $this->assertStringStartsWith($backupRoot . DIRECTORY_SEPARATOR, $file);
        $this->assertFileExists($file);

        return $backupRoot;
    }

    private function assertBackupResponseStaysInBackupRoot(stdClass $response): string
    {
        $backupRoot = $this->getBackupRoot();
        $files = $response->result->result->files;

        foreach (['app', 'db'] as $type) {
            $this->assertNotEmpty($files->$type);
            $this->assertStringStartsWith($backupRoot . DIRECTORY_SEPARATOR, $files->$type);
        }

        return $backupRoot;
    }

    private function assertBackupFilesWrittenUnder(string $backupRoot): void
    {
        $this->assertNotEmpty(glob(FileSystem::buildPath($backupRoot, 'sysPass_app-*.tar.gz')) ?: []);
        $this->assertNotEmpty(glob(FileSystem::buildPath($backupRoot, 'sysPass_db-*.tar.gz')) ?: []);
    }
}
