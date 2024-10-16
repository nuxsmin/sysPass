<?php
declare(strict_types=1);
/**
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

namespace SP\Core\Messages;

use SP\Domain\Core\Messages\FormatterInterface;
use SP\Domain\Html\Html;

use function SP\__;

/**
 * Class HtmlFormatter
 *
 * @package SP\Core\Messages
 */
final class HtmlFormatter implements FormatterInterface
{

    public function formatDetail(array $text, bool $translate = false): string
    {
        return implode(
            '',
            array_map(
                function ($value) use ($translate) {
                    $right = $this->buildLink($value[1]);
                    $left = $translate ? __($value[0]) : $value[0];

                    if (!str_contains($right, '<a')) {
                        $right = $translate ? __($right) : $right;
                    }

                    return '<div class="detail">'
                           . '<span class="detail-left">' . $left . '</span>'
                           . '<span class="detail-right">' . $right . '</span>'
                           . '</div>';
                },
                $text
            )
        );
    }

    /**
     * Detects a link within the string and builds an HTML link
     */
    private function buildLink(string $text): string
    {
        if (preg_match('#^https?://.*$#', $text, $matches)) {
            return sprintf(
                '<a href="%s">%s</a>',
                $matches[0],
                Html::truncate($matches[0], 30)
            );
        }

        return $text;
    }

    public function formatDescription(
        array $text,
        bool  $translate = false
    ): string {
        return implode(
            '',
            array_map(
                static function ($value) use ($translate) {
                    return '<div class="description-line">' . ($translate ? __($value) : $value) . '</div>';
                },
                $text
            )
        );
    }
}
