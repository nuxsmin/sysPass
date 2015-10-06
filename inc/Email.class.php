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

namespace SP;

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
        if (!Util::mailIsEnabled()) {
            return false;
        }

        $mail = self::getEmailObject($mailTo, utf8_decode($log->getAction()));

        if (!is_object($mail)) {
            return false;
        }

        $mail->isHTML();
        $log->setNewLineHtml(true);

        if ($isEvent === true) {
            $performer = (Session::getUserLogin()) ? Session::getUserLogin() : _('N/D');
            $body[] = Html::strongText(_('Acción') . ": ") . utf8_decode($log->getAction());
            $body[] = Html::strongText(_('Realizado por') . ": ") . $performer . ' (' . $_SERVER['REMOTE_ADDR'] . ')';

            $mail->addCC(Config::getValue('mail_from'));
        }

        $body[] = utf8_decode($log->getDescription());
        $body[] = '';
        $body[] = '--';
        $body[] = Util::getAppInfo('appname') . ' - ' . Util::getAppInfo('appdesc');
        $body[] = Html::anchorText(Init::$WEBURI);


        $mail->Body = implode(Log::NEWLINE_HTML, $body);

        $sendMail = $mail->send();

        $logEmail = new Log(_('Enviar Email'));

        // Enviar correo
        if ($sendMail) {
            $logEmail->addDescription(_('Correo enviado'));
        } else {
            $logEmail->addDescription(_('Error al enviar correo'));
            $logEmail->addDescription('ERROR: ' . $mail->ErrorInfo);
        }

        $logEmail->addDescription(_('Destinatario') . ': ' . $mailTo);

        if ($isEvent === true){
            $logEmail->addDescription(_('CC') . ': ' . Config::getValue('mail_from'));
        }

        $logEmail->writeLog();

        return $sendMail;
    }

    /**
     * Inicializar la clase PHPMailer.
     *
     * @param string $mailTo con la dirección del destinatario
     * @param string $action con la acción realizada
     * @return false|\PHPMailer
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

        require_once EXTENSIONS_PATH . '/phpmailer/class.phpmailer.php';
        require_once EXTENSIONS_PATH . '/phpmailer/class.smtp.php';

        $mail = new \PHPMailer();

        $mail->isSMTP();
        $mail->CharSet = 'utf-8';
        $mail->Host = $mailServer;
        $mail->Port = $mailPort;
        if ($mailAuth) {
            $mail->SMTPAuth = $mailAuth;
            $mail->Username = $mailUser;
            $mail->Password = $mailPass;
        }
        $mail->SMTPSecure = strtolower(Config::getValue('mail_security'));
        //$mail->SMTPDebug = 2;
        //$mail->Debugoutput = 'error_log';

        $mail->setFrom($mailFrom, $appName);
        $mail->addAddress($mailTo);
        $mail->addReplyTo($mailFrom, $appName);
        $mail->WordWrap = 100;
        $mail->Subject = $appName . ' (' . _('Aviso') . ') - ' . $action;

        return $mail;
    }
}