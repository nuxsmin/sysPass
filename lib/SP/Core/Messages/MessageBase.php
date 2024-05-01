<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Messages;

use SP\Domain\Core\Messages\FormatterInterface;
use SP\Domain\Core\Messages\MessageInterface;

/**
 * Class MessageBase
 *
 * @package SP\Core\Messages
 */
abstract class MessageBase implements MessageInterface
{
    protected string $title = '';
    protected array $footer = [];
    protected array $description = [];

    public static function factory(): MessageBase
    {
        return new static();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): MessageBase
    {
        $this->title = $title;

        return $this;
    }

    abstract public function getDescription(
        FormatterInterface $formatter,
        bool               $translate
    ): string;

    public function setDescription(array $description): MessageBase
    {
        $this->description = $description;

        return $this;
    }

    public function addDescription(string $description): MessageBase
    {
        $this->description[] = $description;

        return $this;
    }

    public function getFooter(): array
    {
        return $this->footer;
    }

    public function setFooter(array $footer): MessageBase
    {
        $this->footer = $footer;

        return $this;
    }
}
