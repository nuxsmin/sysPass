<?php
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

namespace SP\Tests\Domain\Html\Services;

use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ServerDataCollection;
use Klein\Request;
use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Http\Header;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;
use TypeError;

/**
 * Class MinifyCssTest
 *
 */
#[Group('unitary')]
class MinifyCssTest extends UnitaryTestCase
{

    private Response|MockObject                $response;
    private Request|MockObject                 $request;
    private \SP\Domain\Html\Services\MinifyCss $minifyCss;

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFileWithEtag()
    {
        $this->minifyCss->addFile($this->buildCheckWithEtag()[0], false);
        $this->minifyCss->getMinified();
    }

    /**
     * @param int $numFiles
     * @return FileHandlerInterface[]|MockObject[]
     * @throws Exception
     */
    private function buildCheckWithEtag(int $numFiles = 1): array
    {
        $hash = self::$faker->sha1;
        $etag = sha1(
            array_reduce(
                range(1, $numFiles),
                static fn(string $out) => $out . $hash,
                ''
            )
        );

        $files = array_map(function () use ($hash) {
            $filePath = '/path/to/nowhere/test.css';

            $file = $this->createMock(FileHandlerInterface::class);
            $file->expects(self::once())
                 ->method('getHash')
                 ->willReturn($hash);
            $file->expects(self::any())
                 ->method('getName')
                 ->willReturn(basename($filePath));
            $file->expects(self::any())
                 ->method('getFile')
                 ->willReturn($filePath);
            $file->expects(self::once())
                 ->method('checkFileExists');

            return $file;
        }, range(1, $numFiles));

        $this->response->expects(self::once())
                       ->method('header');

        $headers = $this->createMock(HeaderDataCollection::class);
        $headers->expects(self::exactly(4))
                ->method('get')
                ->with(
                    new Callback(function (string $header) {
                        return $header === Header::IF_NONE_MATCH->value
                               || $header === \SP\Domain\Http\Header::CACHE_CONTROL->value
                               || $header === Header::PRAGMA->value;
                    })
                )
                ->willReturn($etag);

        $this->request->expects(self::once())
                      ->method('headers')
                      ->willReturn($headers);

        $server = $this->createMock(ServerDataCollection::class);
        $server->expects(self::once())
               ->method('get')
               ->with('SERVER_PROTOCOL')
               ->willReturn('http');

        $this->request->expects(self::once())
                      ->method('server')
                      ->willReturn($server);

        $this->response->expects(self::once())
                       ->method('header')
                       ->with('http', '304 Not Modified');

        $this->response->expects(self::once())
                       ->method('send');

        $this->response->expects(self::once())
                       ->method('isSent')
                       ->willReturn(true);

        $this->response->expects(self::never())
                       ->method('body');

        return $files;
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFileWithoutEtag()
    {
        $this->minifyCss->addFile($this->buildCheckWithoutEtag()[0], false);
        $this->minifyCss->getMinified();
    }

    /**
     * @param int $numFiles
     * @return FileHandlerInterface[]|MockObject[]
     * @throws Exception
     */
    private function buildCheckWithoutEtag(int $numFiles = 1): array
    {
        $hash = self::$faker->sha1;
        $etag = sha1(
            array_reduce(
                range(1, $numFiles),
                static fn(string $out) => $out . $hash,
                ''
            )
        );

        $files = array_map(function () use ($hash) {
            $filePath = '/path/to/nowhere/test.css';

            $file = $this->createMock(FileHandlerInterface::class);
            $file->expects(self::once())
                 ->method('getHash')
                 ->willReturn($hash);
            $file->expects(self::any())
                 ->method('getBase')
                 ->willReturn(dirname($filePath));
            $file->expects(self::any())
                 ->method('getName')
                 ->willReturn(basename($filePath));
            $file->expects(self::any())
                 ->method('getFile')
                 ->willReturn($filePath);
            $file->expects(self::once())
                 ->method('checkFileExists');

            return $file;
        }, range(1, $numFiles));

        $headers = $this->createMock(HeaderDataCollection::class);
        $headers->expects(self::once())
                ->method('get')
            ->with(\SP\Domain\Http\Header::IF_NONE_MATCH->value)
                ->willReturn(self::$faker->sha1);

        $this->request->expects(self::once())
                      ->method('headers')
                      ->willReturn($headers);

        $this->response->expects(self::exactly(5))
                       ->method('header')
                       ->with(
                           ...self::withConsecutive(
                           [\SP\Domain\Http\Header::ETAG->value, $etag],
                           [
                               \SP\Domain\Http\Header::CACHE_CONTROL->value,
                               'public, max-age={2592000}, must-revalidate'
                           ],
                           [Header::PRAGMA->value, 'public; maxage={2592000}'],
                           [\SP\Domain\Http\Header::EXPIRES->value, self::anything()],
                           [Header::CONTENT_TYPE->value, 'text/css; charset: UTF-8']
                       )
                       );

        $this->response->expects(self::never())
                       ->method('send');

        $this->response->expects(self::once())
                       ->method('isSent')
                       ->willReturn(false);

        $this->response->expects(self::once())
                       ->method('body');

        return $files;
    }

    public function testBuilder()
    {
        $out = $this->minifyCss->builder();

        self::assertNotEquals(spl_object_id($this->minifyCss), spl_object_id($out));
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFilesWithEtag()
    {
        $this->minifyCss->addFiles($this->buildCheckWithEtag(2), false);
        $this->minifyCss->getMinified();
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFilesWithoutEtag()
    {
        $this->minifyCss->addFiles($this->buildCheckWithoutEtag(2), false);
        $this->minifyCss->getMinified();
    }

    /**
     * @throws FileException
     */
    public function testAddFilesWithWrongObject()
    {
        $this->expectException(TypeError::class);

        $this->minifyCss->addFiles([self::$faker->filePath()], false);
        $this->minifyCss->getMinified();
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testGetMinifiedWithFiles()
    {
        $this->minifyCss->addFiles($this->buildCheckWithoutEtag());
        $this->minifyCss->getMinified();
    }

    public function testGetMinifiedWithoutFiles()
    {
        $this->response->expects(self::never())
                       ->method('isSent');
        $this->response->expects(self::never())
                       ->method('body');

        $this->minifyCss->getMinified();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->response = $this->createMock(Response::class);
        $this->request = $this->createMock(Request::class);

        $this->minifyCss = new \SP\Domain\Html\Services\MinifyCss($this->response, $this->request);
    }

}
