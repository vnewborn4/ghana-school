# Shared Agent Rules

This repository is a shared workspace. Before changing any file, read this file and `.agent-tasks/README.md`.

## Required workflow

1. Run `git status --short` and inspect the current diff for every file you may touch.
2. Check `.agent-tasks/` for active claims.
3. Claim your work in one flat task file before editing. Name it `.agent-tasks/<short-task-name>.md`.
4. List the exact files you own. Do not edit a file claimed by another active task.
5. If a needed file is already dirty and its owner is unclear, stop and record the conflict in your task file. Do not overwrite or clean it.
6. Keep unrelated user and agent changes intact. Never reset, revert, delete, stage, or commit another agent's work.
7. On completion, record the files changed, checks run, results, unresolved risks, and next step in the task file.

## Ownership rules

- One active owner per file.
- Read-only review does not claim a file, but it must not mutate data, run migrations, or deploy.
- A task may depend on another task; record the dependency instead of nesting task files.
- Claims do not expire automatically. A new agent must confirm that an old claim is released before taking it over.
- Coordinate before expanding scope or touching shared entry points such as `index.php`, `includes/header.php`, `includes/footer.php`, database schemas, payment code, or deployment configuration.

## External systems

- Never print or store passwords, API keys, FTP credentials, bank details, or unrestricted donor data.
- Do not run database migrations, change live payment settings, publish, deploy, or commit unless the user explicitly authorizes that action.
- For FTP work, verify the live source and remote target before editing or uploading.

