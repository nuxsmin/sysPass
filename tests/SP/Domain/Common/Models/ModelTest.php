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

namespace SP\Tests\Domain\Common\Models;

use Error;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use SP\Tests\Stubs\ModelStub;

/**
 * Class ModelTest
 */
#[Group('unitary')]
class ModelTest extends TestCase
{
    protected static Generator $faker;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$faker = Factory::create();
    }

    public function testInstance()
    {
        $data = [
            'id' => self::$faker->randomNumber(3),
            'name' => self::$faker->colorName(),
            'test' => self::$faker->text()
        ];

        $model = new ModelStub($data);
        self::assertEquals($data['id'], $model->getId());
        self::assertEquals($data['id'], $model->id);
        self::assertEquals($data['name'], $model->getName());
        self::assertEquals($data['name'], $model->name);
        self::assertEquals($data['test'], $model['test']);
        self::assertNull($model->test);
    }

    #[TestWith(['id', 100])]
    #[TestWith(['test_a', 'a_text'])]
    public function testModifyClassProperties(string $property, mixed $value)
    {
        $data = [
            'id' => self::$faker->randomNumber(3),
            'test' => self::$faker->text()
        ];

        $model = new ModelStub($data);

        self::expectException(Error::class);

        $model->{$property} = $value;
    }

    public function testModifyInternalProperties()
    {
        $data = [
            'test' => self::$faker->text()
        ];

        $model = new ModelStub($data);

        self::expectException(Error::class);

        $model['test'] = 'a_text';
    }

    public function testGetColsWithPreffix()
    {
        self::markTestIncomplete();
    }

    public function testGetCols()
    {
        self::markTestIncomplete();
    }

    public function testToJson()
    {
        self::markTestIncomplete();
    }

    public function testBuildFromSimpleModel()
    {
        self::markTestIncomplete();
    }

    public function testToArray()
    {
        self::markTestIncomplete();
    }

    public function testMutate()
    {
        self::markTestIncomplete();
    }

    public function testJsonSerialize()
    {
        self::markTestIncomplete();
    }
}
