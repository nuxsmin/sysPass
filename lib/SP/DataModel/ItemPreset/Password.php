<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\DataModel\ItemPreset;

/**
 * Class Password
 *
 * @package SP\DataModel\ItemPreset
 */
class Password
{
    const EXPIRE_TIME_MULTIPLIER = 86400;

    /**
     * @var int
     */
    private $length = 0;
    /**
     * @var bool
     */
    private $useNumbers = false;
    /**
     * @var bool
     */
    private $useLetters = false;
    /**
     * @var bool
     */
    private $useSymbols = false;
    /**
     * @var bool
     */
    private $useUpper = false;
    /**
     * @var bool
     */
    private $useLower = false;
    /**
     * @var bool
     */
    private $useImage = false;
    /**
     * @var int
     */
    private $expireTime = 0;
    /**
     * @var int
     */
    private $score = 0;
    /**
     * @var string
     */
    private $regex;

    /**
     * @return string
     */
    public function getRegex(): string
    {
        return $this->regex ?: '';
    }

    /**
     * @param string $regex
     *
     * @return Password
     */
    public function setRegex(string $regex): Password
    {
        $this->regex = $regex;
        return $this;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     *
     * @return Password
     */
    public function setScore(int $score): Password
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param int $length
     *
     * @return Password
     */
    public function setLength(int $length): Password
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseNumbers(): bool
    {
        return $this->useNumbers;
    }

    /**
     * @param bool $useNumbers
     *
     * @return Password
     */
    public function setUseNumbers(bool $useNumbers): Password
    {
        $this->useNumbers = $useNumbers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseLetters(): bool
    {
        return $this->useLetters;
    }

    /**
     * @param bool $useLetters
     *
     * @return Password
     */
    public function setUseLetters(bool $useLetters): Password
    {
        $this->useLetters = $useLetters;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseSymbols(): bool
    {
        return $this->useSymbols;
    }

    /**
     * @param bool $useSymbols
     *
     * @return Password
     */
    public function setUseSymbols(bool $useSymbols): Password
    {
        $this->useSymbols = $useSymbols;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseUpper(): bool
    {
        return $this->useUpper;
    }

    /**
     * @param bool $useUpper
     *
     * @return Password
     */
    public function setUseUpper(bool $useUpper): Password
    {
        $this->useUpper = $useUpper;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseLower(): bool
    {
        return $this->useLower;
    }

    /**
     * @param bool $useLower
     *
     * @return Password
     */
    public function setUseLower(bool $useLower): Password
    {
        $this->useLower = $useLower;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseImage(): bool
    {
        return $this->useImage;
    }

    /**
     * @param bool $useImage
     *
     * @return Password
     */
    public function setUseImage(bool $useImage): Password
    {
        $this->useImage = $useImage;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpireTime(): int
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     *
     * @return Password
     */
    public function setExpireTime(int $expireTime): Password
    {
        $this->expireTime = $expireTime * self::EXPIRE_TIME_MULTIPLIER;

        return $this;
    }
}