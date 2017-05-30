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

use phpmailer\PHPMailer;
use phpmailer\phpmailerException;
use SP\Config\Config;
use SP\Core\Init;
use SP\Core\Messages\LogMessage;
use SP\Core\Messages\NoticeMessage;
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
     * @param string $mailTo con el destinatario
     * @param bool $isEvent para indicar si es um
     * @return bool
     */
    public static function sendEmail(LogMessage $LogMessage, $mailTo = '', $isEvent = true)
    {
        if (!Checks::mailIsEnabled()) {
            return false;
        }

        $Mail = self::getMailer($mailTo, $LogMessage->getAction(true));

        if ($isEvent === true) {
            $performer = Session::getUserData()->getUserLogin() ?: __('N/D');
            $body[] = sprintf('%s: %s', Html::strongText(__('Acción')), $LogMessage->getAction(true));
            $body[] = sprintf('%s: %s (%s)', Html::strongText(__('Realizado por')), $performer, Util::getClientAddress(true));

            $Mail->addCC(Config::getConfig()->getMailFrom());
        }

        $body[] = $LogMessage->getHtmlDescription(true);
        $body[] = $LogMessage->getHtmlDetails(true);

        $Mail->isHTML();
        $Mail->Body = implode(Log::NEWLINE_HTML, array_merge($body, Email::getEmailFooter()));

        $LogMessage = new LogMessage();
        $LogMessage->setAction(__('Enviar Email', false));
        $Log = new Log($LogMessage);

        try {
            $Mail->send();
            $LogMessage->addDescription(__('Correo enviado', false));
            $LogMessage->addDetails(__('Destinatario', false), $mailTo);

            if ($isEvent === true) {
                $LogMessage->addDetails(__('CC', false), Config::getConfig()->getMailFrom());
            }

            $Log->writeLog();
            return true;
        } catch (phpmailerException $e) {
            $LogMessage->addDescription(__('Error al enviar correo', false));
            $LogMessage->addDetails(__('Error', false), $e->getMessage());
            $LogMessage->addDetails(__('Error', false), $Mail->ErrorInfo);
            $Log->writeLog();
        }

        return false;
    }

    /**
     * Inicializar la clase PHPMailer.
     *
     * @param string $mailTo con la dirección del destinatario
     * @param string $action con la acción realizada
     * @return false|PHPMailer
     */
    private static function getMailer($mailTo, $action)
    {
        $appName = Util::getAppInfo('appname');
        $mailFrom = Config::getConfig()->getMailFrom();
        $mailServer = Config::getConfig()->getMailServer();
        $mailPort = Config::getConfig()->getMailPort();
        $mailAuth = Config::getConfig()->isMailAuthenabled();

        if (empty($mailTo)) {
            $mailTo = $mailFrom;
        }

        $Mail = new PHPMailer();

        $Mail->SMTPAutoTLS = false;
        $Mail->isSMTP();
        $Mail->CharSet = 'utf-8';
        $Mail->Host = $mailServer;
        $Mail->Port = $mailPort;

        if ($mailAuth) {
            $Mail->SMTPAuth = $mailAuth;
            $Mail->Username = Config::getConfig()->getMailUser();
            $Mail->Password = Config::getConfig()->getMailPass();
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

    /**
     * Devolver el pie del email con la firma de la aplicación
     *
     * @return array
     */
    protected static function getEmailFooter()
    {
        return [
            '',
            '--',
            sprintf('%s - %s', Util::getAppInfo('appname'), Util::getAppInfo('appdesc')),
            Html::anchorText(Init::$WEBURI)
        ];
    }

    /**
     * Enviar un correo a varios destinatarios.
     *
     * Se envía en copia oculta.
     *
     * @param NoticeMessage $Message
     * @param array $mailTo
     * @return bool
     */
    public static function sendEmailBatch(NoticeMessage $Message, array $mailTo)
    {
        if (!Checks::mailIsEnabled()) {
            return false;
        }

        $Mail = self::getMailer(Config::getConfig()->getMailFrom(), $Message->getTitle());
        $Mail->isHTML();

        foreach ($mailTo as $recipient) {
            $Mail->addBCC($recipient->user_email, $recipient->user_name);
        }

        if (empty($Message->getFooter())) {
            $Message->setFooter(self::getEmailFooter());
        }

        $Mail->Body = $Message->composeHtml();
        $Mail->AltBody = $Message->composeText();

        $LogMessage = new LogMessage();
        $LogMessage->setAction(__('Enviar Email', false));
        $LogMessage->addDetails(__('Destinatario', false), implode(';', array_keys($Mail->getAllRecipientAddresses())));

        $Log = new Log($LogMessage);

        try {
            $Mail->send();
            $LogMessage->addDescription(__('Correo enviado', false));
            $Log->writeLog();
            return true;
        } catch (phpmailerException $e) {
            $LogMessage->addDescription(__('Error al enviar correo', false));
            $LogMessage->addDetails(__('Error', false), $e->getMessage());
            $LogMessage->addDetails(__('Error', false), $Mail->ErrorInfo);
            $Log->writeLog();
        }

        return false;
    }
}