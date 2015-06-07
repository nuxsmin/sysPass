<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

/**
 * Clase base para los controladores
 */
abstract class SP_Controller
{
    /**
     * Constantes de errores
     */
    const ERR_UNAVAILABLE = 0;
    const ERR_ACCOUNT_NO_PERMISSION = 1;
    const ERR_PAGE_NO_PERMISSION = 2;
    const ERR_UPDATE_MPASS = 3;
    const ERR_OPERATION_NO_PERMISSION = 4;
    /**
     * @var Nombre del controlador
     */
    public $controller;
    /**
     * @var Instancia del motor de plantillas a utilizar
     */
    public $view;
    /**
     * @var Módulo a usar
     */
    protected $_action; // FIXME: revisar visibilidad

    /**
     * Constructor
     *
     * @param $template SP_Template con instancia de plantilla
     */
    public function __construct(\SP_Template $template = null)
    {
        global $timeStart;

        if (is_null($template)) {
            $this->view = $this->getTemplate();
        } else {
            $this->view = $template;
        }

        $this->view->assign('timeStart', $timeStart);
    }

    /**
     * Obtener una nueva instancia del motor de plantillas.
     *
     * @param null $template string con el nombre de la plantilla
     * @return SP_Template
     */
    protected function getTemplate($template = null)
    {
        return new SP_Template($template);
    }

    /**
     * Renderizar los datos de la plantilla
     */
    public function view()
    {
        echo $this->view->render();
    }

    /**
     * Establecer la instancia del motor de plantillas a utilizar.
     *
     * @param SP_Template $template
     */
    protected function setTemplate(SP_Template $template)
    {
        $this->view = $template;
    }

    /**
     * Establecer el módulo a presentar.
     *
     * @param $action int con el número de módulo
     */
    protected function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @return bool
     */
    protected function checkAccess()
    {
        if (!\SP_Acl::checkUserAccess($this->_action)) {
            $this->showError(self::ERR_PAGE_NO_PERMISSION);
            return false;
        }

        return true;
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param $type int con el tipo de error
     */
    protected function showError($type, $reset = true, $fancy = false)
    {
        $errorsTypes = array(
            self::ERR_UNAVAILABLE => array('txt' => _('Opción no disponible'), 'hint' => _('Consulte con el administrador')),
            self::ERR_ACCOUNT_NO_PERMISSION => array('txt' => _('No tiene permisos para acceder a esta cuenta'), 'hint' => _('Consulte con el administrador')),
            self::ERR_PAGE_NO_PERMISSION => array('txt' => _('No tiene permisos para acceder a esta página'), 'hint' => _('Consulte con el administrador')),
            self::ERR_OPERATION_NO_PERMISSION => array('txt' => _('No tiene permisos para realizar esta operación'), 'hint' => _('Consulte con el administrador')),
            self::ERR_UPDATE_MPASS => array('txt' => _('Clave maestra actualizada'), 'hint' => _('Reinicie la sesión para cambiarla'))
        );

        if ($reset) {
            $this->view->resetTemplates();
        }

        if ($fancy){
            $this->view->addTemplate('errorfancy');
        } else {
            $this->view->addTemplate('error');
        }

        $this->view->append('errors',
            array(
                'type' => 'critical',
                'description' => $errorsTypes[$type]['txt'],
                'hint' => $errorsTypes[$type]['hint'])
        );
    }

    /**
     * Obtener los datos para la vista de depuración
     */
    public function getDebug()
    {
        global $memInit;

        $this->view->addTemplate('debug');

        $this->view->assign('time', (SP_Init::microtime_float() - $this->view->timeStart));
        $this->view->assign('memInit', $memInit / 1000);
        $this->view->assign('memEnd', memory_get_usage() / 1000);
    }
}