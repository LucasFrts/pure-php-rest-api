<?php

namespace App\Repositories;

use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use DateTime;

class TurmaRepository extends BaseRepository implements TurmaRepositoryInterface
{
    public function get(array $filters = []): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM turmas');
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(int $id): Turma
    {
        $stmt = $this->pdo->prepare('SELECT * FROM turmas WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new NotFound("Turma {$id} não encontrada");
        }

        return $this->hydrate($row);
    }

    public function store(mixed $data): Turma
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO turmas (curso_id, titulo, descricao, quantidade_vagas, status, data_inicio, data_fim)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['curso_id'],
            $data['titulo'],
            $data['descricao'],
            $data['quantidade_vagas'],
            $data['status'],
            $data['data_inicio'],
            $data['data_fim'],
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, mixed $data): Turma
    {
        $this->find($id);

        $stmt = $this->pdo->prepare(
            'UPDATE turmas SET curso_id = ?, titulo = ?, descricao = ?, quantidade_vagas = ?,
             status = ?, data_inicio = ?, data_fim = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['curso_id'],
            $data['titulo'],
            $data['descricao'],
            $data['quantidade_vagas'],
            $data['status'],
            $data['data_inicio'],
            $data['data_fim'],
            $id,
        ]);

        return $this->find($id);
    }

    public function destroy(int $id): void
    {
        $this->find($id);

        $stmt = $this->pdo->prepare('DELETE FROM turmas WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findByCursoId(int $cursoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM turmas WHERE curso_id = ?');
        $stmt->execute([$cursoId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): Turma
    {
        $turma = new Turma(
            $row['titulo'],
            $row['descricao'],
            (int) $row['quantidade_vagas'],
            StatusTurma::fromString($row['status']),
            new DateTime($row['data_inicio']),
            new DateTime($row['data_fim']),
            (int) $row['curso_id']
        );
        $turma->setId((int) $row['id']);

        return $turma;
    }
}
