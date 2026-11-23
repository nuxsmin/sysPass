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

use ArrayObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Adapters\LegacyClassMap;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Core\Exceptions\SPException;
use SP\Tests\Stubs\SerializedPayloadFixture;
use SP\Tests\UnitaryTestCase;

/**
 * Class SerializedPayloadRewriterTest
 */
#[Group('unitary')]
class SerializedPayloadRewriterTest extends UnitaryTestCase
{
    private const LEGACY_CLASS = 'Old\Fixture';
    private const FIXTURE_CLASS = SerializedPayloadFixture::class;

    /**
     * @return array<array{string, string}>
     */
    public static function legacyCoreClassProvider(): array
    {
        return [
            ['a:1:{i:0;O:19:"SP\Core\Crypt\Crypt":0:{}}', 'a:1:{i:0;O:26:"SP\Domain\Core\Crypt\Crypt":0:{}}'],
            ['a:1:{i:0;O:19:"sp\core\crypt\crypt":0:{}}', 'a:1:{i:0;O:26:"SP\Domain\Core\Crypt\Crypt":0:{}}'],
        ];
    }

    /**
     * @return array<array{string}>
     */
    public static function malformedProvider(): array
    {
        return [
            [''],
            ['x'],
            ['i:1'],
            ['N'],
            ['b:2;'],
            ['d:abc;'],
            ['s:5:"abc";'],
            ['s:1:"a"'],
            ['s:99:"a";'],
            ['a:01:{}'],
            ['a:2:{i:0;i:1;}'],
            ['a:1:{i:0;i:1;'],
            ['i:1;x'],
            ['C:3:"Foo":0:{}'],
            ['E:3:"Abc";'],
            ['O:3:"Old":2:{s:1:"a";i:1;}'],
            ['O:3:"Old":1:{s:1:"a";i:1;'],
            ["O:3:\"Old\":1:{s:4:\"\0abc\";i:1;}"],
        ];
    }

    #[Test]
    public function legacyObjectIsRewrittenToCurrentClass(): void
    {
        $rewritten = SerializedPayloadRewriter::rewrite(self::legacyFixture('secret'), self::fixtureMap());

        $this->assertSame(serialize(new SerializedPayloadFixture('secret')), $rewritten);

        $fixture = unserialize($rewritten, ['allowed_classes' => [self::FIXTURE_CLASS]]);

        $this->assertInstanceOf(self::FIXTURE_CLASS, $fixture);
        $this->assertSame('secret', $fixture->data());
    }

    #[Test]
    public function nestedObjectsAreRewritten(): void
    {
        $payload = self::legacyFixture('outer', self::legacyFixture('inner'));

        $rewritten = SerializedPayloadRewriter::rewrite($payload, self::fixtureMap());

        $outer = unserialize($rewritten, ['allowed_classes' => [self::FIXTURE_CLASS]]);

        $this->assertInstanceOf(self::FIXTURE_CLASS, $outer);
        $this->assertSame('outer', $outer->data());
        $this->assertInstanceOf(self::FIXTURE_CLASS, $outer->child);
        $this->assertSame('inner', $outer->child->data());
    }

    #[Test]
    public function stringValuesContainingLegacyNamesAreUnchanged(): void
    {
        $payload = self::str('O:9:"Old\Fixture":0:{}' . "\0" . '";' . "\0Old\Fixture\0data");

        $this->assertSame($payload, SerializedPayloadRewriter::rewrite($payload, self::fixtureMap()));
    }

    #[Test]
    public function binaryStringValuesInsideLegacyObjectAreCopied(): void
    {
        $value = "\0\";s:3:\"x\";\0";

        $rewritten = SerializedPayloadRewriter::rewrite(self::legacyFixture($value), self::fixtureMap());

        $this->assertSame(serialize(new SerializedPayloadFixture($value)), $rewritten);
    }

    #[Test]
    public function integerPropertyKeysArePreserved(): void
    {
        $payload = serialize(new ArrayObject(['dbHost' => 'db']));

        $this->assertSame($payload, SerializedPayloadRewriter::rewrite($payload, []));
    }

