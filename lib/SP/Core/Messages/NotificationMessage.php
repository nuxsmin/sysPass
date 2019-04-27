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

namespace SP\Core\Messages;

/**
 * Class NoticeMessage
 *
 * @package SP\Core\Messages
 */
final class NotificationMessage extends MessageBase
{
    /**
     * Componer un mensaje en formato HTML
     *
     * @return string
     */
    public function composeHtml()
    {
        $formatter = new HtmlFormatter();

        $message = '<div class="notice-message" style="font-family: Helvetica, Arial, sans-serif">';

        if ($this->title) {
            $message .= '<h3>' . $this->title . '</h3>';
        }

        if (!empty($this->description)) {
            $message .= '<div class="notice-description">' . $this->getDescription($formatter) . '</div>';
        }

        if (!empty($this->footer)) {
            $message .= '<footer>' . implode('<br>', $this->footer) . '</footer>';
        }

        $message .= '</div>';

        return $message;
    }

    /**
     * @param FormatterInterface $formatter
     * @param bool               $translate
     *
     * @return string
     */
    public function getDescription(FormatterInterface $formatter, $translate = false): string
    {
        return $formatter->formatDescription($this->description, $translate);
    }

    /**
     * Componer un mensaje en formato texto
     *
     * @param string $delimiter
     *
     * @return string
     */
    public function composeText($delimiter = PHP_EOL)
    {
        $parts = [];

        if ($this->title) {
            $parts[] = $this->title;
        }

        if (!empty($this->description)) {
            $parts[] = implode($delimiter, $this->description);
        }

        if (!empty($this->footer)) {
            $parts[] = implode($delimiter, $this->footer);
        }

        return implode($delimiter, $parts);
    }
}