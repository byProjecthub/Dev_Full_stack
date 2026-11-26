# Neon conversion validation — 26 November 2026

## Verified locally

- All 163 PHP files passed syntax checks with PHP 8.4.28.
- The PostgreSQL schema installed successfully on PostgreSQL 17.12. It contains 32 application tables plus its migration ledger. The version-aware installer correctly skipped a second installation.
- 288 complete static SQL statements were prepared and planned with PostgreSQL EXPLAIN without errors. Dynamic query branches were also exercised through the application integration checks.
- 84 integration checks passed. Coverage includes public pages and protected administration screens, both client registration/login paths, duplicate email handling, administrator creation/login, invalid credentials/CSRF, database-backed sessions, logout and client record isolation.
- Authenticated sessions survived a web-server restart, and two independent PHP processes updated the same session without losing an update.
- Contact, consultation, calculator JSON/estimate and booking records persisted. A duplicate booking slot was rejected.
- Project, invoice and payment-record API creation returned PostgreSQL identity IDs. Generated invoice totals and line totals were verified.
- Settings upserts, email queue IDs, audit JSON/IP storage, database rate limiting, analytics exports, subscription renewal, PostgreSQL backups and expiry cleanup passed.
- The empty PayFast callback route resolves, and the Zapier handler enforces authentication. An empty email-worker batch starts without a missing include.
- Private SQL, configuration and installer files were rejected over the local HTTP router.
- All 86 existing frontend CSS/JavaScript/image assets were preserved byte-for-byte, including the light theme and card navigation.
- The delivered smoke test rolled back its synthetic records.

## Verification limits

No live Neon account, customer database, Vercel project, email provider or payment gateway was contacted by the application tests. The tests used a private local PostgreSQL fixture and synthetic accounts; none of those fixture accounts, passwords, session records or database dumps are included in this project ZIP.

The Docker/Apache image was not built here because Docker is not installed in the execution environment. Build and startup logs still need checking during the real Vercel deployment. Live MySQL records have not been transferred. Follow NEON_SETUP.md for connection, installation, administrator creation, external storage and provider checks.
# GitHub repository reconciliation

The repository's newer login and portfolio fixes were merged with this conversion.
The merged source passed 93 local integration checks, 292 PostgreSQL SQL checks and
PHP syntax checks on all 165 PHP files. Repository environment and portfolio regression
tests passed. The locked PHPMailer 6.12.0 version is preserved in Composer; container
installation of that version is checked by GitHub Actions, since Docker was unavailable
locally. The old MySQL publishing path is replaced by PostgreSQL testing and publication
of the application image. A tracked `.env` and runtime logs are removed from the new
tree; existing commit history is retained.

