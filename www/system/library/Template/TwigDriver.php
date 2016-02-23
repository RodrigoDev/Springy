<?php
/** \file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright  Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *
 *  \brief      Classe driver de tratamento de templates utilizando Twig como mecanismo de renderização
 *  \see        http://twig.sensiolabs.org/
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.11.7 beta 4
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \ingroup    framework
 */
namespace FW\Template;

use FW\Configuration;
use FW\Kernel;
use FW\URI;

/**
 *  \brief Classe driver de tratamento de templates utilizando Twig como mecanismo.
 *
 *  \note Esta classe é um driver para a classe FW\Template e utiliza internamente a classe Twig.
 *        Não utilize a classe Twig diretamente.
 *        Não utilize esta classe diretamente em sua aplicação.
 *        Instancie a classe Template em sua aplicação.
 */
class TwigDriver implements TemplateDriverInterface
{
    const TPL_NAME_SUFIX = '.twig.html';

    private $tplObj = null;

    private $templatePath = null;
    private $templateName = null;

    private $templateCacheId = null;
    private $templateCompileId = null;

    private $templateVars = [];
    private $templateFuncs = [];

    /**
     *  \brief Inicializa a classe de template.
     */
    public function __construct($tpl = null)
    {
        // Inicializa a classe de template
        \Twig_Autoloader::register();

        $this->__twigInstance(Configuration::get('template', 'template_path'), Configuration::get('template', 'template_cached_path'));

        if ($tpl) {
            $this->setTemplate($tpl);
        }

        // Iniciliza as variáveis com URLs padrão de template
        if (Configuration::get('uri', 'common_urls')) {
            if (!Configuration::get('uri', 'register_method_set_common_urls')) {
                foreach (Configuration::get('uri', 'common_urls') as $var => $value) {
                    if (isset($value[4])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1], $value[2], $value[3], $value[4]));
                    } elseif (isset($value[3])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1], $value[2], $value[3]));
                    } elseif (isset($value[2])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1], $value[2]));
                    } elseif (isset($value[1])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1]));
                    } else {
                        $this->assign($var, URI::buildURL($value[0]));
                    }
                }
            } elseif (Configuration::get('uri', 'register_method_set_common_urls')) {
                $toCall = Configuration::get('uri', 'register_method_set_common_urls');
                if ($toCall['static']) {
                    if (!isset($toCall['method'])) {
                        throw new Exception('You need to determine which method will be executed.', 500);
                    }

                    //$toCall['class']::$toCall['method'];
                } else {
                    $obj = new $toCall['class']();
                    if (isset($toCall['method']) && $toCall['method']) {
                        $obj->$toCall['method'];
                    }
                }
            }
        }

        return true;
    }

    /**
     *  \brief Cria a instância da classe Twig.
     */
    private function __twigInstance($templatePath, $compilePath)
    {
        if (isset($this->tplObj)) {
            unset($this->tplObj);
        }
        $this->templatePath = $templatePath;
        $loader = new \Twig_Loader_Filesystem($templatePath);
        $this->tplObj = new \Twig_Environment($loader, [
            'autoescape'       => false,
            'strict_variables' => Configuration::get('template', 'strict_variables'),
            //'debug' => Configuration::get('system', 'debug'),
            'cache'       => $compilePath,
            'auto_reload' => true,
        ]);
    }

    /**
     *  \brief Destrói o objeto.
     */
    public function __destruct()
    {
        unset($this->tplObj);
    }

    /**
     *  \brief Define o local dos arquivos de template.
     */
    public function setTemplateDir($path)
    {
        $this->__twigInstance($path, Configuration::get('template', 'template_cached_path'));
    }

    /**
     *  \brief Define o local dos arquivos de template compilados.
     *  \note No caso do Twig, não tem função, pois o Twig não tem um diretório diferenciado
     *        para arquivos compilados. Apenas o diretório de chache é utilizado.
     *  \see setCacheDir.
     */
    public function setCompileDir($path)
    {
        // Sem função no Twig
    }

    /**
     *  \brief Define o local dos arquivos .conf usados nas tpls (sem função)
     *  \note Este método não tem uso na Twig, pois o mesmo não dá suporte
     *        a arquivos de configuração de template.
     */
    public function setConfigDir($path)
    {
        // Método criado apenas para atender definição da interface
    }

    /**
     *  \brief Define o local dos arquivos de template cacheados.
     */
    public function setCacheDir($path)
    {
        $this->tplObj->setCache($path);
    }

    /**
     *  \brief Verifica o template ideal de acordo com a página.
     */
    private function setAutoTemplatePaths()
    {
        // Se o nome do template não foi informado, define como relativo à controladora atual
        if ($this->templateName === null) {
            // Pega o caminho relativo da página atual
            $relative_path_page = URI::relativePathPage(true);
            $this->setTemplate($relative_path_page.(empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR).URI::getControllerClass());

            // $this->templateName = URI::getControllerClass();

            // // Monta o caminho do diretório do arquivo de template
            // $path = Configuration::get('template', 'template_path') . (empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR) . $relative_path_page;

            // // Verifica se existe o diretório e dentro dele um template com o nome da página e
            // // havendo, usa como caminho relativo adicionao. Se não houver, limpa o caminho relativo.
            // if (is_dir($path) && file_exists($path . DIRECTORY_SEPARATOR . $this->templateName . self::TPL_NAME_SUFIX)) {
                // $relative_path = (empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR) . $relative_path_page;
            // } else {
                // $relative_path = '';
            // }

            // // Ajusta os caminhos de template
            // $this->setTemplateDir( Configuration::get('template', 'template_path') . $relative_path);
            // $this->setCompileDir( Configuration::get('template', 'compiled_template_path') . $relative_path);
            // $this->setConfigDir( Configuration::get('template', 'template_config_path'));
        }

        // Se o arquivo de template não existir, exibe erro 404
        // if (!$this->templateExists($this->templateName)) {
            // Errors::displayError(404, $this->templateName . self::TPL_NAME_SUFIX);
        // }

        return true;
    }

    /**
     *  \brief Verifica se o template está cacheado.
     *
     *  @return bool
     */
    public function isCached()
    {
        return file_exists($this->tplObj->getCacheFilename($this->templateName.self::TPL_NAME_SUFIX));
    }

    /**
     *  \brief Define o cacheamento dos templates
     *  \node Sem função, pois o Twig não permite controle de tempo de vida do cache.
     */
    public function setCaching($value = 'current')
    {
        // Método criado apenas para atender definição da interface
    }

    /**
     *  \brief Define o tempo de vida dos arquivos de cache
     *  \note Sem função, pois no Twig não dá para controlar o tempo de cache dos
     *        template compilados.
     */
    public function setCacheLifetime($seconds)
    {
        // Método criado apenas para atender definição da interface
    }

    /**
     *  \brief Retorna a página montada.
     */
    public function fetch()
    {
        $this->setAutoTemplatePaths();

        // Alimenta as variáveis CONSTANTES
        $vars = [
            'HOST'               => URI::buildURL(),
            'CURRENT_PAGE_URI'   => URI::currentPageURI(),
            'SYSTEM_NAME'        => Kernel::systemName(),
            'SYSTEM_VERSION'     => Kernel::systemVersion(),
            'ACTIVE_ENVIRONMENT' => Kernel::environment(),
        ];

        // Alimenta as variáveis padrão da aplicação
        foreach (Kernel::getTemplateVar() as $name => $data) {
            $vars[$name] = $data;
        }

        // Alimenta as variáveis do template
        foreach ($this->templateVars as $name => $data) {
            $vars[$name] = $data['value'];
        }

        // Inicializa a função padrão assetFile
        $this->tplObj->addFunction(new \Twig_SimpleFunction('assetFile', [$this, 'assetFile']));

        // Inicializa as funções personalizadas padrão
        foreach (Kernel::getTemplateFunctions() as $func) {
            $this->tplObj->addFunction(new \Twig_SimpleFunction($func[1], $func[2]));
        }

        // Inicializa as funções personalizadas do template
        foreach ($this->templateFuncs as $func) {
            $this->tplObj->addFunction(new \Twig_SimpleFunction($func[1], $func[2]));
        }

        return $this->tplObj->render($this->templateName.self::TPL_NAME_SUFIX, $vars);
    }

    /**
     *  \brief Define o arquivos de template
     *  \param $tpl - String com o nome do template, sem extenção do arquivo.
     */
    public function setTemplate($tpl)
    {
        $this->templateName = ((is_array($tpl)) ? implode(DIRECTORY_SEPARATOR, $tpl) : $tpl);

        $compile = '';
        if (!is_null($tpl)) {
            $compile = is_array($tpl) ? implode('/', $tpl) : $tpl;
            $compile = substr($compile, 0, strrpos('/', $compile));
        }

        $this->setCompileDir(Configuration::get('template', 'compiled_template_path').$compile);
    }

    /**
     *  \brief Define o id do cache
     *  \note A pesar de implementado, esse método não tem função,
     *        pois o Twig controla internamente o id de controle do cache.
     */
    public function setCacheId($cid)
    {
        $this->templateCacheId = $cid;
    }

    /**
     *  \brief Define o id da compilação
     *  \note A pesar de implementado, esse método não tem função,
     *        pois o Twig não dá controle sobre id de compilação dos
     *        templates.
     */
    public function setCompileId($cid)
    {
        $this->templateCompileId = $cid;
    }

    /**
     *  \brief Define uma variável do template.
     */
    public function assign($var, $value = null, $nocache = false)
    {
        if (is_array($var)) {
            foreach ($var as $name => $value) {
                $this->assign($name, $value);
            }
        } else {
            $this->templateVars[$var] = ['value' => $value, 'nocache' => $nocache];
        }
    }

    /**
     *  \brief Método statico que define um pluguin para todas as instancias da Template.
     */
    public function registerPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null)
    {
        $this->templateFuncs[] = [$type, $name, $callback, $cacheable, $cache_attrs];
    }

    /**
     *  \brief Limpa uma variável do template.
     */
    public function clearAssign($var)
    {
        unset($this->tplVars[$var]);
    }

    /**
     *  \brief clears the entire template cache.
     *
     *  As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
     */
    public function clearAllCache($expire_time)
    {
        $this->tplObj->clearCacheFiles();
    }

    /**
     *  \brief Limpa o cache para o template corrente.
     */
    public function clearCache($expireTime = null)
    {
        $this->tplObj->clearCacheFiles();
    }

    /**
     *  \brief Limpa a versão compilada do template atual.
     */
    public function clearCompiled($expTime)
    {
        $this->tplObj->clearCacheFiles();
    }

    /**
     *  \brief Limpa variável de config definida
     *  \note Esse método não tem funçao no Twig.
     */
    public function clearConfig($var)
    {
        // Método criado apenas para atender definição da interface
    }

    /**
     *  \brief Verifica se um arquivo de template existe.
     */
    public function templateExists($tplName)
    {
        return file_exists($this->templatePath.DIRECTORY_SEPARATOR.$tplName.self::TPL_NAME_SUFIX);
    }

    /**
     *  \brief Mascara nome de arquivo estático para evitar cache do navegador.
     *
     *  Este método é inserido como função de template para utilização na criação da URI
     *  de arquivos estáticos de CSS e JavaScript com objetivo de evitar que o cache
     *  do navegador utilize versões desatualizadas deles.
     */
    public function assetFile($type, $file)
    {
        if ($type == 'js') {
            $filePath = Configuration::get('system', 'js_path').DIRECTORY_SEPARATOR.$file.'.js';
            $fileURI = Configuration::get('uri', 'js_dir');
        } elseif ($type == 'css') {
            $filePath = Configuration::get('system', 'css_path').DIRECTORY_SEPARATOR.$file.'.css';
            $fileURI = Configuration::get('uri', 'css_dir');
        } else {
            return '#';
        }

        if (file_exists($filePath)) {
            $fileURI .= '/'.$file.'__'.filemtime($filePath).'.'.$type;
        } else {
            $fileURI .= '/'.$file.'.'.$type;
        }

        return URI::buildURL(explode('/', $fileURI), [], isset($_SERVER['HTTPS']), 'static', false);
    }
}
