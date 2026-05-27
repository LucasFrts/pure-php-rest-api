<?php

namespace App\Repositories;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Entities\Curso;
use App\Enums\Temas;
use App\Exceptions\Http\NotFound;

class CursoRepository extends BaseRepository implements CursoRepositoryInterface
{
    public function get(array $filters = []): array
    {
        $sql = 'SELECT * FROM cursos';
        $params = [];
        $conditions = [];

        if (!empty($filters['titulo'])) {
            $conditions[] = 'titulo LIKE ?';
            $params[] = '%' . $filters['titulo'] . '%';
        }

        if (!empty($filters['tema'])) {
            $conditions[] = 'tema = ?';
            $params[] = $filters['tema'];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(int $id): Curso
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cursos WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new NotFound("Curso {$id} não encontrado");
        }

        return $this->hydrate($row);
    }

    public function store(Curso $curso): Curso
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cursos (titulo, descricao, tema, url_imagem) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $curso->getTitulo(),
            $curso->getDescricao(),
            $curso->getTema()->toString(),
            $curso->getUrlImagem(),
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, Curso $curso): Curso
    {
        $this->find($id);

        $stmt = $this->pdo->prepare(
            'UPDATE cursos SET titulo = ?, descricao = ?, tema = ?, url_imagem = ? WHERE id = ?'
        );
        $stmt->execute([
            $curso->getTitulo(),
            $curso->getDescricao(),
            $curso->getTema()->toString(),
            $curso->getUrlImagem(),
            $id,
        ]);

        return $this->find($id);
    }

    public function destroy(int $id): void
    {
        $this->find($id);

        $stmt = $this->pdo->prepare('DELETE FROM cursos WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getWithAvailableTurmas(array $filters = []): array
    {
        $today = (new \DateTime())->format('Y-m-d');
        $sql = 'SELECT DISTINCT cursos.id, cursos.titulo, cursos.descricao, cursos.tema, cursos.url_imagem
                FROM cursos
                JOIN turmas ON turmas.curso_id = cursos.id
                WHERE turmas.status = ?
                AND ? BETWEEN turmas.data_inicio AND turmas.data_fim';
        $params = ['disponivel', $today];

        if (!empty($filters['titulo'])) {
            $sql .= ' AND cursos.titulo LIKE ?';
            $params[] = '%' . $filters['titulo'] . '%';
        }

        if (!empty($filters['tema'])) {
            $sql .= ' AND cursos.tema = ?';
            $params[] = $filters['tema'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): Curso
    {
        $curso = new Curso(
            $row['titulo'],
            $row['descricao'],
            Temas::fromString($row['tema']),
            $row['url_imagem']
        );
        $curso->setId((int) $row['id']);

        return $curso;
    }
}
