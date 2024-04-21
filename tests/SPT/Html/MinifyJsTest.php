<?php
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

namespace SPT\Html;

use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ServerDataCollection;
use Klein\Request;
use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Html\Header;
use SP\Html\MinifyJs;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;
use TypeError;

/**
 * Class MinifyJsTest
 *
 */
#[Group('unitary')]
class MinifyJsTest extends UnitaryTestCase
{

    private Response|MockObject $response;
    private Request|MockObject  $request;
    private MinifyJs            $minifyJs;

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFileWithEtag()
    {
        $this->minifyJs->addFile($this->buildCheckWithEtag()[0], false);
        $this->minifyJs->getMinified();
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
            $filePath = '/path/to/nowhere/test.js';

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
                               || $header === Header::CACHE_CONTROL->value
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
        $this->minifyJs->addFile($this->buildCheckWithoutEtag()[0], false);
        $this->minifyJs->getMinified();
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
                ->with(Header::IF_NONE_MATCH->value)
                ->willReturn(self::$faker->sha1);

        $this->request->expects(self::once())
                      ->method('headers')
                      ->willReturn($headers);

        $this->response->expects(self::exactly(5))
                       ->method('header')
                       ->with(
                           ...self::withConsecutive(
                           [Header::ETAG->value, $etag],
                           [
                               Header::CACHE_CONTROL->value,
                               'public, max-age={2592000}, must-revalidate'
                           ],
                           [Header::PRAGMA->value, 'public; maxage={2592000}'],
                           [Header::EXPIRES->value, self::anything()],
                           [Header::CONTENT_TYPE->value, 'application/javascript; charset: UTF-8']
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
        $out = $this->minifyJs->builder();

        self::assertNotEquals(spl_object_id($this->minifyJs), spl_object_id($out));
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFilesWithEtag()
    {
        $this->minifyJs->addFiles($this->buildCheckWithEtag(2), false);
        $this->minifyJs->getMinified();
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testAddFilesWithoutEtag()
    {
        $this->minifyJs->addFiles($this->buildCheckWithoutEtag(2), false);
        $this->minifyJs->getMinified();
    }

    /**
     * @throws FileException
     */
    public function testAddFilesWithWrongObject()
    {
        $this->expectException(TypeError::class);

        $this->minifyJs->addFiles([self::$faker->filePath()], false);
        $this->minifyJs->getMinified();
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testGetMinifiedWithFiles()
    {
        $this->minifyJs->addFiles($this->buildCheckWithoutEtag());
        $this->minifyJs->getMinified();
    }

    public function testGetMinifiedWithoutFiles()
    {
        $this->response->expects(self::never())
                       ->method('isSent');
        $this->response->expects(self::never())
                       ->method('body');

        $this->minifyJs->getMinified();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->response = $this->createMock(Response::class);
        $this->request = $this->createMock(Request::class);

        $this->minifyJs = new MinifyJs($this->response, $this->request);
    }

}
