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

use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Messages\EventMessageFormatterInterface;

/**
 * Composes event messages using the application formatters.
 */
final class EventMessageFormatter implements EventMessageFormatterInterface
{
    /**
     * @param EventMessage<mixed> $message
     */
    public function composeText(EventMessage $message, string $delimiter = PHP_EOL): string
    {
        if ($message->getDescriptionCounter() === 0 && $message->getDetailsCounter() === 0) {
            return '';
        }

        $formatter = new TextFormatter($delimiter);

        return implode(
            $delimiter,
            array_filter([
                             $message->getDescription($formatter, true),
                             $message->getDetails($formatter, true)
                         ])
        );
    }

    /**
     * @param EventMessage<mixed> $message
     */
    public function composeHtml(EventMessage $message): string
    {
        $formatter = new HtmlFormatter();

        $output = '<div class="event-message">';
        $output .= '<div class="event-description">' . $message->getDescription($formatter, true) . '</div>';
        $output .= '<div class="event-details">' . $message->getDetails($formatter, true) . '</div>';
        $output .= '</div>';

        return $output;
    }
}
