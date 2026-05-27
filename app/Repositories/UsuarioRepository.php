<?php

namespace App\Repositories;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\ValueObjects\Email;

class UsuarioRepository extends BaseRepository implements UsuarioRepositoryInterface
{
    public function get(array $filters = []): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios');
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(int $id): Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new NotFound("Usuário {$id} não encontrado");
        }

        return $this->hydrate($row);
    }

    public function store(mixed $data): Usuario
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, email) VALUES (?, ?)');
        $stmt->execute([$data['nome'], $data['email']]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, mixed $data): Usuario
    {
        $this->find($id);

        $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?');
        $stmt->execute([$data['nome'], $data['email'], $id]);

        return $this->find($id);
    }

    public function destroy(int $id): void
    {
        $this->find($id);

        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Usuario
    {
        $usuario = new Usuario($row['nome'], new Email($row['email']));
        $usuario->setId((int) $row['id']);

        return $usuario;
    }
}
