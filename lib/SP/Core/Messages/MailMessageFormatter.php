<?php

declare(strict_types=1);
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

namespace SP\Core\Messages;

use SP\Domain\Notification\Dtos\MailMessage;
use SP\Domain\Notification\Ports\MailMessageFormatterInterface;

use function SP\__;

/**
 * Composes mail messages using the application formatters.
 */
final class MailMessageFormatter implements MailMessageFormatterInterface
{
    public function composeText(MailMessage $message, string $delimiter = PHP_EOL): string
    {
        $formatter = new TextFormatter($delimiter);

        return __($message->getTitle())
               . $delimiter
               . $message->getDescription($formatter, true)
               . $delimiter
               . implode($delimiter, $message->getFooter());
    }

    public function composeHtml(MailMessage $message): string
    {
        $formatter = new HtmlFormatter();

        $output = '<div class="mail-message" style="font-family: Helvetica, Arial, sans-serif">';
        $output .= '<h3>' . __($message->getTitle()) . '</h3>';
        $output .= '<div class="mail-description">' . $message->getDescription($formatter, true) . '</div>';
        $output .= '<footer>' . implode('<br>', $message->getFooter()) . '</footer>';
        $output .= '</div>';

        return $output;
    }
}
