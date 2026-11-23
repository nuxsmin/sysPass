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

declare(strict_types=1);

namespace SP\Tests\Infrastructure\File;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Infrastructure\File\FileHandler;

/**
 * Class FileHandlerTest
 */
#[Group('unitary')]
class FileHandlerTest extends TestCase
{
    /**
     * PHP 8.4 deprecates calling fgetcsv() without the $escape argument.
     */
    #[Test]
    public function testReadFromCsvDoesNotTriggerEscapeDeprecation(): void
    {
        if (PHP_VERSION_ID < 80400) {
            self::markTestSkipped('The fgetcsv() $escape argument is deprecated on PHP >= 8.4');
        }

        $file = $this->createCsvFile('one;two;three');
        $deprecations = [];

        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                if ($errno === E_DEPRECATED) {
                    $deprecations[] = $errstr;

                    return true;
                }

                return false;
            }
        );

        try {
            $rows = iterator_to_array((new FileHandler($file))->readFromCsv(';'));
        } finally {
            restore_error_handler();
            unlink($file);
        }

        self::assertSame([['one', 'two', 'three']], $rows);
        self::assertSame([], $deprecations);
    }

    /**
     * The legacy backslash escape behaviour must be kept explicitly, because
     * the PHP 9 default will change to an empty escape character.
     */
    #[Test]
    public function testReadFromCsvKeepsBackslashEscapeBehaviour(): void
    {
        $file = $this->createCsvFile('"foo\\"bar";baz');

        try {
            $rows = iterator_to_array((new FileHandler($file))->readFromCsv(';'));
        } finally {
            unlink($file);
        }

        self::assertSame([['foo\\"bar', 'baz']], $rows);
    }

    #[Test]
    public function testReadFromCsvIgnoresTrailingBlankLines(): void
    {
        $file = $this->createCsvFile("one;two;three\n\n");

        try {
            $rows = iterator_to_array((new FileHandler($file))->readFromCsv(';'));
        } finally {
            unlink($file);
        }

        self::assertSame([['one', 'two', 'three']], $rows);
    }

    private function createCsvFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'syspass-csv-');

        if ($file === false) {
            self::fail('Unable to create a temporary CSV file');
        }

        file_put_contents($file, $contents);

        return $file;
    }
}
