<?php
/** \file
 *  Springy
 *
 *  \brief      Test case for Classe com m�todos para diversos tipos de tratamento e valida��o de dados string.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo � parte integrante do framework e n�o pode ser omitido
 *  \version    0.2.1
 *  \ingroup    tests
 */
use Springy\Utils\Strings;

class StringsTest extends PHPUnit_Framework_TestCase
{
    public function testEmailGetsValidateSuccessfully()
    {
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.com.br'));
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.com.br', false));

        $this->assertFalse(Strings::validateEmailAddress('fernando@fval', false));
        $this->assertFalse(Strings::validateEmailAddress('fernandofval.com.br', false));
        $this->assertFalse(Strings::validateEmailAddress('fernando@fval.nonexiuuste'));
        $this->assertFalse(Strings::validateEmailAddress('fernando@fval.nonexiuuste', false));
    }

    public function testThatDateGetsValidatedSuccessfully()
    {
        $this->assertTrue(Strings::data('25/01/1987'));
        $this->assertFalse(Strings::data('31d/f02/gg2014'));
        $this->assertFalse(Strings::data('31/02/2014'));
    }
}
