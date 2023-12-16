<?php
/*
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

namespace SP\Core\Events;

use SP\Core\Messages\HtmlFormatter;
use SP\Core\Messages\TextFormatter;
use SP\Domain\Core\Messages\FormatterInterface;
use SP\Domain\Core\Messages\MessageInterface;

/**
 * Class EventMessage
 *
 * @template T
 */
class EventMessage implements MessageInterface
{
    /**
     * @var array Detalles de la acción en formato "detalle : descripción"
     */
    private array $details            = [];
    private int   $descriptionCounter = 0;
    private int   $detailsCounter     = 0;
    private array $description        = [];
    private array $extra              = [];

    /**
     * @return EventMessage
     */
    public static function factory(): EventMessage
    {
        return new self();
    }

    /**
     * Establece los detalles de la acción realizada
     */
    public function addDetail(string $key, ?string $value): EventMessage
    {
        if (empty($value) || empty($key)) {
            return $this;
        }

        $this->details[] = [$this->formatString($key), $this->formatString($value)];

        $this->detailsCounter++;

        return $this;
    }

    /**
     * Formatear una cadena para guardarla en el registro
     */
    private function formatString(string $string): string
    {
        return strip_tags($string);
    }

    /**
     * Establece la descripción de la acción realizada
     */
    public function addDescription(string $description = ''): EventMessage
    {
        $this->description[] = $this->formatString($description);

        $this->descriptionCounter++;

        return $this;
    }

    /**
     * Componer un mensaje en formato texto
     */
    public function composeText(string $delimiter = PHP_EOL): string
    {
        if ($this->descriptionCounter === 0 && $this->detailsCounter === 0) {
            return '';
        }

        $formatter = new TextFormatter($delimiter);

        return implode(
            $delimiter,
            array_filter([
                             $this->getDescription($formatter, true),
                             $this->getDetails($formatter, true)
                         ])
        );
    }

    /**
     * Devuelve la descripción de la acción realizada
     */
    public function getDescription(
        FormatterInterface $formatter,
        bool               $translate
    ): string {
        if ($this->descriptionCounter === 0) {
            return '';
        }

        return $formatter->formatDescription($this->description, $translate);
    }

    /**
     * Devuelve los detalles de la acción realizada
     */
    public function getDetails(
        FormatterInterface $formatter,
        bool               $translate = false
    ): string {
        if ($this->detailsCounter === 0) {
            return '';
        }

        return $formatter->formatDetail($this->details, $translate);
    }

    /**
     * Componer un mensaje en formato HTML
     */
    public function composeHtml(): string
    {
        $formatter = new HtmlFormatter();

        $message = '<div class="event-message">';
        $message .= '<div class="event-description">' . $this->getDescription($formatter, true) . '</div>';
        $message .= '<div class="event-details">' . $this->getDetails($formatter, true) . '</div>';
        $message .= '</div>';

        return $message;
    }

    public function getDescriptionCounter(): int
    {
        return $this->descriptionCounter;
    }

    public function getDetailsCounter(): int
    {
        return $this->detailsCounter;
    }

    /**
     * @param string|class-string<T> $type
     * @return array<T>|string|int|bool|null
     */
    public function getExtra(string $type): array|string|int|bool|null
    {
        return $this->extra[$type] ?? null;
    }

    /**
     * @param class-string<T> $type
     * @param array<T> $data
     */
    public function setExtra(string $type, array $data): EventMessage
    {
        if (isset($this->extra[$type])) {
            $this->extra[$type] = array_merge($this->extra[$type], $data);
        } else {
            $this->extra[$type] = $data;
        }

        return $this;
    }

    /**
     * Extra data are stored as an array of values per key, thus each key is unique
     *
     * @param class-string<T> $type
     * @param array<T>|string|int|bool $data
     */
    public function addExtra(string $type, array|string|int|bool|null $data): EventMessage
    {
        if (!isset($this->extra[$type]) || !in_array($data, $this->extra[$type])) {
            $this->extra[$type][] = $data;
        }

        return $this;
    }
}
