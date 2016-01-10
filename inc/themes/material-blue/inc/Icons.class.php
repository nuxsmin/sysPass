<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

namespace Theme;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ThemeIconsBase;
use SP\Html\Assets\FontIcon;

/**
 * Class Icons con los iconos del tema visual
 *
 * @package Theme
 */
class Icons extends ThemeIconsBase
{
    /**
     * Establecer los iconos utilizados en el DataGrid
     */
    public function setIcons()
    {
        $this->_iconAdd = new FontIcon('add', 'mdl-color-text--indigo-A200', _('Añadir'));
        $this->_iconView = new FontIcon('visibility', 'mdl-color-text--indigo-A200', _('Ver Detalles'));
        $this->_iconViewPass = new FontIcon('lock_open', 'mdl-color-text--indigo-A200', _('Ver Clave'));
        $this->_iconEdit = new FontIcon('mode_edit', 'mdl-color-text--amber-A200', _('Editar'));
        $this->_iconDelete = new FontIcon('delete', 'mdl-color-text--red-A200', _('Eliminar'));
        $this->_iconEditPass = new FontIcon('lock_outline', 'mdl-color-text--amber-A200', _('Cambiar Clave'));
        $this->_iconAppAdmin = new FontIcon('star', 'mdl-color-text--amber-A100', _('Admin Aplicación'));
        $this->_iconAccAdmin = new FontIcon('star_half', 'mdl-color-text--amber-A100', _('Admin Cuentas'));
        $this->_iconLdapUser = new FontIcon('business', 'mdl-color-text--deep-purple-A100', _('Usuario de LDAP'));
        $this->_iconDisabled = new FontIcon('error', 'mdl-color-text--red-A100', _('Deshabilitado'));
        $this->_iconRefresh = new FontIcon('refresh', 'mdl-color-text--teal-500', _('Actualizar'));
        $this->_iconCopy = new FontIcon('content_copy', 'mdl-color-text--indigo-A200', _('Copiar'));
        $this->_iconClipboard = new FontIcon('content_paste', 'mdl-color-text--indigo-A200');
        $this->_iconEmail = new FontIcon('email', 'mdl-color-text--indigo-A200', _('Email'));
        $this->_iconOptional = new FontIcon('settings');
        $this->_iconPublicLink = new FontIcon('link', 'mdl-color-text--teal-500');
        $this->_iconBack = new FontIcon('arrow_back', 'mdl-color-text--indigo-A200', _('Volver'));
        $this->_iconRestore = new FontIcon('restore', 'mdl-color-text--teal-500', ('Restaurar'));
        $this->_iconSave = new FontIcon('save', 'mdl-color-text--teal-500', _('Guardar'));
        $this->_iconHelp = new FontIcon('help_outline', 'mdl-color-text--indigo-A100', _('Ayuda'));
        $this->_iconClear = new FontIcon('clear_all', 'mdl-color--indigo-A200', _('Limpiar'));
        $this->_iconPlay = new FontIcon('play_circle_filled', 'mdl-color-text--teal-500', _('Realizar'));
        $this->_iconDownload = new FontIcon('file_download', 'mdl-color-text--indigo-A200', _('Descargar'));
        $this->_iconWarning = new FontIcon('warning', 'mdl-color-text--amber-A100', _('Aviso'));

        // Iconos de Navegación
        $this->_iconNavPrev = new FontIcon('chevron_left', null, _('Página anterior'));
        $this->_iconNavNext = new FontIcon('chevron_right', null, _('Página siguiente'));
        $this->_iconNavFirst = new FontIcon('arrow_back', null, _('Primera página'));
        $this->_iconNavLast = new FontIcon('arrow_forward', null, _('Última página'));

        // Iconos de Ordenación
        $this->_iconUp = new FontIcon('arrow_drop_up');
        $this->_iconDown = new FontIcon('arrow_drop_down');
    }
}