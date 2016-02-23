<?php
/**	\file
 *	Springy.
 *
 *  \copyright Copyright (c) 2007-2015 FVAL Consultoria e Inform�tica Ltda.\n
 *  \copyright Copyright (c) 2007-2015 Fernando Val\n
 *
 *	\brief     Autoload initialization script for PHPUnit
 *	\warning   Este arquivo � parte integrante do framework e n�o pode ser omitido
 *	\version   0.2
 *  \author    Fernando Val - fernando.val@gmail.com
 *	\ingroup   tests
 */

// Edit the two lines above and set the relative path to sysconf.php e helpers.php scripts
define('SYSCONF', 'www/sysconf.php');
define('HELPERS', 'www/helpers.php');

require SYSCONF;
require HELPERS;
if (!spl_autoload_register('springyAutoload')) {
    die('Internal System Error on Startup');
}

/*
 *  \brief Carrega autoload do Composer, caso exista
 */
if (file_exists($GLOBALS['SYSTEM']['3RDPARTY_PATH'].DIRECTORY_SEPARATOR.'autoload.php')) {
    require $GLOBALS['SYSTEM']['3RDPARTY_PATH'].DIRECTORY_SEPARATOR.'autoload.php';
}
