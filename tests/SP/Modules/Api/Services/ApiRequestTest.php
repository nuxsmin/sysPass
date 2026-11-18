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

namespace SP\Tests\Modules\Api\Services;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Api\Exceptions\ApiRequestException;
use SP\Modules\Api\Adapters\ApiRequestAdapter;
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
    #[Test]
    public function testBuildFromRequest()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

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
    #[Test]
    public function testBuildFromRequestNoContent()
    {
        $stream = $this->getTempnam();

        file_put_contents($stream, '');

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid data');

        ApiRequestAdapter::buildFromRequest($stream);
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[Test]
    public function testBuildFromRequestInvalidJson()
    {
        $stream = $this->getTempnam();

        file_put_contents($stream, '{"test": "test"}');

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid format');

        ApiRequestAdapter::buildFromRequest($stream);
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[DataProvider('getJsonRpcProperty')]
    #[Test]
    public function testBuildFromRequestWithoutProperty(string $property)
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        unset($jsonRpcRequest[$property]);

        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid format');

        ApiRequestAdapter::buildFromRequest($stream);
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[Test]
    public function testExists()
    {
        $stream = $this->getTempnam();

        file_put_contents($stream, json_encode(self::buildJsonRpcRequest(), JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

        $this->assertTrue($out->exists('authToken'));
        $this->assertFalse($out->exists('test'));
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[Test]
    public function testGetId()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['id'], $out->getId());
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[Test]
    public function testGet()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['params']['authToken'], $out->get('authToken'));
    }

    /**
     * @throws ApiRequestException
     * @throws JsonException
     */
    #[Test]
    public function testGetMethod()
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

        $this->assertEquals($jsonRpcRequest['method'], $out->getMethod());
    }

    #[Test]
    public function testMethodStripsNonAlphaSlashCharacters(): void
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        $jsonRpcRequest['method'] = 'account/view2_?';
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

        $this->assertSame('account/view', $out->getMethod());
    }

    /**
     * Characterization of current ApiRequestAdapter::buildFromJson() ID parsing:
     * filter_var($data['id'], FILTER_VALIDATE_INT) ?: 1 coerces zero, non-numeric strings, and floats to 1,
     * while coercing numeric strings to integers and preserving non-zero integers.
     *
     * @param int|string|float $inputId
     */
    #[DataProvider('getCoercedOrPreservedIds')]
    #[Test]
    public function testBuildFromRequestCurrentIdHandling(int|string|float $inputId, int $expectedId): void
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        $jsonRpcRequest['id'] = $inputId;
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $out = ApiRequestAdapter::buildFromRequest($stream);

        $this->assertSame($expectedId, $out->getId());
    }

    #[Test]
    public function testBuildFromRequestNullIdThrowsInvalidRequest(): void
    {
        $stream = $this->getTempnam();

        $jsonRpcRequest = self::buildJsonRpcRequest();
        $jsonRpcRequest['id'] = null;
        file_put_contents($stream, json_encode($jsonRpcRequest, JSON_THROW_ON_ERROR));

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('Invalid format');

        ApiRequestAdapter::buildFromRequest($stream);
    }

    public static function getCoercedOrPreservedIds(): array
    {
        return [
            'zero coerced to 1' => [0, 1],
            'negative int preserved' => [-7, -7],
            'large int preserved' => [9007199254740991, 9007199254740991],
            'numeric string coerced to int' => ['42', 42],
            'string zero coerced to 1' => ['0', 1],
            'non-numeric string coerced to 1' => ['req-abc', 1],
            'float coerced to 1' => [1.5, 1],
        ];
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
