<?php
use Springy\Controller;

class Test_Controller extends Controller
{
    /**
     *  \brief Método principal (default).
     *
     *  Este método é executado se nenhum outro método for definido na URI para ser chamado, quando essa controladora é chamada.
     */
    public function _default()
    {
        $date = date('F j, Y');

        $tpl = $this->_template("test");

        $test = new Test();
        $test->load("");

        $tpl->assign('date', $date);
        $tpl->assign('data', $test->all());
        $tpl->display();
    }

    public function create()
    {
        $test = new Test();
        if ($_POST['id']) {
            $test->load(['id' => $_POST['id']]);
        }

        $test->parseData($_POST);
        $test->save();
    }

    public function delete()
    {
        $test = new Test();
        if ($_POST['id']) {
            $test->delete(['id' => $_POST['id']]);
        }
    }
}
