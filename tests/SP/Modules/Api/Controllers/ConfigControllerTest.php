<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Modules\Api\Controllers;

use SP\Tests\Modules\Api\ApiTest;
use SP\Tests\WebTestCase;
use stdClass;

/**
 * Class ConfigControllerTest
 *
 * @package SP\Tests\Modules\Api\Controllers
 */
class ConfigControllerTest extends WebTestCase
{
    public function testExportAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'config/export',
            'params' => [
                'authToken' => ApiTest::API_TOKEN
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals('/var/www/html/sysPass/app/backup', $result->result->result);
        $this->assertEquals('Export process finished', $result->result->resultMessage);
        $this->assertEquals(0, $result->result->resultCode);
    }

    public function testBackupAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'config/backup',
            'params' => [
                'authToken' => ApiTest::API_TOKEN
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals(0, $result->result->itemId);
        $this->assertEquals('/var/www/html/sysPass/app/backup', $result->result->result);
        $this->assertEquals('Backup process finished', $result->result->resultMessage);
        $this->assertEquals(0, $result->result->resultCode);
    }
}
