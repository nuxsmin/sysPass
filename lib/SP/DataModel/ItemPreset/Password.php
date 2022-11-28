<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel\ItemPreset;

/**
 * Class Password
 *
 * @package SP\DataModel\ItemPreset
 */
class Password implements PresetInterface
{
    private const PRESET_TYPE = 'password';

    public const EXPIRE_TIME_MULTIPLIER = 86400;

    /**
     * @param  int  $length
     * @param  bool  $useNumbers
     * @param  bool  $useLetters
     * @param  bool  $useSymbols
     * @param  bool  $useUpper
     * @param  bool  $useLower
     * @param  bool  $useImage
     * @param  int  $expireTime
     * @param  int  $score
     * @param  string|null  $regex
     */
    public function __construct(
        private int $length,
        private bool $useNumbers,
        private bool $useLetters,
        private bool $useSymbols,
        private bool $useUpper,
        private bool $useLower,
        private bool $useImage,
        private int $expireTime,
        private int $score,
        private ?string $regex = null
    ) {
        $this->expireTime = $expireTime * self::EXPIRE_TIME_MULTIPLIER;
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function isUseNumbers(): bool
    {
        return $this->useNumbers;
    }

    public function isUseLetters(): bool
    {
        return $this->useLetters;
    }

    public function isUseSymbols(): bool
    {
        return $this->useSymbols;
    }

    public function isUseUpper(): bool
    {
        return $this->useUpper;
    }

    public function isUseLower(): bool
    {
        return $this->useLower;
    }

    public function isUseImage(): bool
    {
        return $this->useImage;
    }

    public function getExpireTime(): int
    {
        return $this->expireTime;
    }

    public function getPresetType(): string
    {
        return self::PRESET_TYPE;
    }
}
