<?php

use App\Http\Controllers\CursosController;
use App\Http\Controllers\MatriculasController;
use App\Http\Controllers\TurmasController;
use App\Http\Controllers\UsuariosController;
use App\Support\Router;

$router = new Router;

$router->prefix('/api/v1');

$router->get('/cursos', [CursosController::class, 'index']);
$router->post('/cursos', [CursosController::class, 'store']);
$router->put('/cursos/{id}', [CursosController::class, 'update']);
$router->delete('/cursos/{id}', [CursosController::class, 'destroy']);

$router->post('/cursos/{cursoId}/turmas', [TurmasController::class, 'store']);
$router->put('/turmas/{id}', [TurmasController::class, 'update']);
$router->delete('/turmas/{id}', [TurmasController::class, 'destroy']);

$router->post('/usuarios', [UsuariosController::class, 'store']);
$router->delete('/usuarios/{id}', [UsuariosController::class, 'destroy']);

$router->post('/matriculas', [MatriculasController::class, 'store']);
$router->delete('/matriculas/{id}', [MatriculasController::class, 'destroy']);
$router->patch('/matriculas/{id}/status', [MatriculasController::class, 'updateStatus']);
$router->get('/usuarios/{usuarioId}/matriculas', [MatriculasController::class, 'index']);

$router->get('/usuarios', [UsuariosController::class, 'index']);
$router->get('/turmas', [TurmasController::class, 'index']);
$router->get('/cursos/{cursoId}/turmas', [TurmasController::class, 'indexByCurso']);

return $router;
