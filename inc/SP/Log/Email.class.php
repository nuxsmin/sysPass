<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Log;

use phpmailer\phpmailerException;
use SP\Config\Config;
use SP\Core\Init;
use SP\Core\Messages\LogMessage;
use SP\Core\Session;
use SP\Html\Html;
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
     * @param LogMessage $LogMessage con el objeto del tipo Log
     * @param string     $mailTo     con el destinatario
     * @param bool       $isEvent    para indicar si es um
     * @return bool
     */
    public static function sendEmail(LogMessage $LogMessage, $mailTo = '', $isEvent = true)
    {
        if (!Checks::mailIsEnabled()) {
            return false;
        }

        $Mail = self::getMailer($mailTo, $LogMessage->getAction());

        if (!is_object($Mail)) {
            return false;
        }

        $Mail->isHTML();

        if ($isEvent === true) {
            $performer = Session::getUserData()->getUserLogin() ?: __('N/D');
            $body[] = sprintf('%s: %s', Html::strongText(__('Acción')), $LogMessage->getAction());
            $body[] = sprintf('%s: %s (%s)', Html::strongText(__('Realizado por')), $performer, $_SERVER['REMOTE_ADDR']);

            $Mail->addCC(Config::getConfig()->getMailFrom());
        }

        $body[] = $LogMessage->getHtmlDescription(true);
        $body[] = $LogMessage->getHtmlDetails(true);
        $body[] = '';
        $body[] = '--';
        $body[] = sprintf('%s - %s', Util::getAppInfo('appname'), Util::getAppInfo('appdesc'));
        $body[] = Html::anchorText(Init::$WEBURI);


        $Mail->Body = implode(Log::NEWLINE_HTML, $body);

        $LogMessage = new LogMessage();
        $LogMessage->setAction(__('Enviar Email', false));

        try {
            $sendMail = $Mail->send();
        } catch (phpmailerException $e) {
            $LogMessage->addDetails(__('Error', false), $e->getMessage());
            $sendMail = false;
        }

        // Enviar correo
        if ($sendMail) {
            $LogMessage->addDescription(__('Correo enviado', false));
        } else {
            $LogMessage->addDescription(__('Error al enviar correo', false));
            $LogMessage->addDetails(__('Error', false), $Mail->ErrorInfo);
        }

        $LogMessage->addDescription(__('Destinatario', false) . ': ' . $mailTo);

        if ($isEvent === true) {
            $LogMessage->addDescription(__('CC', false) . ': ' . Config::getConfig()->getMailFrom());
        }

        $Log = new Log($LogMessage);
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
    private static function getMailer($mailTo, $action)
    {
        $appName = Util::getAppInfo('appname');
        $mailFrom = Config::getConfig()->getMailFrom();
        $mailServer = Config::getConfig()->getMailServer();
        $mailPort = Config::getConfig()->getMailPort();
        $mailAuth = Config::getConfig()->isMailAuthenabled();

        if ($mailAuth) {
            $mailUser = Config::getConfig()->getMailUser();
            $mailPass = Config::getConfig()->getMailPass();
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
        $Mail->SMTPSecure = strtolower(Config::getConfig()->getMailSecurity());
        //$mail->SMTPDebug = 2;
        //$mail->Debugoutput = 'error_log';

        $Mail->setFrom($mailFrom, $appName);
        $Mail->addAddress($mailTo);
        $Mail->addReplyTo($mailFrom, $appName);
        $Mail->WordWrap = 100;
        $Mail->Subject = sprintf('%s (%s) - %s', $appName, __('Aviso'), $action);

        return $Mail;
    }
}