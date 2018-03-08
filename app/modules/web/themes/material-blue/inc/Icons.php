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

defined('APP_ROOT') || die();

use SP\Core\UI\ThemeIcons;
use SP\Html\Assets\FontIcon;

$themeIcons = new ThemeIcons();

// Iconos de Acciones
$themeIcons->iconAdd = new FontIcon('add', 'mdl-color-text--indigo-A200', __u('Añadir'));
$themeIcons->iconView = new FontIcon('visibility', 'mdl-color-text--indigo-A200', __u('Ver Detalles'));
$themeIcons->iconViewPass = new FontIcon('lock_open', 'mdl-color-text--indigo-A200', __u('Ver Clave'));
$themeIcons->iconEdit = new FontIcon('mode_edit', 'mdl-color-text--amber-A200', __u('Editar'));
$themeIcons->iconDelete = new FontIcon('remove_circle', 'mdl-color-text--red-A200', __u('Eliminar'));
$themeIcons->iconEditPass = new FontIcon('lock_outline', 'mdl-color-text--amber-A200', __u('Cambiar Clave'));
$themeIcons->iconAppAdmin = new FontIcon('star', 'mdl-color-text--amber-A100', __u('Admin Aplicación'));
$themeIcons->iconAccAdmin = new FontIcon('star_half', 'mdl-color-text--amber-A100', __u('Admin Cuentas'));
$themeIcons->iconLdapUser = new FontIcon('business', 'mdl-color-text--deep-purple-A100', __u('Usuario de LDAP'));
$themeIcons->iconDisabled = new FontIcon('error', 'mdl-color-text--red-A100', __u('Deshabilitado'));
$themeIcons->iconEnabled = new FontIcon('check_circle', 'mdl-color-text--teal-500', __u('Habilitado'));
$themeIcons->iconRefresh = new FontIcon('refresh', 'mdl-color-text--teal-500', __u('Actualizar'));
$themeIcons->iconCopy = new FontIcon('content_copy', 'mdl-color-text--indigo-A200', __u('Copiar'));
$themeIcons->iconClipboard = new FontIcon('content_paste', 'mdl-color-text--indigo-A200');
$themeIcons->iconEmail = new FontIcon('email', 'mdl-color-text--indigo-A200', __u('Email'));
$themeIcons->iconOptional = new FontIcon('settings');
$themeIcons->iconPublicLink = new FontIcon('link', 'mdl-color-text--teal-500');
$themeIcons->iconBack = new FontIcon('arrow_back', 'mdl-color-text--indigo-A200', __u('Volver'));
$themeIcons->iconRestore = new FontIcon('restore', 'mdl-color-text--teal-500', __u('Restaurar'));
$themeIcons->iconSave = new FontIcon('save', 'mdl-color-text--teal-500', __u('Guardar'));
$themeIcons->iconHelp = new FontIcon('help_outline', 'mdl-color-text--indigo-A100', __u('Ayuda'));
$themeIcons->iconClear = new FontIcon('clear_all', 'mdl-color--indigo-A200', __u('Limpiar'));
$themeIcons->iconPlay = new FontIcon('play_circle_filled', 'mdl-color-text--teal-500', __u('Realizar'));
$themeIcons->iconDownload = new FontIcon('file_download', 'mdl-color-text--indigo-A200', __u('Descargar'));
$themeIcons->iconWarning = new FontIcon('warning', 'mdl-color-text--amber-A100', __u('Aviso'));
$themeIcons->iconCheck = new FontIcon('cached', 'mdl-color-text--indigo-A200', __u('Comprobar'));
$themeIcons->iconSearch = new FontIcon('search', 'mdl-color-text--indigo-A200', __u('Buscar'));
$themeIcons->iconAccount = new FontIcon('account_box', 'mdl-color-text--indigo-A200');
$themeIcons->iconGroup = new FontIcon('group_work', 'mdl-color-text--indigo-A200');
$themeIcons->iconSettings = new FontIcon('settings', 'mdl-color-text--indigo-A200', __u('Configuración'));
$themeIcons->iconHeadline = new FontIcon('view_headline', 'mdl-color-text--indigo-A200');
$themeIcons->iconInfo = new FontIcon('info_outline', 'mdl-color-text--indigo-A200', __u('Información'));
$themeIcons->iconCritical = new FontIcon('error_outline', 'mdl-color-text--red-A200', __u('Crítico'));
$themeIcons->iconNotices = new FontIcon('notifications', 'mdl-color-text--indigo-A200', __u('Notificaciones'));
$themeIcons->iconRemove = new FontIcon('remove', 'mdl-color-text--indigo-A200', __u('Eliminar'));

// Iconos de Navegación
$themeIcons->iconNavPrev = new FontIcon('chevron_left', null, __u('Página anterior'));
$themeIcons->iconNavNext = new FontIcon('chevron_right', null, __u('Página siguiente'));
$themeIcons->iconNavFirst = new FontIcon('arrow_back', null, __u('Primera página'));
$themeIcons->iconNavLast = new FontIcon('arrow_forward', null, __u('Última página'));

// Iconos de Ordenación
$themeIcons->iconUp = new FontIcon('arrow_drop_up');
$themeIcons->iconDown = new FontIcon('arrow_drop_down');

return $themeIcons;