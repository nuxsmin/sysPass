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

namespace SP\Core\Messages;

use SP\Domain\Core\Messages\FormatterInterface;

/**
 * Class LogMessage
 *
 * @package SP\Core\Messages
 */
final class LogMessage extends MessageBase
{
    /**
     * @var string Acción realizada
     */
    protected string $action;
    /**
     * @var array Detalles de la acción en formato "detalle : descripción"
     */
    protected array $details = [];
    protected int $descriptionCounter = 0;
    protected int $detailsCounter = 0;

    /**
     * Establece los detalles de la acción realizada
     */
    public function addDetails(string $key, string $value): LogMessage
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
     */
    private function formatString(string $string): string
    {
        return strip_tags($string);
    }

    /**
     * Establece la descripción de la acción realizada
     */
    public function addDescription(string $description = ''): LogMessage
    {
        $this->description[] = $this->formatString($description);

        return $this;
    }

    /**
     * Añadir una línea en blanco a la descripción
     */
    public function addDescriptionLine(): LogMessage
    {
        $this->description[] = '';
        $this->descriptionCounter++;

        return $this;
    }

    /**
     * Componer un mensaje en formato texto
     */
    public function composeText(string $delimiter = PHP_EOL): string
    {
        $formatter = new TextFormatter();

        $message[] = $this->getAction(true);
        $message[] = $this->getDescription($formatter, true);
        $message[] = $this->getDetails($formatter, true);

        return implode(PHP_EOL, $message);
    }

    /**
     * Devuelve la acción realizada
     */
    public function getAction(bool $translate = false): string
    {
        return $translate ? __($this->action) : $this->action;
    }

    /**
     * Establece la acción realizada
     */
    public function setAction(string $action): LogMessage
    {
        $this->action = $this->formatString($action);

        return $this;
    }

    /**
     * Devuelve la descripción de la acción realizada
     */
    public function getDescription(
        FormatterInterface $formatter,
        bool               $translate
    ): string
    {
        if (count($this->description) === 0) {
            return '';
        }

        return $formatter->formatDescription($this->description, $translate);
    }

    /**
     * Devuelve los detalles de la acción realizada
     */
    public function getDetails(
        FormatterInterface $formatter,
        bool               $translate
    ): string
    {
        if (count($this->details) === 0) {
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

        $message = '<div class="log-message">';
        $message .= '<h1>'.$this->action.'</h1>';
        $message .= '<div class="log-description">'.$this->getDescription($formatter, true).'</div>';
        $message .= '<div class="log-details">'.$this->getDetails($formatter, true).'</div>';
        $message .= '<footer>'.join('<br/>', $this->footer).'</footer>';
        $message .= '</div>';

        return $message;
    }

    /**
     * Restablecer la variable de descripcion
     */
    public function resetDescription(): LogMessage
    {
        $this->description = [];
        $this->descriptionCounter = 0;

        return $this;
    }

    /**
     * Restablecer la variable de detalles
     */
    public function resetDetails(): LogMessage
    {
        $this->details = [];
        $this->detailsCounter = 0;

        return $this;
    }

    public function getDescriptionCounter(): int
    {
        return $this->descriptionCounter;
    }

    public function getDetailsCounter(): int
    {
        return $this->detailsCounter;
    }
}