    #[Test]
    public function legacyClassesInsideIntegerKeyedObjectsAreRewritten(): void
    {
        $vault = Vault::factory(new Crypt())->saveData('secret', 'key');

        $legacy = SerializedPayloadRewriter::rewrite(
            serialize(new ArrayObject(['vault' => $vault])),
            array_flip(LegacyClassMap::MAP)
        );

        $this->assertSame(
            serialize(new ArrayObject(['vault' => $vault])),
            SerializedPayloadRewriter::rewrite($legacy, LegacyClassMap::MAP)
        );
    }

    #[Test]
    public function arrayStringKeysAreNotRewritten(): void
    {
        $payload = 'a:1:{' . self::str("\0" . self::LEGACY_CLASS . "\0data") . self::str('v') . '}';

        $this->assertSame($payload, SerializedPayloadRewriter::rewrite($payload, self::fixtureMap()));
    }

    #[Test]
    public function referencesArePreserved(): void
    {
        $payload = 'a:2:{i:0;' . self::legacyFixture('shared') . 'i:1;R:2;}';

        $rewritten = SerializedPayloadRewriter::rewrite($payload, self::fixtureMap());

        $this->assertSame(
            'a:2:{i:0;' . serialize(new SerializedPayloadFixture('shared')) . 'i:1;R:2;}',
            $rewritten
        );

        $array = unserialize($rewritten, ['allowed_classes' => [self::FIXTURE_CLASS]]);

        $this->assertSame($array[0], $array[1]);
    }

    #[Test]
    public function unmappedClassesAreLeftUntouched(): void
    {
        $payload = self::object('Unknown\Class', [self::str('a') . self::str('b')]);

        $this->assertSame($payload, SerializedPayloadRewriter::rewrite($payload, self::fixtureMap()));
    }

    #[Test]
    public function enumClassNamesAreRewritten(): void
    {
        $enum = self::LEGACY_CLASS . ':Case';

        $rewritten = SerializedPayloadRewriter::rewrite('E:' . strlen($enum) . ':"' . $enum . '";', self::fixtureMap());

        $expected = self::FIXTURE_CLASS . ':Case';

        $this->assertSame('E:' . strlen($expected) . ':"' . $expected . '";', $rewritten);
    }

    #[Test]
    public function scalarTokensAreCopiedVerbatim(): void
    {
        $payload = 'a:8:{i:0;N;i:1;b:1;i:2;i:-5;i:3;d:1.5;i:4;d:INF;i:5;d:-0;i:6;d:1.0E+25;i:7;d:NAN;}';

        $this->assertSame($payload, SerializedPayloadRewriter::rewrite($payload, self::fixtureMap()));
    }

    #[Test]
    public function rewriteIsIdempotent(): void
    {
        $once = SerializedPayloadRewriter::rewrite(self::legacyFixture('secret'), self::fixtureMap());

        $this->assertSame($once, SerializedPayloadRewriter::rewrite($once, self::fixtureMap()));
    }

    #[Test]
    #[DataProvider('legacyCoreClassProvider')]
    public function defaultMapRewritesLegacyCoreClassesCaseInsensitively(string $payload, string $expected): void
    {
        $this->assertSame($expected, SerializedPayloadRewriter::rewrite($payload));
    }

    #[Test]
    #[DataProvider('malformedProvider')]
    public function malformedPayloadsThrow(string $payload): void
    {
        $this->expectException(SPException::class);

        SerializedPayloadRewriter::rewrite($payload, self::fixtureMap());
    }

    /**
     * @return array<string, string>
     */
    private static function fixtureMap(): array
    {
        return [self::LEGACY_CLASS => self::FIXTURE_CLASS];
    }

    private static function str(string $value): string
    {
        return 's:' . strlen($value) . ':"' . $value . '";';
    }

    /**
     * @param array<string> $properties
     */
    private static function object(string $class, array $properties): string
    {
        return 'O:' . strlen($class) . ':"' . $class . '":' . count($properties) . ':{' . implode('', $properties) . '}';
    }

    private static function legacyFixture(string $data, ?string $child = null): string
    {
        return self::fixture(self::LEGACY_CLASS, $data, $child);
    }

    private static function fixture(string $class, string $data, ?string $child = null): string
    {
        return self::object($class, [
            self::str("\0*\0shown") . self::str('shown'),
            self::str('child') . ($child ?? 'N;'),
            self::str("\0" . $class . "\0data") . self::str($data),
        ]);
    }
}
