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

namespace SP\Tests\Domain\Common\Adapters;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Adapters\LegacyClassMap;
use SP\Domain\Common\Adapters\PersistedClassRegistry;
use SP\Domain\Common\Adapters\SerializedPayloadReader;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Tests\Stubs\SerializedPayloadFixture;
use SP\Tests\Stubs\SerializedPayloadGraphFixture;
use SP\Tests\UnitaryTestCase;

/**
 * Class SerializedPayloadReaderTest
 */
#[Group('unitary')]
class SerializedPayloadReaderTest extends UnitaryTestCase
{
    private const LEGACY_CLASS = 'Old\Fixture';

    #[Test]
    public function legacyVaultIsReadable(): void
    {
        $data = 'secret';
        $key = 'key';
        $vault = Vault::factory(new Crypt())->saveData($data, $key);

        $legacy = SerializedPayloadRewriter::rewrite(
            serialize($vault),
            array_flip(LegacyClassMap::MAP)
        );

        $read = SerializedPayloadReader::read($legacy, Vault::class);

        $this->assertInstanceOf(Vault::class, $read);
        $this->assertSame($data, $read->getData($key));
    }

    #[Test]
    public function currentPayloadIsReadable(): void
    {
        $data = 'secret';
        $key = 'key';
        $vault = Vault::factory(new Crypt())->saveData($data, $key);

        $read = SerializedPayloadReader::read(serialize($vault), Vault::class);

        $this->assertInstanceOf(Vault::class, $read);
        $this->assertSame($data, $read->getData($key));
    }

    #[Test]
    public function configDataPayloadIsReadable(): void
    {
        $configData = new ConfigData([
            ConfigDataInterface::DB_HOST => 'db',
            ConfigDataInterface::DB_PORT => 3306
        ]);

        $read = SerializedPayloadReader::read(
            serialize($configData),
            ConfigData::class,
            PersistedClassRegistry::CLASSES,
            []
        );

        $this->assertInstanceOf(ConfigData::class, $read);
        $this->assertSame('db', $read->getDbHost());
        $this->assertSame(3306, $read->getDbPort());
    }

    #[Test]
    public function mappedFixtureIsReadable(): void
    {
        $legacy = SerializedPayloadRewriter::rewrite(
            serialize(new SerializedPayloadFixture('secret')),
            [SerializedPayloadFixture::class => self::LEGACY_CLASS]
        );

        $read = SerializedPayloadReader::read(
            $legacy,
            SerializedPayloadFixture::class,
            [SerializedPayloadFixture::class],
            [self::LEGACY_CLASS => SerializedPayloadFixture::class]
        );

        $this->assertInstanceOf(SerializedPayloadFixture::class, $read);
        $this->assertSame('secret', $read->data());
    }

    #[Test]
    public function unmappedPayloadThrows(): void
    {
        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read(serialize(new SerializedPayloadFixture('secret')), Vault::class);
    }

    #[Test]
    public function stdClassPayloadThrows(): void
    {
        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read(serialize((object)['data' => 'secret']), Vault::class);
    }

    #[Test]
    public function wrongExpectedClassThrows(): void
    {
        $legacy = SerializedPayloadRewriter::rewrite(
            serialize(new SerializedPayloadFixture('secret')),
            [SerializedPayloadFixture::class => self::LEGACY_CLASS]
        );

        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read(
            $legacy,
            SerializedPayloadGraphFixture::class,
            [SerializedPayloadFixture::class],
            [self::LEGACY_CLASS => SerializedPayloadFixture::class]
        );
    }

    #[Test]
    public function nestedClassOutsideAllowListThrows(): void
    {
        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read(
            serialize(self::graphFixture(new SerializedPayloadFixture('secret'))),
            SerializedPayloadGraphFixture::class
        );
    }

    #[Test]
    public function nestedClassInsideAllowListIsReadable(): void
    {
        $read = SerializedPayloadReader::read(
            serialize(self::graphFixture(new SerializedPayloadFixture('secret'))),
            SerializedPayloadGraphFixture::class,
            [SerializedPayloadGraphFixture::class, SerializedPayloadFixture::class]
        );

        $this->assertInstanceOf(SerializedPayloadGraphFixture::class, $read);
        $this->assertInstanceOf(SerializedPayloadFixture::class, $read->child);
        $this->assertSame('secret', $read->child->data());
    }

    #[Test]
    public function nestedIncompleteClassThrows(): void
    {
        $unknown = 'Unknown\Class';

        $payload = sprintf(
            'O:%d:"%s":2:{s:5:"child";O:%d:"%s":0:{}s:4:"data";s:4:"data";}',
            strlen(SerializedPayloadGraphFixture::class),
            SerializedPayloadGraphFixture::class,
            strlen($unknown),
            $unknown
        );

        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read($payload, SerializedPayloadGraphFixture::class);
    }

    #[Test]
    public function malformedPayloadThrows(): void
    {
        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read('a:2:{i:0;i:1;}', Vault::class);
    }

    #[Test]
    public function malformedPayloadKeepsTheRewriterExceptionAsPrevious(): void
    {
        try {
            SerializedPayloadReader::read('a:2:{i:0;i:1;}', Vault::class);
        } catch (InvalidClassException $e) {
            $this->assertInstanceOf(SPException::class, $e->getPrevious());

            return;
        }

        $this->fail('Expected InvalidClassException');
    }

    #[Test]
    public function unknownExpectedClassThrows(): void
    {
        $this->expectException(InvalidClassException::class);

        SerializedPayloadReader::read(serialize(new SerializedPayloadFixture('secret')), 'Unknown\Class');
    }

    private static function graphFixture(mixed $child): SerializedPayloadGraphFixture
    {
        $fixture = new SerializedPayloadGraphFixture();
        $fixture->child = $child;

        return $fixture;
    }
}
