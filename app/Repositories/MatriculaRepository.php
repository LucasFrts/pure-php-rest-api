<?php

namespace App\Repositories;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Entities\Matricula;

class MatriculaRepository extends BaseRepository implements MatriculaRepositoryInterface
{
    public function store(Matricula $matricula): Matricula
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO matriculas (usuario_id, turma_id, curso_id) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $matricula->getUsuarioId(),
            $matricula->getTurmaId(),
            $matricula->getCursoId(),
        ]);

        $matricula->setId((int) $this->pdo->lastInsertId());

        return $matricula;
    }

    public function findByUsuarioAndCurso(int $usuarioId, int $cursoId): ?Matricula
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM matriculas WHERE usuario_id = ? AND curso_id = ?'
        );
        $stmt->execute([$usuarioId, $cursoId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function getByUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM matriculas WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function destroy(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM matriculas WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Matricula
    {
        $m = new Matricula(
            (int) $row['usuario_id'],
            (int) $row['turma_id'],
            (int) $row['curso_id']
        );
        $m->setId((int) $row['id']);

        return $m;
    }
}
