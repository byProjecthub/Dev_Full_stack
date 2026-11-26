> Historical frontend change report. The later Neon conversion changes database/backend files; see NEON_VALIDATION.md and NEON_SETUP.md for the current edition.

# Frontend Update — 26 November 2026

## Completed checks

- 154 PHP files passed syntax checks using PHP 8.4.28.
- 8 isolated account-flow tests passed: root and client login, invalid CSRF rejection, wrong-password rejection, administrator login and audit update, both registration paths and logout.
- 106 backend/configuration/dependency/schema files were verified unchanged against the original archive.
- The POST contracts of 9 business/account forms were retained. The administrator field binding was corrected to match its existing username-based query.
- Domain-root and subfolder URL generation passed 4 checks.
- 23 inline scripts passed JavaScript syntax checks, with the updated calculator script rechecked after its fixes.
- Eight page layouts were reviewed at phone and tablet widths. Homepage, About, contact, calculator, client sign-in, client registration, administrator sign-in and the selected concept were included.
- The calculator was checked in the browser: only the selected panel is visible; changing mobile screen counts does not change software inputs; the correct estimate and quote data are produced. The phone-width button overflow was repaired.
- Mobile navigation opens and closes, including Escape dismissal. The browser preview shows explicit feedback without submitting forms.

## Integration scope

Production database settings, email code, API handlers, JWT and PayFast verification code remain part of the project. Authentication checks used a separate temporary SQLite fixture and synthetic accounts; those fixtures and accounts are not included in this archive. Live MySQL, email delivery, calendar services and PayFast callbacks require verification on the existing PHP host with its actual service settings. The visual preview does not run those integrations.

## Main presentation files

- `assets/css/vueports-light.css`
- `includes/frontend.php`
- `includes/header.php`
- `includes/footer.php`
- `index.php`
- `about.php`

The remaining account and administration screens load the shared light stylesheet while retaining their server-side logic.

## Navigation update — 26 November 2026

The public header now uses a forest-green navigation bar and ivory card dropdowns, following the supplied reference while retaining the light page background. Solutions, For businesses, Pricing, Resources and Company expose the existing public pages. Client sign-in and Get a quote remain visible actions.

Signed-in visitors receive dashboard, projects, invoices and sign-out links in place of registration links. No account or payment logic was changed by this navigation update. The shared URL helper keeps navigation correct in both domain-root and subfolder installations.

Desktop disclosure menus support pointer hover, click, Arrow Down, Escape, outside dismissal and focus leaving the header. The mobile dialog provides scrollable accordion groups, focus containment, Escape dismissal, background inertness and focus return. Only one menu group stays open at a time.

Navigation destinations and section anchors passed checks across guest and signed-in fixtures. Phone, small-phone and tablet layouts were checked in the browser; desktop menus, mobile accordion links, keyboard dismissal and focus return passed. The POPIA page is included in the visual preview. Live integrations remain subject to the hosting checks described above.

New shared files: `includes/navigation.php`, `assets/css/vueports-navigation.css`, `assets/js/vueports-navigation.js`. The header and footer use this shared behavior, and service/FAQ anchors have been made stable.
