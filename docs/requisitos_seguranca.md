# Requisitos de Segurança — Salve Alimento

Plataforma de doação de alimentos desenvolvida para a disciplina de Segurança para Aplicações Web — PUCPR.

---

## Tabela de Requisitos

| ID | Requisito | Categoria OWASP | Mecanismo implementado | CWE | CVE de referência |
|----|-----------|-----------------|----------------------|-----|-------------------|
| RS01 | Autenticação com MFA obrigatório | A07 – Falhas de Identificação e Autenticação | Amazon Cognito com TOTP (RFC 6238) via `SOFTWARE_TOKEN_MFA`; login bloqueado sem segundo fator | CWE-308 | CVE-2022-35737 |
| RS02 | Controle de acesso por perfil (RBAC) | A01 – Quebra de Controle de Acesso | `AuthMiddleware::verificarSessao()` extrai `custom:perfil` do IdToken Cognito; cada rota verifica o perfil antes de executar | CWE-284 | CVE-2021-43798 |
| RS03 | Prevenção de IDOR | A01 – Quebra de Controle de Acesso | Todas as queries de mutação incluem `AND id_doador = ?`; `Doacao::pertenceAoDoador()` valida ownership antes de editar ou excluir | CWE-639 | CVE-2023-20198 |
| RS04 | Prevenção de injeção SQL | A03 – Injeção | 100% das queries usam PDO com `prepare()` + `execute([])`; `ATTR_EMULATE_PREPARES = false` força prepared statements reais no MySQL | CWE-89 | CVE-2022-21663 |
| RS05 | Proteção contra CSRF | A01 – Quebra de Controle de Acesso | Token de 256 bits gerado por `random_bytes(32)`, armazenado em sessão e validado via `hash_equals()` em toda requisição de mutação; rotacionado após cada uso | CWE-352 | CVE-2019-11358 |
| RS06 | Rate limiting no login | A07 – Falhas de Identificação e Autenticação | Máximo de 5 tentativas; bloqueio de 15 minutos armazenado no banco; alerta por e-mail ao titular da conta | CWE-307 | CVE-2020-35677 |
| RS07 | Timeout de sessão por inatividade | A07 – Falhas de Identificação e Autenticação | `AuthMiddleware::sessaoExpirada()` verifica `ultima_atividade` na sessão PHP; redireciona para login após 30 minutos sem interação | CWE-613 | CVE-2021-27927 |
| RS08 | Criptografia de dados sensíveis em repouso | A02 – Falhas Criptográficas | CPF e endereço cifrados no browser com AES-256-GCM (WebCrypto API); chave AES encapsulada com RSA-OAEP 4096 bits; servidor armazena apenas blobs cifrados | CWE-311 | CVE-2021-3449 |
| RS09 | Transporte seguro obrigatório (HTTPS) | A02 – Falhas Criptográficas | Nginx com TLS 1.2/1.3; HTTP redireciona para HTTPS via 301; `Strict-Transport-Security: max-age=31536000` ativado em toda resposta HTTPS | CWE-319 | CVE-2021-3618 |
| RS10 | Headers de segurança HTTP | A05 – Configuração de Segurança Incorreta | `Content-Security-Policy: default-src 'self'` bloqueia XSS e recursos externos não autorizados; `X-Frame-Options: DENY` previne clickjacking; `X-Content-Type-Options: nosniff` previne MIME sniffing | CWE-693 | CVE-2018-1000620 |
| RS11 | Cookies de sessão protegidos | A02 – Falhas Criptográficas | Tokens JWT salvos com `HttpOnly`, `Secure` e `SameSite=Strict`; inacessíveis por JavaScript e não enviados em requisições cross-site | CWE-1004 | CVE-2020-8026 |
| RS12 | Auditoria de ações sensíveis | A09 – Falhas de Registro e Monitoramento | `AuditService` registra login, logout, registro, alteração de senha e ações administrativas com timestamp, IP e hash SHA-256 encadeado para detecção de adulteração | CWE-778 | CVE-2021-44228 |

---

## Mapeamento por categoria OWASP Top 10

| Categoria OWASP | Requisitos |
|-----------------|------------|
| A01 – Quebra de Controle de Acesso | RS02, RS03, RS05 |
| A02 – Falhas Criptográficas | RS08, RS09, RS11 |
| A03 – Injeção | RS04 |
| A05 – Configuração de Segurança Incorreta | RS10 |
| A07 – Falhas de Identificação e Autenticação | RS01, RS06, RS07 |
| A09 – Falhas de Registro e Monitoramento | RS12 |

---

## Cobertura por testes automatizados

| Requisito | Teste | Arquivo |
|-----------|-------|---------|
| RS04 – SQL Injection | `SqlInjectionTest` | `tests/Security/SqlInjectionTest.php` |
| RS05 – CSRF | `CsrfTest` | `tests/Security/CsrfTest.php` |
| RS03 – IDOR | `IdorTest` | `tests/Security/IdorTest.php` |
| RS07 – Session Timeout | `SessionTimeoutTest` | `tests/Security/SessionTimeoutTest.php` |
| RS08 – Criptografia | `CryptoTest` | `tests/Security/CryptoTest.php` |
