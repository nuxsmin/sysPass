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
 * Class MailMessage
 *
 * @package SP\Core\Messages
 */
final class MailMessage extends MessageBase implements MessageInterface
{
    /**
     * Adds a blank description line
     */
    public function addDescriptionLine()
    {
        $this->description[] = '';
    }

    /**
     * Componer un mensaje en formato HTML
     *
     * @return string
     */
    public function composeHtml()
    {
        $formatter = new HtmlFormatter();

        $message = '<div class="mail-message" style="font-family: Helvetica, Arial, sans-serif">';
        $message .= '<h3>' . $this->title . '</h3>';
        $message .= '<div class="mail-description">' . $this->getDescription($formatter, true) . '</div>';
        $message .= '<footer>' . implode('<br>', $this->footer) . '</footer>';
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
        $formatter = new TextFormatter($delimiter);

        return $this->title
            . $delimiter
            . $this->getDescription($formatter, true)
            . $delimiter
            . implode($delimiter, $this->footer);
    }
}