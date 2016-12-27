<?php
/** \file
 *  Springy.
 *
 *  \brief      Interface para representar identidades que terão uma sessão na aplicação.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    framework
 */

namespace Springy\Security;

/**
 * \brief Interface para representar identidades que terão uma sessão na aplicação.
 */
interface IdentityInterface
{
    /**
     *  \brief Carrega os dados da identidade de acordo com as credências passadas por parâmetro.
     *  \param [in] (array) $data = Credenciais da identidade.
     */
    public function loadByCredencials(array $data);

    /**
     *  \brief Carrega os dados da identidade com os dados que estão guardados na sessão.
     *  \param [in] (array) $data = Dados da sessão.
     */
    public function fillFromSession(array $data);

    /**
     *  \brief Retorna a chave identificadora da identidade.
     *  \return (variant).
     */
    public function getId();

    /**
     *  \brief Retorna o nome da coluna identificadora da identidade.
     *  \return (string).
     */
    public function getIdField();

    /**
     *  \brief Retorna o nome identificador da sessão da identidade.
     *  \return (string).
     */
    public function getSessionKey();

    /**
     *  \brief Retorna os dados que serão guardados na sessão da identidade.
     *  \return (array).
     */
    public function getSessionData();

    /**
     *  \brief Retorna o nome dos campos de credenciais da identidade (ex. Login e Senha).
     *  \return (array).
     */
    public function getCredentials();
}
