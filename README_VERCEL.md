# Deploy na Vercel com Supabase

Este guia explica como fazer o deploy do **Sistema de Controle SPC** na Vercel usando Supabase (PostgreSQL) como banco de dados.

## Pré-requisitos

1.  **Conta na Vercel**: [Cadastre-se aqui](https://vercel.com/signup).
2.  **Conta no Supabase**: [Cadastre-se aqui](https://supabase.com/).
3.  **Repositório GitHub**: Certifique-se de que este projeto foi enviado para um repositório no GitHub.

## Passo 1: Configurar Banco de Dados Supabase

1.  Crie um novo projeto no Supabase.
2.  Assim que o projeto estiver pronto, vá para o **SQL Editor** na barra lateral esquerda.
3.  Clique em "New Query".
4.  Copie o conteúdo de `database/schema_supabase.sql` deste projeto.
5.  Cole no SQL Editor e clique em **Run** para criar as tabelas.

## Passo 2: Configurar Vercel

1.  Faça login na Vercel e clique em **Add New...** -> **Project**.
2.  Importe seu repositório do GitHub.
3.  Na tela **Configure Project**, vá para **Environment Variables**.
4.  Adicione as seguintes variáveis (pegue essas informações em Supabase **Project Settings** -> **Database** -> **Connection parameters**):

    | Variável | Valor |
    | :--- | :--- |
    | `DB_CONNECTION` | `pgsql` |
    | `DB_HOST` | *Seu Host do Supabase* (ex: `db.xyz.supabase.co`) |
    | `DB_NAME` | `postgres` (padrão) |
    | `DB_USER` | `postgres` (padrão) |
    | `DB_PASSWORD` | *Sua Senha do Banco de Dados* |
    | `DB_PORT` | `5432` |

5.  Clique em **Deploy**.

## Desenvolvimento Local

Seu ambiente local provavelmente usa **MySQL**. O código interpreta a variável de ambiente `DB_CONNECTION` para alternar entre MySQL (local) e PostgreSQL (Vercel).

-   **Local**: Usa os padrões de `config/db.php` (MySQL).
-   **Vercel**: Usa variáveis de ambiente (PostgreSQL).

## Notas Importantes

-   **Upload de Arquivos**: Na Vercel, arquivos enviados são processados imediatamente no diretório temporário. Não há armazenamento persistente para uploads.
-   **Exportações**: Arquivos Excel gerados são transmitidos diretamente para download e não são salvos no servidor.
-   **Logs**: Logs da aplicação são enviados para os **Runtime Logs** da Vercel (stdout/stderr) em vez de arquivos locais.
