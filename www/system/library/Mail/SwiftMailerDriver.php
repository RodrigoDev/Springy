<?php
/**	\file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright  Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *
 *  \brief      Class driver for use with Swift Mailer class
 *  \see        http://swiftmailer.org/
 *  \warning    This file is part of the framework and can not be omitted
 *  \version    1.0.1
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \ingroup    framework
 */
namespace FW\Mail;

use FW\Configuration;
use FW\Errors;
use FW\Kernel;

/**
 *  \brief Driver class for sent mail using Swift Mailer class.
 *
 *  \note This classe is a driver used by FW\Mail classe.
 *        Do not use it directly.
 */
class SwiftMailerDriver implements MailDriverInterface
{
    private $mailObj = null;

    /**
     *  \brief Constructor method
     *  \param $cfg - array with de configuration.
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['protocol'])) {
            throw new \Exception('Mail configuration \'protocol\' undefined');
        }

        $this->mailObj = \Swift_Message::newInstance();
        $this->mailObj->setCharset(Kernel::charset());

        if ($cfg['protocol'] == 'smtp') {
            if (!isset($cfg['host'])) {
                throw new \Exception('Mail configuration \'host\' undefined');
            }

            $this->transport = \Swift_SmtpTransport::newInstance($cfg['host'], isset($cfg['port']) ? $cfg['port'] : 25);

            if (isset($cfg['cryptography'])) {
                $this->transport->setEncryption($cfg['cryptography']);
            }
            if (isset($cfg['username']) && $cfg['username']) {
                $this->transport->setUsername($cfg['username']);
                $this->transport->setPassword(isset($cfg['password']) ? $cfg['password'] : '');
            }
        } elseif ($cfg['protocol'] == 'sendmail') {
            $this->transport = \Swift_SendmailTransport::newInstance(isset($cfg['sendmail_path']) ? $cfg['sendmail_path'] : null);
        } elseif ($cfg['protocol'] == 'mail') {
            $this->transport = \Swift_MailTransport::newInstance();
        } else {
            throw new \Exception('Unsuported mail transport agent');
        }

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->mailObj->setReturnPath(Configuration::get('mail', 'errors_go_to'));
        }
        $this->mailObj->addTextHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
    }

    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->addTextHeader($header, $value);
    }

    /**
     *  \brief Add an address to 'To' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '')
    {
        $this->mailObj->addTo($email, $name);
    }

    /**
     *  \brief Add an address to 'BCC' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addBCC($email, $name = '')
    {
        $this->mailObj->addBcc($email, $name);
    }

    /**
     *  \brief Add an address to 'CC' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addCC($email, $name = '')
    {
        $this->mailObj->addCc($email, $name);
    }

    /**
     *  \brief Add a file to be attached to the e-mail.
     *  
     *  \param $path - full pathname to the attachment
     *  \param $name - override the attachment name (optional)
     *  \param $type - MIME type/file extension type (optional)
     *  \param $encoding - file enconding (optional)
     */
    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64')
    {
        $attachment = \Swift_Attachment::fromPath($path, $type);
        if ($name) {
            $attachment->setFilename($name);
        }
        $this->mailObj->attach($attachment);
    }

    /**
     *  \brief Set the mail subject.
     *  
     *  \param $subject - the subject text
     */
    public function setSubject($subject)
    {
        $this->mailObj->setSubject($subject);
    }

    /**
     *  \brief Set the 'From' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '')
    {
        $this->mailObj->setFrom([$email => $name]);
    }

    /**
     *  \brief Set the message bo.
     *  
     *  \param $body - HTML ou text message body
     *  \param $html - set true if body is HTML ou false if plain text
     */
    public function setBody($body, $html = true)
    {
        $this->mailObj->setBody($body, $html ? 'text/html' : 'text/plain');
    }

    /**
     *	\brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text)
    {
        $this->mailObj->addPart($text, 'text/plain');
    }

    /**
     *  \brief Send the mail message
     *  \return The error message or a empty string if success.
     */
    public function send()
    {
        $error = false;

        $mailer = \Swift_Mailer::newInstance($this->transport);
        if (!$mailer->send($this->mailObj, $failures)) {
            $error = $failures;
        }

        return $error;
    }
}
