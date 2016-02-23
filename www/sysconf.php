<?php
/** \file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright	Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2016 Fernando Val\n
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	3.1.15
 *  \author		Fernando Val  - fernando.val@gmail.com
 *
 *  \brief Configurações do cerne do sistema
 */

/**
 *  \addtogroup config.
 *
 *  O script \c sysconf.php é o arquivo de configuração geral do sistema. Nele estão as entradas de definição do nome e versão do sistema,
 *  ambiente, caminhos dos diretórios do sistema, charset e timezone.
 *
 *  Este arquivo é obrigatório e não pode ser removido ou renomeado.
 *
 *  Definição das entradas da configuração geral do sistema:
 *
 *  \li 'SYSTEM_NAME' - Nome do sistema.
 *  \li 'SYSTEM_VERSION' - Versão do sistema.
 *  \li 'ACTIVE_ENVIRONMENT' - Determina o ambiente ativo. São comumente utilizados 'development' e 'production' como valores dessa chave.
 *    Se for deixada em branco, o framework irá buscar entradas de configuração para o host acessado. Por exemplo: 'www.seusite.com.br'
 *  \li 'ENVIRONMENT_VARIABLE' - Define o nome da variável de ambiente que será usada para definir o ambiente ativo.
 *  \li 'CONSIDER_PORT_NUMBER' - Informa à configuração por host que a porta deve ser considerada como parte do conteúdo.
 *  \li 'ENVIRONMENT_ALIAS' - Array contendo um conjunto \c chave => \c valor, onde \c chave representa um apelido para o ambiente \c valor.
 *    A \c chave pode ser uma expressão regular.
 *  \li 'CMS' - Liga ou desliga o sistema Mini CMS.
 *  \li 'ROOT_PATH' - Diretório root da aplicação.
 *  \li 'SYSTEM_PATH' - Diretório do sistema.
 *  \li 'LIBRARY_PATH' - Diretório da biblioteca do sistema.
 *  \li '3RDPARTY_PATH' - Diretório da biblioteca de classes de terceiros.
 *  \li 'CONTROLER_PATH' - Diretório das classes da aplicação.
 *  \li 'CLASS_PATH' - Diretório das classes da aplicação.
 *  \li 'CONFIG_PATH' - Diretório das configurações do sistema.
 *  \li 'CHARSET' - Charset do sistema.
 *  \li 'TIMEZONE' - Timestamp do sistema
 *
 *  As demais configurações da aplicação deverão estar no diretório e sub-diretórios definidos pela entrada \c 'CONFIG_PATH'.
 *
 *  O sistema de configuração irá buscar por arquivos contendo o sufixo \c ".conf.php", dentro dos sub-diretórios do ambiente em que o
 *  sistema estiver sendo executado. Além disso, o arquivo de mesmo nome e sufixo \c ".default.conf.php" também será carregado,
 *  caso exista, no diretório raiz das configurações, independete do ambiente, como sendo entradas de configuração padrão para todos os
 *  ambientes. As entradas padrão serão sobrescritas por entradas específicas do ambiente.
 *
 *  A configuração dos arquivos de configuração é feita pela definição da variável de nome \c $conf que é um array de definições de
 *  configuração.
 *
 *  Exemplo:
 *
 *  \code{.php}
 $conf = array(
 'entrada' => 'valor',
 'outra_configuracao' => 'valor da outra configuracao'
 ); \endcode
 *  
 *  É possível sobrescrever as configurações para determinados hosts de sua aplicação, utilizando a variável \c $over_conf, que é um array
 *  contendo no primeiro nível de índices o nome do host para o qual se deseja sobrescrever determinada(s) entrada(s) de configuração,
 *  que por sua vez, receberá um array contendo cada entrada de configuração a ser sobrescrita.
 *
 *  Exemplo:
 *
 *  \code{.php}
 $over_conf = array(
 'host.meudominio.com' => array(
 'entrada1' => 'novo valor',
 'entrada2' => 'outro novo valor'
 )
 ); \endcode
 *  
 *  Os arquivos pré-distribuídos com o framework são de uso interno das classes e não podem ser renomeados ou omitidos.
 *
 *  Seu sistema poderá possuir arquivos de configuração específicos, bastando obedecer o formato e a estrutura de nomes e diretórios.
 */
/**@{*/

/// Configurações gerais do sistema
$GLOBALS['SYSTEM'] = [
    'SYSTEM_NAME'    => 'Nome Do Seu Sistema',
    'SYSTEM_VERSION' => [1, 0, 0],

    'ACTIVE_ENVIRONMENT'   => '',
    'ENVIRONMENT_VARIABLE' => 'FWGV_ENVIRONMENT',
    'CONSIDER_PORT_NUMBER' => false,
    'ENVIRONMENT_ALIAS'    => [
        'localhost'                   => 'development',
        '127\.0\.0\.1'                => 'development',
        '192\.168\.[0-9]*\.[0-9]*'    => 'development',
        'homol(ogation)?'             => 'development',
        '(www\.)?seusite\.com(\.br)?' => 'production',
    ],

    'ROOT_PATH'      => realpath(dirname(__FILE__)),
    'SYSTEM_PATH'    => '',
    'LIBRARY_PATH'   => '',
    'CONTROLER_PATH' => '',
    'CLASS_PATH'     => '',
    'CONFIG_PATH'    => '',

    'CHARSET'  => 'UTF-8',
    'TIMEZONE' => 'America/Sao_Paulo',
];

/// Diretório do sistema
$GLOBALS['SYSTEM']['SYSTEM_PATH'] = realpath($GLOBALS['SYSTEM']['ROOT_PATH'].DIRECTORY_SEPARATOR.'system');
/// Diretório da biblioteca do sistema
$GLOBALS['SYSTEM']['LIBRARY_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'library');
/// Diretório de classes de terceiros que não são carregadas pelo autoload
$GLOBALS['SYSTEM']['3RDPARTY_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'vendor');
/// Diretório das controladoras
$GLOBALS['SYSTEM']['CONTROLER_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'controllers');
/// Diretório das classes da aplicação
$GLOBALS['SYSTEM']['CLASS_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'classes');
/// Diretório das configurações do sistema
$GLOBALS['SYSTEM']['CONFIG_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'conf');

/// Diretório da classe de controle de versionamento de banco de dados
$GLOBALS['SYSTEM']['MIGRATION_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'migration');

/**@}*/
