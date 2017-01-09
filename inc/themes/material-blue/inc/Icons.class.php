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

use SP\Core\UI\ThemeIconsBase;
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
        // Iconos de Acciones
        $this->iconAdd = new FontIcon('add', 'mdl-color-text--indigo-A200', _('Añadir'));
        $this->iconView = new FontIcon('visibility', 'mdl-color-text--indigo-A200', _('Ver Detalles'));
        $this->iconViewPass = new FontIcon('lock_open', 'mdl-color-text--indigo-A200', _('Ver Clave'));
        $this->iconEdit = new FontIcon('mode_edit', 'mdl-color-text--amber-A200', _('Editar'));
        $this->iconDelete = new FontIcon('remove_circle', 'mdl-color-text--red-A200', _('Eliminar'));
        $this->iconEditPass = new FontIcon('lock_outline', 'mdl-color-text--amber-A200', _('Cambiar Clave'));
        $this->iconAppAdmin = new FontIcon('star', 'mdl-color-text--amber-A100', _('Admin Aplicación'));
        $this->iconAccAdmin = new FontIcon('star_half', 'mdl-color-text--amber-A100', _('Admin Cuentas'));
        $this->iconLdapUser = new FontIcon('business', 'mdl-color-text--deep-purple-A100', _('Usuario de LDAP'));
        $this->iconDisabled = new FontIcon('error', 'mdl-color-text--red-A100', _('Deshabilitado'));
        $this->iconEnabled = new FontIcon('check_circle', 'mdl-color-text--teal-500', _('Habilitado'));
        $this->iconRefresh = new FontIcon('refresh', 'mdl-color-text--teal-500', _('Actualizar'));
        $this->iconCopy = new FontIcon('content_copy', 'mdl-color-text--indigo-A200', _('Copiar'));
        $this->iconClipboard = new FontIcon('content_paste', 'mdl-color-text--indigo-A200');
        $this->iconEmail = new FontIcon('email', 'mdl-color-text--indigo-A200', _('Email'));
        $this->iconOptional = new FontIcon('settings');
        $this->iconPublicLink = new FontIcon('link', 'mdl-color-text--teal-500');
        $this->iconBack = new FontIcon('arrow_back', 'mdl-color-text--indigo-A200', _('Volver'));
        $this->iconRestore = new FontIcon('restore', 'mdl-color-text--teal-500', _('Restaurar'));
        $this->iconSave = new FontIcon('save', 'mdl-color-text--teal-500', _('Guardar'));
        $this->iconHelp = new FontIcon('help_outline', 'mdl-color-text--indigo-A100', _('Ayuda'));
        $this->iconClear = new FontIcon('clear_all', 'mdl-color--indigo-A200', _('Limpiar'));
        $this->iconPlay = new FontIcon('play_circle_filled', 'mdl-color-text--teal-500', _('Realizar'));
        $this->iconDownload = new FontIcon('file_download', 'mdl-color-text--indigo-A200', _('Descargar'));
        $this->iconWarning = new FontIcon('warning', 'mdl-color-text--amber-A100', _('Aviso'));
        $this->iconCheck = new FontIcon('cached', 'mdl-color-text--indigo-A200', _('Comprobar'));
        $this->iconSearch = new FontIcon('search', 'mdl-color-text--indigo-A200', _('Buscar'));
        $this->iconAccount = new FontIcon('account_box', 'mdl-color-text--indigo-A200');
        $this->iconGroup = new FontIcon('group_work', 'mdl-color-text--indigo-A200');
        $this->iconSettings = new FontIcon('settings_applications', 'mdl-color-text--indigo-A200', _('Configuración'));
        $this->iconHeadline = new FontIcon('view_headline', 'mdl-color-text--indigo-A200');
        $this->iconInfo = new FontIcon('info_outline', 'mdl-color-text--indigo-A200', _('Información'));
        $this->iconCritical = new FontIcon('error_outline', 'mdl-color-text--red-A200', _('Crítico'));
        $this->iconNotices = new FontIcon('notifications', 'mdl-color-text--indigo-A200', _('Notificaciones'));
        $this->iconRemove = new FontIcon('remove', 'mdl-color-text--indigo-A200', _('Eliminar'));

        // Iconos de Navegación
        $this->iconNavPrev = new FontIcon('chevron_left', null, _('Página anterior'));
        $this->iconNavNext = new FontIcon('chevron_right', null, _('Página siguiente'));
        $this->iconNavFirst = new FontIcon('arrow_back', null, _('Primera página'));
        $this->iconNavLast = new FontIcon('arrow_forward', null, _('Última página'));

        // Iconos de Ordenación
        $this->iconUp = new FontIcon('arrow_drop_up');
        $this->iconDown = new FontIcon('arrow_drop_down');
    }
}