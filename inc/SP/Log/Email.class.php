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

namespace SP\Log;

use PHPMailer;
use SP\Config\Config;
use SP\Html\Html;
use SP\Core\Init;
use SP\Core\Session;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Clase Email para la gestión de envío de correos de notificación
 *
 * @package SP
 */
class Email
{
    /**
     * Enviar un email utilizando la clase PHPMailer.
     *
     * @param Log    $log     con el objeto del tipo Log
     * @param string $mailTo  con el destinatario
     * @param bool   $isEvent para indicar si es um
     * @return bool
     */
    public static function sendEmail(Log $log, $mailTo = '', $isEvent = true)
    {
        if (!Checks::mailIsEnabled()) {
            return false;
        }

        $Mail = self::getEmailObject($mailTo, utf8_decode($log->getAction()));

        if (!is_object($Mail)) {
            return false;
        }

        $Mail->isHTML();
        $log->setNewLineHtml(true);

        if ($isEvent === true) {
            $performer = (Session::getUserLogin()) ? Session::getUserLogin() : _('N/D');
            $body[] = Html::strongText(_('Acción') . ": ") . utf8_decode($log->getAction());
            $body[] = Html::strongText(_('Realizado por') . ": ") . $performer . ' (' . $_SERVER['REMOTE_ADDR'] . ')';

            $Mail->addCC(Config::getValue('mail_from'));
        }

        $body[] = utf8_decode($log->getDescription());
        $body[] = utf8_decode($log->getDetails());
        $body[] = '';
        $body[] = '--';
        $body[] = Util::getAppInfo('appname') . ' - ' . Util::getAppInfo('appdesc');
        $body[] = Html::anchorText(Init::$WEBURI);


        $Mail->Body = implode(Log::NEWLINE_HTML, $body);

        $sendMail = $Mail->send();

        $Log = new Log(_('Enviar Email'));

        // Enviar correo
        if ($sendMail) {
            $Log->addDescription(_('Correo enviado'));
        } else {
            $Log->addDescription(_('Error al enviar correo'));
            $Log->addDescription('ERROR: ' . $Mail->ErrorInfo);
        }

        $Log->addDescription(_('Destinatario') . ': ' . $mailTo);

        if ($isEvent === true){
            $Log->addDescription(_('CC') . ': ' . Config::getValue('mail_from'));
        }

        $Log->writeLog();

        return $sendMail;
    }

    /**
     * Inicializar la clase PHPMailer.
     *
     * @param string $mailTo con la dirección del destinatario
     * @param string $action con la acción realizada
     * @return false|\phpmailer\PHPMailer
     */
    private static function getEmailObject($mailTo, $action)
    {
        $appName = Util::getAppInfo('appname');
        $mailFrom = Config::getValue('mail_from');
        $mailServer = Config::getValue('mail_server');
        $mailPort = Config::getValue('mail_port', 25);
        $mailAuth = Config::getValue('mail_authenabled', FALSE);

        if ($mailAuth) {
            $mailUser = Config::getValue('mail_user');
            $mailPass = Config::getValue('mail_pass');
        }

        if (!$mailServer) {
            return false;
        }

        if (empty($mailTo)) {
            $mailTo = $mailFrom;
        }

        $Mail = new \phpmailer\PHPMailer();

        $Mail->isSMTP();
        $Mail->CharSet = 'utf-8';
        $Mail->Host = $mailServer;
        $Mail->Port = $mailPort;
        if ($mailAuth) {
            $Mail->SMTPAuth = $mailAuth;
            $Mail->Username = $mailUser;
            $Mail->Password = $mailPass;
        }
        $Mail->SMTPSecure = strtolower(Config::getValue('mail_security'));
        //$mail->SMTPDebug = 2;
        //$mail->Debugoutput = 'error_log';

        $Mail->setFrom($mailFrom, $appName);
        $Mail->addAddress($mailTo);
        $Mail->addReplyTo($mailFrom, $appName);
        $Mail->WordWrap = 100;
        $Mail->Subject = $appName . ' (' . _('Aviso') . ') - ' . $action;

        return $Mail;
    }
}