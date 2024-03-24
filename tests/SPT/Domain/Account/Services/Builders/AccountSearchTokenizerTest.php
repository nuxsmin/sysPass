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

namespace SPT\Domain\Account\Services\Builders;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SP\Domain\Account\Services\Builders\AccountSearchTokenizer;
use SPT\UnitaryTestCase;

/**
 * Class AccountSearchTokenizerTest
 *
 */
#[Group('unitary')]
class AccountSearchTokenizerTest extends UnitaryTestCase
{
    use AccountSearchTokenizerDataTrait;

    /**
     * @param string $search
     * @param array $expectedConditions
     *
     * @return void
     */
    #[DataProvider('searchByItemDataProvider')]
    public function testTokenizeFromFilterByItems(string $search, array $expectedConditions): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedConditions, $out->getItems());
    }

    /**
     * @param string $search
     * @param array $expectedConditions
     *
     * @return void
     */
    #[DataProvider('searchByConditionDataProvider')]
    public function testTokenizeFromFilterByCondition(string $search, array $expectedConditions): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedConditions, $out->getConditions());
    }

    /**
     * @param string $search
     * @param string|null $expectedCondition
     *
     * @return void
     */
    #[DataProvider('searchUsingOperatorDataProvider')]
    public function testTokenizeFromFilterUsingOperator(string $search, ?string $expectedCondition): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $out = $tokenizer->tokenizeFrom($search);

        $this->assertNotNull($out);
        $this->assertEquals($expectedCondition, $out->getOperator());
    }

    /**
     * @param string $search
     * @param string $expectedString
     *
     * @return void
     */
    #[DataProvider('searchUsingStringDataProvider')]
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
