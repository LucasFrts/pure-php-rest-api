<?php

namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;

class TurmasController extends BaseController
{
    public function __construct()
    {}

    public function index() : ResponseInterface
    {
        return $this->response()->success(['message' => 'só o file']);
    }
}
