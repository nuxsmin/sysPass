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

namespace SP\Domain\Html;

/**
 * Class Header
 */
enum Header: string
{
    case ETAG                   = 'Etag';
    case EXPIRES                = 'Expires';
    case CONTENT_TYPE           = 'Content-type';
    case PRAGMA                 = 'Pragma';
    case CACHE_CONTROL          = 'Cache-Control';
    case IF_NONE_MATCH          = 'If-None-Match';
    case CONTENT_TYPE_JSON      = 'application/json; charset=utf-8';
    case CONTENT_TYPE_CSS       = 'text/css; charset: UTF-8';
    case CONTENT_TYPE_JS        = 'application/javascript; charset: UTF-8';
    case ACCEPT                 = 'Accept';
    case ACCEPT_JSON            = 'application/json';
    case X_REQUESTED_WITH       = 'X-Requested-With';
    case HTTP_FORWARDED         = 'Http-Forwarded';
    case HTTP_X_FORWARDED_HOST  = 'Http-Forwarded-Host';
    case HTTP_X_FORWARDED_PROTO = 'Http-Forwarded-Proto';
    case HTTP_X_FORWARDED_FOR   = 'Http-Forwarded-For';
}
