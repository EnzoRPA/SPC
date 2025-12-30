# Deploy to Vercel with Supabase

This guide explains how to deploy the **SPC Control System** to Vercel using Supabase (PostgreSQL) as the database.

## Prerequisites

1.  **Vercel Account**: [Sign up here](https://vercel.com/signup).
2.  **Supabase Account**: [Sign up here](https://supabase.com/).
3.  **GitHub Repository**: Ensure this project is pushed to a GitHub repository.

## Step 1: Set up Supabase Database

1.  Create a new project in Supabase.
2.  Once the project is ready, go to the **SQL Editor** in the left sidebar.
3.  Click "New Query".
4.  Copy the content of `database/schema_supabase.sql` from this project.
5.  Paste it into the SQL Editor and click **Run** to create the tables.

## Step 2: Configure Vercel

1.  Log in to Vercel and click **Add New...** -> **Project**.
2.  Import your GitHub repository.
3.  In the **Configure Project** screen, go to **Environment Variables**.
4.  Add the following variables (get these from Supabase **Project Settings** -> **Database** -> **Connection parameters**):

    | Variable | Value |
    | :--- | :--- |
    | `DB_CONNECTION` | `pgsql` |
    | `DB_HOST` | *Your Supabase Host* (e.g., `db.xyz.supabase.co`) |
    | `DB_NAME` | `postgres` (default) |
    | `DB_USER` | `postgres` (default) |
    | `DB_PASSWORD` | *Your Database Password* |
    | `DB_PORT` | `5432` |

5.  Click **Deploy**.

## Local Development

Your local environment likely uses **MySQL**. The code interprets the `DB_CONNECTION` environment variable to switch between MySQL (local) and PostgreSQL (Vercel).

-   **Local**: Uses `config/db.php` defaults (MySQL).
-   **Vercel**: Uses environment variables (PostgreSQL).

## Important Notes

-   **File Uploads**: On Vercel, uploaded files are processed immediately in the temporary directory. There is no persistent storage for uploads.
-   **Exports**: Generated Excel files are streamed directly to the download and not saved on the server.
-   **Logs**: Application logs are sent to Vercel's **Runtime Logs** (stdout/stderr) instead of local files.
