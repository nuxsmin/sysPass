<?php
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

/**
 * Class NoticeMessage
 *
 * @package SP\Core\Messages
 */
final class NotificationMessage extends MessageBase
{
    /**
     * Componer un mensaje en formato HTML
     */
    public function composeHtml(): string
    {
        $formatter = new HtmlFormatter();

        $message = '<div class="notice-message" style="font-family: Helvetica, Arial, sans-serif">';

        if ($this->title) {
            $message .= '<h3>' . $this->title . '</h3>';
        }

        if (count($this->description) !== 0) {
            $message .= '<div class="notice-description">' . $this->getDescription($formatter) . '</div>';
        }

        if (count($this->footer) !== 0) {
            $message .= '<footer>' . implode('<br>', $this->footer) . '</footer>';
        }

        $message .= '</div>';

        return $message;
    }

    public function getDescription(
        FormatterInterface $formatter,
        bool               $translate
    ): string
    {
        return $formatter->formatDescription($this->description, $translate);
    }

    /**
     * Componer un mensaje en formato texto
     */
    public function composeText(string $delimiter = PHP_EOL): string
    {
        $parts = [];

        if ($this->title) {
            $parts[] = $this->title;
        }

        if (count($this->description) !== 0) {
            $parts[] = implode($delimiter, $this->description);
        }

        if (count($this->footer) !== 0) {
            $parts[] = implode($delimiter, $this->footer);
        }

        return implode($delimiter, $parts);
    }
}
