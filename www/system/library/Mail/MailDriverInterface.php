<?php
/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright  Copyright (c) 2007-2016 FVAL Consultoria e Inform�tica Ltda.\n
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *
 *	\brief      Interface for mail drivers
 *	\warning    This file is part of the framework and can not be omitted
 *	\version    1.0.0
 *  \author     Fernando Val  - fernando.val@gmail.com
 *	\ingroup    framework
 */
namespace FW\Mail;

/**
 *  \brief Interface for mail drivers.
 *
 *  \note This class is a interface for construction of mail drivers.
 */
interface MailDriverInterface
{
    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value);

    /**
     *  \brief Add an address to 'To' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '');

    /**
     *  \brief Add an address to 'BCC' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addBCC($email, $name = '');

    /**
     *  \brief Add an address to 'CC' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addCC($email, $name = '');

    /**
     *  \brief Add a file to be attached to the e-mail.
     *  
     *  \param $path - full pathname to the attachment
     *  \param $name - override the attachment name (optional)
     *  \param $type - MIME type/file extension type (optional)
     *  \param $encoding - file enconding (optional)
     */
    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64');

    /**
     *  \brief Set the 'From' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '');

    /**
     *  \brief Set the mail subject.
     *  
     *  \param $subject - the subject text
     */
    public function setSubject($subject);

    /**
     *  \brief Set the message bo.
     *  
     *  \param $body - HTML ou text message body
     *  \param $html - set true if body is HTML ou false if plain text
     */
    public function setBody($body, $html = true);

    /**
     *	\brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text);

    /**
     *  \brief Send the mail message
     *  \return The error message or a empty string if success.
     */
    public function send();
}
