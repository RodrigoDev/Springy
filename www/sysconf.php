<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (C) 2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		1.0.0

	This script:
		Framework kernel configurations
	------------------------------------------------------------------------------------ --- -- - */

// Define o ambiente do sistema
$GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'] = 'development';

// Define se o sistema está em manutenção
$GLOBALS['SYSTEM']['MAINTENANCE'] = false;

// Caminhos das classes e arquivos de configuração
$GLOBALS['SYSTEM']['LIBRARY_PATH'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'library';
$GLOBALS['SYSTEM']['USER_CLASS_PATH'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'user_classes';
$GLOBALS['SYSTEM']['CONFIG_PATH'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'conf';

// Configurações globais
$GLOBALS['SYSTEM']['CHARSET'] = 'UTF-8';

?>