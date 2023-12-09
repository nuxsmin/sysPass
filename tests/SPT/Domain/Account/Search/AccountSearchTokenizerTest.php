<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Domain\Account\Search;

use SP\Domain\Account\Search\AccountSearchTokenizer;
use SPT\UnitaryTestCase;

/**
 * Class AccountSearchTokenizerTest
 *
 * @group unitary
 */
class AccountSearchTokenizerTest extends UnitaryTestCase
{
    use AccountSearchTokenizerDataTrait;

    /**
     * @dataProvider searchByItemDataProvider
     *
     * @param  string  $search
     * @param  array  $expectedConditions
     *
     * @return void
     */
    public function testTokenizeFromFilterByItems(string $search, array $expectedConditions): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedConditions, $out->getItems());
    }

    /**
     * @dataProvider searchByConditionDataProvider
     *
     * @param  string  $search
     * @param  array  $expectedConditions
     *
     * @return void
     */
    public function testTokenizeFromFilterByCondition(string $search, array $expectedConditions): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedConditions, $out->getConditions());
    }

    /**
     * @dataProvider searchUsingOperatorDataProvider
     *
     * @param  string  $search
     * @param  string|null  $expectedCondition
     *
     * @return void
     */
    public function testTokenizeFromFilterUsingOperator(string $search, ?string $expectedCondition): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedCondition, $out->getOperator());
    }

    /**
     * @dataProvider searchUsingStringDataProvider
     *
     * @param  string  $search
     * @param  string  $expectedString
     *
     * @return void
     */
    public function testTokenizeFromFilterUsingSearchString(string $search, string $expectedString): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedString, $out->getSearch());
    }

    /**
     * @return void
     */
    public function testTokenizeFromFilterUsingSearchStringWithIsNull(): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom('');

        $this->assertNull($out);
    }
}
