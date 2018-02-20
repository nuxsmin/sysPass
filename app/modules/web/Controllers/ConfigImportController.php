<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;


use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Import\FileImport;
use SP\Services\Import\ImportParams;
use SP\Services\Import\ImportService;

/**
 * Class ConfigImportController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigImportController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function importAction()
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, esto es una DEMO!!'));
        }

        $importParams = new ImportParams();
        $importParams->setDefaultUser(Request::analyze('import_defaultuser', $this->session->getUserData()->getId()));
        $importParams->setDefaultGroup(Request::analyze('import_defaultgroup', $this->session->getUserData()->getUserGroupId()));
        $importParams->setImportPwd(Request::analyzeEncrypted('importPwd'));
        $importParams->setImportMasterPwd(Request::analyzeEncrypted('importMasterPwd'));
        $importParams->setCsvDelimiter(Request::analyze('csvDelimiter'));

        try {
            $importService = $this->dic->get(ImportService::class);
            $counter = $importService->doImport($importParams, new FileImport($this->router->request()->files()->get('inFile')));

            if ($counter > 0) {
                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Importación finalizada'), [__u('Revise el registro de eventos para más detalles')]);
            } else {
                $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('No se importaron cuentas'), [__u('Revise el registro de eventos para más detalles')]);
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }
}