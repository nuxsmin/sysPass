<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Domain\Http\Services;

use Klein\Response;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls;
use PHPUnit\Framework\MockObject\Stub\ReturnSelf;
use PHPUnit\Framework\TestCase;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Http\Services\JsonResponse;
use SP\Tests\PHPUnitHelper;

/**
 * Class JsonResponseTest
 */
#[Group('unitary')]
class JsonResponseTest extends TestCase
{

    use PHPUnitHelper;

    private MockObject|Response $response;
    private JsonResponse        $jsonResponse;

    #[TestWith([true])]
    #[TestWith([false])]
    public function testSendRaw(bool $isSent)
    {
        $this->response
            ->expects($this->once())
            ->method('header')
            ->with('Content-type', 'application/json; charset=utf-8')
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('body')
            ->with('a_response')
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('send')
            ->with(true)
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('isSent')
            ->willReturn($isSent);

        $this->assertEquals($isSent, $this->jsonResponse->sendRaw('a_response'));
    }

    #[DoesNotPerformAssertions]
    public function testFactory()
    {
        JsonResponse::factory($this->response);
    }

    /**
     * @throws SPException
     */
    #[TestWith([true])]
    #[TestWith([false])]
    public function testSend(bool $isSent)
    {
        $message = new JsonMessage('a_test');
        $message->setData(['test' => 'a_data']);
        $message->addMessage('a_message');

        $this->response
            ->expects($this->once())
            ->method('header')
            ->with('Content-type', 'application/json; charset=utf-8')
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('body')
            ->with('{"status":1,"description":"a_test","data":{"test":"a_data"},"messages":["a_message"]}')
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('send')
            ->with(true)
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('isSent')
            ->willReturn($isSent);

        $this->assertEquals($isSent, $this->jsonResponse->send($message));
    }

    /**
     * @throws SPException
     */
    #[TestWith([true])]
    #[TestWith([false])]
    public function testSendWithException(bool $isSent)
    {
        $message = new JsonMessage('a_test');
        $message->setData(['test' => 'a_data']);
        $message->addMessage('a_message');

        $this->response
            ->expects($this->once())
            ->method('header')
            ->with('Content-type', 'application/json; charset=utf-8')
            ->willReturnSelf();

        $bodyOk = '{"status":1,"description":"a_test","data":{"test":"a_data"},"messages":["a_message"]}';
        $bodyError = '{"status":1,"description":"test","data":[],"messages":["a_hint"]}';

        $this->response
            ->expects($this->exactly(2))
            ->method('body')
            ->with(...self::withConsecutive([$bodyOk], [$bodyError]))
            ->will(
                new ConsecutiveCalls(
                    [
                        new \PHPUnit\Framework\MockObject\Stub\Exception(SPException::error('test', 'a_hint')),
                        new ReturnSelf(),
                    ]
                )
            );

        $this->response
            ->expects($this->once())
            ->method('send')
            ->with(true)
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('isSent')
            ->willReturn($isSent);

        $this->assertEquals($isSent, $this->jsonResponse->send($message));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->response = $this->createMock(Response::class);
        $this->jsonResponse = new JsonResponse($this->response);
    }
}
