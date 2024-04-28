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

namespace SPT\Domain\Common\Adapters;

use __PHP_Incomplete_Class;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Core\Exceptions\SPException;
use SPT\UnitaryTestCase;
use stdClass;

/**
 * Class SerdeTest
 */
#[Group('unitary')]
class SerdeTest extends UnitaryTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [['a' => 'testA', 'b' => 1, 'c' => true], 'a:3:{s:1:"a";s:5:"testA";s:1:"b";i:1;s:1:"c";b:1;}'],
            [
                (object)['a' => 'testA', 'b' => 1, 'c' => true],
                'O:8:"stdClass":3:{s:1:"a";s:5:"testA";s:1:"b";i:1;s:1:"c";b:1;}'
            ],
            ['a_string', 's:8:"a_string";'],
            [1, 'i:1;']
        ];
    }

    #[DataProvider('serializeDataProvider')]
    public function testSerialize(mixed $data, string $expected)
    {
        $out = \SP\Domain\Common\Adapters\Serde::serialize($data);

        $this->assertEquals($expected, $out);
    }

    /**
     * @throws SPException
     */
    public function testDeserialize()
    {
        $data = 'O:20:"SP\Config\ConfigData":1:{s:13:"'
                . "\0" . '*' . "\0" .
                'attributes";a:4:{s:12:"passwordSalt";s:60:"901a4d025ab807564c3c46afc69ab9fd1ae25c6dbba7d62ce3b279f7523c";s:10:"configDate";i:1633156732;s:11:"configSaver";s:7:"sysPass";s:10:"configHash";s:40:"0f099212786ab8090432f2889ac37c2a977f164a";}}';

        $out = Serde::deserialize($data);

        $this->assertInstanceOf(stdClass::class, $out);
        $this->assertIsArray($out->attributes);
        $this->assertEquals(
            '901a4d025ab807564c3c46afc69ab9fd1ae25c6dbba7d62ce3b279f7523c',
            $out->attributes['passwordSalt']
        );
        $this->assertEquals('1633156732', $out->attributes['configDate']);
        $this->assertEquals('sysPass', $out->attributes['configSaver']);
        $this->assertEquals('0f099212786ab8090432f2889ac37c2a977f164a', $out->attributes['configHash']);
    }

    /**
     * @throws SPException
     */
    public function testDeserializeWithClass()
    {
        $data = 'O:20:"SP\Config\ConfigData":1:{s:13:"'
                . "\0" . '*' . "\0" .
                'attributes";a:4:{s:12:"passwordSalt";s:60:"901a4d025ab807564c3c46afc69ab9fd1ae25c6dbba7d62ce3b279f7523c";s:10:"configDate";i:1633156732;s:11:"configSaver";s:7:"sysPass";s:10:"configHash";s:40:"0f099212786ab8090432f2889ac37c2a977f164a";}}';

        $out = \SP\Domain\Common\Adapters\Serde::deserialize($data, __PHP_Incomplete_Class::class);

        $this->assertInstanceOf(stdClass::class, $out);
        $this->assertIsArray($out->attributes);
        $this->assertEquals(
            '901a4d025ab807564c3c46afc69ab9fd1ae25c6dbba7d62ce3b279f7523c',
            $out->attributes['passwordSalt']
        );
        $this->assertEquals('1633156732', $out->attributes['configDate']);
        $this->assertEquals('sysPass', $out->attributes['configSaver']);
        $this->assertEquals('0f099212786ab8090432f2889ac37c2a977f164a', $out->attributes['configHash']);
    }

    /**
     * @throws SPException
     */
    public function testDeserializeWithClassException()
    {
        $data = 'O:20:"SP\Config\ConfigData":1:{s:13:"'
                . "\0" . '*' . "\0" .
                'attributes";a:4:{s:12:"passwordSalt";s:60:"901a4d025ab807564c3c46afc69ab9fd1ae25c6dbba7d62ce3b279f7523c";s:10:"configDate";i:1633156732;s:11:"configSaver";s:7:"sysPass";s:10:"configHash";s:40:"0f099212786ab8090432f2889ac37c2a977f164a";}}';

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('Invalid target class');

        \SP\Domain\Common\Adapters\Serde::deserialize($data, ConfigData::class);
    }

    /**
     * @throws SPException
     */
    public function testSerializeJson()
    {
        $data = ['a' => 'testA', 'b' => 1, 'c' => true, 'd' => new stdClass()];

        $out = Serde::serializeJson($data);

        $expected = '{"a":"testA","b":1,"c":true,"d":{}}';

        $this->assertEquals($expected, $out);
    }

    /**
     * @throws SPException
     */
    public function testSerializeJsonWithException()
    {
        $data = [
            'a' => 'testA',
            'b' => 1,
            'c' => true,
            'd' => &$data
        ];

        $this->expectException(SPException::class);

        Serde::serializeJson($data);
    }

    /**
     * @throws SPException
     */
    public function testDeserializeJson()
    {
        $data = '{"a":"testA","b":1,"c":true,"d":{}}';

        $out = Serde::deserializeJson($data);

        $expected = (object)['a' => 'testA', 'b' => 1, 'c' => true, 'd' => new stdClass()];

        $this->assertEquals($expected, $out);
    }

    /**
     * @throws SPException
     */
    public function testDeserializeJsonWithException()
    {
        $data = '{"a":"testA","b":1,"c":true,"d":}';

        $this->expectException(SPException::class);

        \SP\Domain\Common\Adapters\Serde::deserializeJson($data);
    }
}
