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

namespace SP\Tests\Services\Api;

use PHPUnit\Framework\TestCase;
use SP\Services\Api\ApiRequest;
use SP\Services\Api\ApiRequestException;
use function SP\Tests\getResource;

/**
 * Class ApiRequestTest
 *
 * @package SP\Tests\Services\Api
 */
class ApiRequestTest extends TestCase
{
    /**
     * @throws ApiRequestException
     */
    public function testGetRequestJsonData()
    {
        $apiRequest = new ApiRequest(getResource('json', 'account_search.json'));
        $this->assertEquals(10, $apiRequest->getId());
        $this->assertEquals('account/search', $apiRequest->getMethod());
        $this->assertEquals('2cee8b224f48e01ef48ac172e879cc7825800a9d7ce3b23783212f4758f1c146', $apiRequest->get('authToken'));
        $this->assertEquals('API', $apiRequest->get('text'));
        $this->assertEquals(5, $apiRequest->get('count'));
        $this->assertEquals(1, $apiRequest->get('clientId'));
        $this->assertEquals(1, $apiRequest->get('categoryId'));

    }

    /**
     * @throws ApiRequestException
     */
    public function testWrongJson()
    {
        $this->expectException(ApiRequestException::class);
        $this->expectExceptionCode(-32700);

        $wrongJson = '{abc}';
        new ApiRequest($wrongJson);
    }

    /**
     * testWrongJsonParams
     */
    public function testWrongJsonParams()
    {
        $this->checkJsonException('{"a": 1}');
        $this->checkJsonException('{"jsonrpc": 2.0}');
        $this->checkJsonException('{"jsonrpc": 2.0, "method": "account/search"}');
        $this->checkJsonException('{"jsonrpc": 2.0, "method": "account/search", "params": {}}');
        $this->checkJsonException('{"jsonrpc": 2.0, "method": "account/search", "params": {"authToken": "1"}}');
    }

    /**
     * @throws ApiRequestException
     */
    public function testFilterData()
    {
        $json = '{"jsonrpc": 2.0, "method": "&account/$(search)?!%()=?¿", "params": {"authToken": "1"}, "id": "10"}';

        $apiRequest = new ApiRequest($json);
        $this->assertEquals(10, $apiRequest->getId());
        $this->assertEquals('account/search', $apiRequest->getMethod());
    }

    /**
     * @param $json
     */
    private function checkJsonException($json)
    {
        try {
            new ApiRequest($json);

            $this->fail('No exception thrown');
        } catch (ApiRequestException $e) {
            $this->assertEquals(-32600, $e->getCode());
        }
    }
}
