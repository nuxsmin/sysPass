<?php

declare(strict_types=1);
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

namespace SP\Tests\Infrastructure\File;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Adapters\LegacyClassMap;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\File\FileCache;
use SP\Tests\UnitaryTestCase;

/**
 * Class FileCacheTest
 */
#[Group('unitary')]
class FileCacheTest extends UnitaryTestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(TMP_PATH, 'file-cache-');
    }

    protected function tearDown(): void
    {
        unlink($this->file);

        parent::tearDown();
    }

    #[Test]
    public function loadWithReadsCurrentPayload(): void
    {
        $vault = Vault::factory(new Crypt())->saveData('secret', 'key');

        (new FileCache($this->file))->save($vault);

        $this->assertSame('secret', (new FileCache($this->file))->loadWith(Vault::class)->getData('key'));
    }

    #[Test]
    public function loadWithLegacyPayloadThrows(): void
    {
        file_put_contents($this->file, $this->legacyVault());

        $this->expectException(InvalidClassException::class);

        (new FileCache($this->file))->loadWith(Vault::class);
    }

    #[Test]
    public function loadWithCorruptPayloadThrows(): void
    {
        file_put_contents($this->file, 'O:26:"SP\Domain\Core\Crypt\Vault":1:{');

        $this->expectException(InvalidClassException::class);

        (new FileCache($this->file))->loadWith(Vault::class);
    }

    #[Test]
    public function saveOverLongerFileLeavesNoTrailingBytes(): void
    {
        file_put_contents($this->file, str_repeat('x', 4096));

        (new FileCache($this->file))->save(Vault::factory(new Crypt())->saveData('secret', 'key'));

        $this->assertSame('secret', (new FileCache($this->file))->loadWith(Vault::class)->getData('key'));
    }

    #[Test]
    public function saveAfterLoadOnSameInstanceReplacesPayload(): void
    {
        (new FileCache($this->file))->save(Vault::factory(new Crypt())->saveData('first', 'key'));

        $cache = new FileCache($this->file);

        $this->assertSame('first', $cache->loadWith(Vault::class)->getData('key'));

        $cache->save(Vault::factory(new Crypt())->saveData('second', 'key'));

        $this->assertSame('second', (new FileCache($this->file))->loadWith(Vault::class)->getData('key'));
    }

    private function legacyVault(): string
    {
        return SerializedPayloadRewriter::rewrite(
            serialize(Vault::factory(new Crypt())->saveData('secret', 'key')),
            array_flip(LegacyClassMap::MAP)
        );
    }
}
