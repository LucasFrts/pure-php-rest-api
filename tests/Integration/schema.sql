CREATE TABLE IF NOT EXISTS usuarios (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    nome  TEXT    NOT NULL,
    email TEXT    NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS cursos (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo     TEXT NOT NULL,
    descricao  TEXT NOT NULL,
    tema       TEXT NOT NULL,
    url_imagem TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS turmas (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    curso_id         INTEGER NOT NULL,
    titulo           TEXT    NOT NULL,
    descricao        TEXT    NOT NULL,
    quantidade_vagas INTEGER NOT NULL,
    status           TEXT    NOT NULL,
    data_inicio      TEXT    NOT NULL,
    data_fim         TEXT    NOT NULL,
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

CREATE TABLE IF NOT EXISTS matriculas (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    turma_id   INTEGER NOT NULL,
    status     TEXT    NOT NULL DEFAULT 'ativo',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (turma_id)   REFERENCES turmas(id)
);
