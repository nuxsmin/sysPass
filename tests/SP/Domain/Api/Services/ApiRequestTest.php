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

namespace SP\Tests\Domain\Api\Services;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SP\Domain\Api\Services\ApiRequest;
use SP\Domain\Api\Services\ApiRequestException;
use SP\Tests\UnitaryTestCase;

/**
 * Class ApiRequestTest
 *
 */
#[Group('unitary')]
class ApiRequestTest extends UnitaryTestCase
{
    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testBuildFromRequest()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequest::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['method'], $out->getMethod());
        $this->assertEquals($jsonRpcRequest['id'], $out->getId());
        $this->assertEquals($jsonRpcRequest['params']['authToken'], $out->get('authToken'));
    }

    private static function buildJsonRpcRequest(): array
    {
        return [
            'jsonrpc' => '2.0',
            'method'  => self::$faker->colorName,
            'params'  => ['authToken' => self::$faker->password],
            'id'      => 1,
        ];
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testBuildFromRequestNoContent()
    {
        $stream = $this->getTempnam();

        file_put_contents($stream, '');

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid data');

        ApiRequest::buildFromRequest($stream);
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testBuildFromRequestInvalidJson()
    {
        $stream = $this->getTempnam();

        file_put_contents($stream, '{"test": "test"}');

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid format');

        ApiRequest::buildFromRequest($stream);
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[DataProvider('getJsonRpcProperty')]
    public function testBuildFromRequestWithoutProperty(string $property)
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        unset($jsonRpcRequest[$property]);

        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid format');

        ApiRequest::buildFromRequest($stream);
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testExists()
    {
        $stream = $this->getTempnam();

        file_put_contents($stream, json_encode(self::buildJsonRpcRequest(), JSON_THROW_ON_ERROR));

        $out = ApiRequest::buildFromRequest($stream);

        $this->assertTrue($out->exists('authToken'));
        $this->assertFalse($out->exists('test'));
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testGetId()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequest::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['id'], $out->getId());
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testGet()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequest::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['params']['authToken'], $out->get('authToken'));
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    public function testGetMethod()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequest::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['method'], $out->getMethod());
    }

    public static function getJsonRpcProperty(): array
    {
        return [
            ['jsonrpc'],
            ['method'],
            ['params'],
            ['id'],
        ];
    }

    /**
     * @return false|string
     */
    private function getTempnam(): string|false
    {
        return tempnam(sys_get_temp_dir(), (string)time());
    }
}
