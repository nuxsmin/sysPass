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

namespace SP\Domain\Account;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\Services\AccountSearchFilter;
use SP\Infrastructure\Database\QueryResult;
use SP\Mvc\Model\QueryCondition;

/**
 * Class AccountSearchService para la gestión de búsquedas de cuentas
 */
interface AccountSearchServiceInterface
{
    /**
     * Procesar los resultados de la búsqueda y crear la variable que contiene los datos de cada cuenta
     * a mostrar.
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function processSearchResults(AccountSearchFilter $accountSearchFilter): QueryResult;

    /**
     * Analizar la cadena de consulta por eqituetas especiales y devolver un objeto
     * QueryCondition con los filtros
     */
    public function analyzeQueryFilters(string $string): QueryCondition;

    public function getCleanString(): ?string;
}