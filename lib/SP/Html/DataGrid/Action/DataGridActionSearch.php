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

namespace SP\Html\DataGrid\Action;

defined('APP_ROOT') || die();

/**
 * Class DataGridActionSearch para definir una acción de búsqueda de datos
 *
 * @package SP\Html\DataGrid
 */
final class DataGridActionSearch extends DataGridActionBase
{
    /**
     * @var string
     */
    private $onSubmitFunction = '';

    /**
     * Los argumentos de la función OnSubmit
     *
     * @var array
     */
    private $onSubmitArgs = [];

    /**
     * DataGridActionSearch constructor.
     *
     * @param int $id EL id de la acción
     */
    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->setSkip(true);
    }

    /**
     * @return string
     */
    public function getOnSubmit()
    {
        $args = [];

        foreach ($this->onSubmitArgs as $arg) {
            $args[] = (!is_numeric($arg) && $arg !== 'this') ? '\'' . $arg . '\'' : $arg;
        }

        return count($args) > 0 ? 'return ' . $this->onSubmitFunction . '(' . implode(',', $args) . ');' : $this->onSubmitFunction;
    }

    /**
     * @param string $onSubmitFunction
     */
    public function setOnSubmitFunction($onSubmitFunction)
    {
        $this->onSubmitFunction = $onSubmitFunction;
    }

    /**
     * @param array $args
     */
    public function setOnSubmitArgs($args)
    {
        $this->onSubmitArgs[] = $args;
    }
}