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

namespace SP\Core\Events;

use SP\Core\Messages\FormatterInterface;
use SP\Core\Messages\HtmlFormatter;
use SP\Core\Messages\MessageInterface;
use SP\Core\Messages\TextFormatter;

/**
 * Class EventMessage
 *
 * @package SP\Core\Events
 */
final class EventMessage implements MessageInterface
{
    /**
     * @var array Detalles de la acción en formato "detalle : descripción"
     */
    protected $details = [];
    /**
     * @var int
     */
    protected $descriptionCounter = 0;
    /**
     * @var int
     */
    protected $detailsCounter = 0;
    /**
     * @var array
     */
    protected $description = [];
    /**
     * @var array
     */
    protected $extra = [];

    /**
     * @return static
     */
    public static function factory()
    {
        return new static();
    }

    /**
     * Devuelve la descripción
     *
     * @return array
     */
    public function getDescriptionRaw()
    {
        return $this->description;
    }

    /**
     * Establece los detalles de la acción realizada
     *
     * @param $key   string
     * @param $value string
     *
     * @return $this
     */
    public function addDetail($key, $value)
    {
        if ($value === '' || $key === '') {
            return $this;
        }

        $this->details[] = [$this->formatString($key), $this->formatString($value)];

        $this->detailsCounter++;

        return $this;
    }

    /**
     * Formatear una cadena para guardarla en el registro
     *
     * @param $string string La cadena a formatear
     *
     * @return string
     */
    private function formatString($string)
    {
        return strip_tags($string);
    }

    /**
     * Establece la descripción de la acción realizada
     *
     * @param string $description
     *
     * @return $this
     */
    public function addDescription($description = '')
    {
        $this->description[] = $this->formatString($description);

        $this->descriptionCounter++;

        return $this;
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
        if ($this->descriptionCounter === 0 && $this->detailsCounter === 0) {
            return '';
        }

        $formatter = new TextFormatter($delimiter);

        return implode($delimiter, array_filter([
            $this->getDescription($formatter, true),
            $this->getDetails($formatter, true)
        ]));
    }

    /**
     * Devuelve la descripción de la acción realizada
     *
     * @param FormatterInterface $formatter
     * @param bool               $translate
     *
     * @return string
     */
    public function getDescription(FormatterInterface $formatter, $translate = false)
    {
        if ($this->descriptionCounter === 0) {
            return '';
        }

        return $formatter->formatDescription($this->description, $translate);
    }

    /**
     * Devuelve los detalles de la acción realizada
     *
     * @param FormatterInterface $formatter
     * @param bool               $translate
     *
     * @return string
     */
    public function getDetails(FormatterInterface $formatter, bool $translate = false)
    {
        if ($this->detailsCounter === 0) {
            return '';
        }

        return $formatter->formatDetail($this->details, $translate);
    }

    /**
     * Devuelve los detalles
     *
     * @return array
     */
    public function getDetailsRaw()
    {
        return $this->details;
    }

    /**
     * Componer un mensaje en formato HTML
     *
     * @return mixed
     */
    public function composeHtml()
    {
        $formatter = new HtmlFormatter();

        $message = '<div class="event-message">';
        $message .= '<div class="event-description">' . $this->getDescription($formatter, true) . '</div>';
        $message .= '<div class="event-details">' . $this->getDetails($formatter, true) . '</div>';
        $message .= '</div>';

        return $message;
    }

    /**
     * @return int
     */
    public function getDescriptionCounter()
    {
        return $this->descriptionCounter;
    }

    /**
     * @return int
     */
    public function getDetailsCounter()
    {
        return $this->detailsCounter;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param       $type
     * @param array $data
     *
     * @return EventMessage
     */
    public function setExtra($type, array $data)
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
     * @param string $type
     * @param mixed  $data
     *
     * @return EventMessage
     */
    public function addExtra($type, $data)
    {
        if (isset($this->extra[$type])
            && in_array($data, $this->extra[$type])
        ) {
            return $this;
        }

        $this->extra[$type][] = $data;

        return $this;
    }
}