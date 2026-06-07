-- =============================================================================
-- Salve Alimento — Schema do Banco de Dados
-- MySQL 8.0 · utf8mb4 · InnoDB
-- =============================================================================

CREATE DATABASE IF NOT EXISTS salve_alimento
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE salve_alimento;

-- -----------------------------------------------------------------------------
-- usuarios
-- cognito_sub: identificador único do Cognito (sub claim do JWT)
-- cpf_enc, endereco_enc: blobs cifrados com AES-256 (chave protegida por RSA)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    cognito_sub     VARCHAR(36)     NOT NULL UNIQUE,
    nome            VARCHAR(150)    NOT NULL,
    email           VARCHAR(254)    NOT NULL UNIQUE,
    perfil          ENUM('doador','receptor_ong','receptor_familia','admin') NOT NULL,
    status          ENUM('pendente','ativo','bloqueado') NOT NULL DEFAULT 'pendente',
    cpf_enc         BLOB            NULL,
    endereco_enc    BLOB            NULL,
    chave_enc       BLOB            NULL,
    dt_criacao      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email  (email),
    INDEX idx_perfil (perfil),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- doacoes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doacoes (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_doador           INT UNSIGNED    NOT NULL,
    titulo              VARCHAR(200)    NOT NULL,
    descricao           TEXT            NULL,
    status              ENUM('disponivel','reservado','concluido','expirado') NOT NULL DEFAULT 'disponivel',
    dt_publicacao       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_limite_retirada  DATETIME        NOT NULL,
    endereco_retirada   VARCHAR(300)    NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_status    (status),
    INDEX idx_doador    (id_doador),
    INDEX idx_dt_limite (dt_limite_retirada),
    CONSTRAINT fk_doacao_doador FOREIGN KEY (id_doador)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- itens_doacao
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS itens_doacao (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_doacao       INT UNSIGNED    NOT NULL,
    nome_item       VARCHAR(150)    NOT NULL,
    quantidade      DECIMAL(10,2)   NOT NULL,
    unidade         VARCHAR(20)     NOT NULL,
    data_validade   DATE            NULL,
    PRIMARY KEY (id),
    INDEX idx_doacao (id_doacao),
    CONSTRAINT fk_item_doacao FOREIGN KEY (id_doacao)
        REFERENCES doacoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- solicitacoes
-- Regra: apenas uma solicitação ativa (pendente/aprovada) por doação
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS solicitacoes (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_doacao       INT UNSIGNED    NOT NULL,
    id_receptor     INT UNSIGNED    NOT NULL,
    status          ENUM('pendente','aprovada','recusada','concluida') NOT NULL DEFAULT 'pendente',
    dt_solicitacao  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    obs             TEXT            NULL,
    PRIMARY KEY (id),
    INDEX idx_doacao   (id_doacao),
    INDEX idx_receptor (id_receptor),
    INDEX idx_status   (status),
    CONSTRAINT fk_solic_doacao   FOREIGN KEY (id_doacao)
        REFERENCES doacoes  (id) ON DELETE CASCADE,
    CONSTRAINT fk_solic_receptor FOREIGN KEY (id_receptor)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- logs_auditoria — imutável via permissões (sem UPDATE/DELETE para app_user)
-- Cadeia de integridade: cada registro referencia o hash do anterior (SHA-256)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs_auditoria (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario       INT UNSIGNED    NULL,
    acao             VARCHAR(100)    NOT NULL,
    tabela_afetada   VARCHAR(64)     NOT NULL,
    id_registro      INT UNSIGNED    NULL,
    dados_anteriores JSON            NULL,
    dt_evento        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_origem        VARCHAR(45)     NOT NULL,
    hash_anterior    CHAR(64)        NULL,
    hash_atual       CHAR(64)        NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_usuario (id_usuario),
    INDEX idx_dt      (dt_evento),
    INDEX idx_tabela  (tabela_afetada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- tokens_senha — recuperação de senha (single-use, expira em 1h)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tokens_senha (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_usuario  INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL UNIQUE,
    expiracao   DATETIME        NOT NULL,
    usado       TINYINT(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_token   (token_hash),
    INDEX idx_usuario (id_usuario),
    CONSTRAINT fk_token_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- chaves_sessao — chave AES por usuário para criptografia híbrida
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chaves_sessao (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_usuario          INT UNSIGNED    NOT NULL UNIQUE,
    chave_simetrica_enc BLOB            NOT NULL,
    dt_criacao          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_chave_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Permissões — app_user NÃO pode alterar ou remover logs de auditoria
-- =============================================================================
GRANT SELECT, INSERT, UPDATE, DELETE ON salve_alimento.usuarios       TO 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON salve_alimento.doacoes        TO 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON salve_alimento.itens_doacao   TO 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON salve_alimento.solicitacoes   TO 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON salve_alimento.tokens_senha   TO 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON salve_alimento.chaves_sessao  TO 'app_user'@'%';

-- logs_auditoria: apenas INSERT e SELECT (imutável pela aplicação)
GRANT SELECT, INSERT                  ON salve_alimento.logs_auditoria TO 'app_user'@'%';

FLUSH PRIVILEGES;
