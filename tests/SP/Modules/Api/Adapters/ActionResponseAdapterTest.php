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
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Modules\Api\Adapters;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Api\Adapters\ActionResponseAdapter;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
class ActionResponseAdapterTest extends UnitaryTestCase
{
    #[Test]
    public function toJsonRpcMapsTheLegacyResultEnvelope(): void
    {
        $response = ActionResponse::ok('Item added', ['id' => 5, 'name' => 'test'], 5);

        $out = json_decode((new ActionResponseAdapter())->toJsonRpc($response, 7), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('2.0', $out['jsonrpc']);
        self::assertSame(7, $out['id']);
        self::assertSame(5, $out['result']['itemId']);
        self::assertSame(['id' => 5, 'name' => 'test'], $out['result']['result']);
        self::assertSame(0, $out['result']['resultCode']);
        self::assertSame('Item added', $out['result']['resultMessage']);
        self::assertSame(2, $out['result']['count']);
    }

    #[Test]
    public function toJsonRpcMapsSearchResultsWithoutMessageOrItemId(): void
    {
        $response = ActionResponse::ok('', [['id' => 1], ['id' => 2]]);

        $out = json_decode((new ActionResponseAdapter())->toJsonRpc($response, 1), true, 512, JSON_THROW_ON_ERROR);

        self::assertNull($out['result']['itemId']);
        self::assertSame(0, $out['result']['resultCode']);
        self::assertNull($out['result']['resultMessage']);
        self::assertSame(2, $out['result']['count']);
    }

    #[Test]
    public function toJsonRpcMapsErrorStatusToResultCodeOne(): void
    {
        $response = ActionResponse::error('Something failed', ['reason' => 'test']);

        $out = json_decode((new ActionResponseAdapter())->toJsonRpc($response, 1), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $out['result']['resultCode']);
        self::assertSame('Something failed', $out['result']['resultMessage']);
    }

    #[Test]
    public function toRestReturnsTheResultEnvelopeWithoutJsonRpcWrapper(): void
    {
        $response = ActionResponse::ok('Item added', ['id' => 5], 5);

        $out = json_decode((new ActionResponseAdapter())->toRest($response), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('jsonrpc', $out);
        self::assertSame(5, $out['itemId']);
        self::assertSame(['id' => 5], $out['result']);
        self::assertSame('Item added', $out['resultMessage']);
    }

    #[Test]
    public function toJsonRpcExceptionIncludesTheHintAsDataForInvalidParams(): void
    {
        $exception = SPException::error('Wrong parameters', 'Missing name', ActionResponseAdapter::INVALID_PARAMS);

        $out = json_decode(
            (new ActionResponseAdapter())->toJsonRpcException($exception, 3),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('2.0', $out['jsonrpc']);
        self::assertSame(3, $out['id']);
        self::assertSame('Wrong parameters', $out['error']['message']);
        self::assertSame(ActionResponseAdapter::INVALID_PARAMS, $out['error']['code']);
        self::assertSame(['Missing name'], $out['error']['data']['help']);
    }

    #[Test]
    public function toJsonRpcExceptionOmitsHintDataOutsideInvalidParams(): void
    {
        $adapter = new ActionResponseAdapter();

        foreach ([
            ActionResponseAdapter::PARSE_ERROR,
            ActionResponseAdapter::INVALID_REQUEST,
            ActionResponseAdapter::METHOD_NOT_FOUND,
            ActionResponseAdapter::INTERNAL_ERROR,
            ActionResponseAdapter::SERVER_ERROR,
            0,
        ] as $code) {
            $exception = SPException::error('Internal failure', 'Sensitive internal detail', $code);
            $out = json_decode($adapter->toJsonRpcException($exception, 4), true, 512, JSON_THROW_ON_ERROR);

            self::assertSame('Internal failure', $out['error']['message']);
            self::assertSame($code, $out['error']['code']);
            self::assertNull($out['error']['data']);
        }
    }

    #[Test]
    public function toJsonRpcAndErrorPreserveZeroIntIdWhenPassedDirectly(): void
    {
        $response = ActionResponse::ok('Ok', ['x' => 1]);
        $adapter = new ActionResponseAdapter();

        $ok = json_decode($adapter->toJsonRpc($response, 0), true, 512, JSON_THROW_ON_ERROR);
        $err = json_decode($adapter->toJsonRpcError('Err', -32600, 0), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $ok['id']);
        self::assertSame(0, $err['id']);
    }

    #[Test]
    public function toJsonRpcAndErrorPreserveIntFloatStringAndNullIds(): void
    {
        $response = ActionResponse::ok('Ok', ['x' => 1]);
        $exception = SPException::error('Wrong', 'Hint', ActionResponseAdapter::INVALID_PARAMS);
        $adapter = new ActionResponseAdapter();

        foreach ([0, 1.5, 'abc', '0', null] as $id) {
            $ok = json_decode($adapter->toJsonRpc($response, $id), true, 512, JSON_THROW_ON_ERROR);
            $err = json_decode($adapter->toJsonRpcError('Err', -32600, $id), true, 512, JSON_THROW_ON_ERROR);
            $exc = json_decode($adapter->toJsonRpcException($exception, $id), true, 512, JSON_THROW_ON_ERROR);

            self::assertArrayHasKey('id', $ok);
            self::assertArrayHasKey('id', $err);
            self::assertArrayHasKey('id', $exc);
            self::assertSame($id, $ok['id']);
            self::assertSame($id, $err['id']);
            self::assertSame($id, $exc['id']);
        }
    }
}
