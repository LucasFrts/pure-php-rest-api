<?php

namespace App\Repositories;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Entities\Matricula;
use App\Enums\StatusMatricula;
use App\Exceptions\Http\NotFound;

class MatriculaRepository extends BaseRepository implements MatriculaRepositoryInterface
{
    public function store(Matricula $matricula): Matricula
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO matriculas (usuario_id, turma_id, curso_id, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $matricula->getUsuarioId(),
            $matricula->getTurmaId(),
            $matricula->getCursoId(),
            $matricula->getStatus()->toString(),
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

    public function find(int $id): Matricula
    {
        $stmt = $this->pdo->prepare('SELECT * FROM matriculas WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new NotFound("Matrícula {$id} não encontrada");
        }

        return $this->hydrate($row);
    }

    public function updateStatus(int $id, StatusMatricula $status): Matricula
    {
        $stmt = $this->pdo->prepare('UPDATE matriculas SET status = ? WHERE id = ?');
        $stmt->execute([$status->toString(), $id]);

        return $this->find($id);
    }

    public function inativarByTurma(int $turmaId): void
    {
        $stmt = $this->pdo->prepare('UPDATE matriculas SET status = ? WHERE turma_id = ?');
        $stmt->execute([StatusMatricula::Inativo->toString(), $turmaId]);
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
            (int) $row['curso_id'],
            StatusMatricula::fromString($row['status'])
        );
        $m->setId((int) $row['id']);

        return $m;
    }
}
