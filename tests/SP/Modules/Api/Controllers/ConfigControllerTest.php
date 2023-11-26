<?php
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
use SP\Tests\Modules\Api\ApiTestCase;
use stdClass;

/**
 * Class ConfigControllerTest
 *
 * @package SP\Tests\Modules\Api\Controllers
 */
class ConfigControllerTest extends ApiTestCase
{
    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
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
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testExportActionCustomPath(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CONFIG_EXPORT_RUN,
            [
                'path' => TMP_PATH . '/export/custom/path'
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertEquals('Export process finished', $response->result->resultMessage);
        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNotEmpty($response->result->result->files->xml);
        $this->assertFileExists($response->result->result->files->xml);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testExportActionInvalidPath(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CONFIG_EXPORT_RUN,
            [
                'path' => '/export/custom/path'
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertStringContainsString('Unable to create the directory', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
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
        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNotEmpty($response->result->result->files->app);
        $this->assertNotEmpty($response->result->result->files->db);
        $this->assertFileExists($response->result->result->files->app);
        $this->assertFileExists($response->result->result->files->db);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testBackupActionInvalidPath(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CONFIG_BACKUP_RUN,
            [
                'path' => '/backup/custom/path'
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertStringContainsString('Unable to create the backups directory', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testBackupActionCustomPath(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CONFIG_BACKUP_RUN,
            [
                'path' => TMP_PATH . '/backup/custom/path'
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertEquals('Backup process finished', $response->result->resultMessage);
        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNotEmpty($response->result->result->files->app);
        $this->assertNotEmpty($response->result->result->files->db);
        $this->assertFileExists($response->result->result->files->app);
        $this->assertFileExists($response->result->result->files->db);
    }
}
