<?php
namespace app\controller;

use lfly\Controller;

class Index extends Controller
{
    public function index()
    {
        $this->showMessage(0, 'Hello, LFLY Framework!');
    }
}
