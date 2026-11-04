# API reliability fixes - 3 November 2026

The fixes are implemented in the local frontend and admin repositories. They have not been committed, pushed, or deployed by this work. The original investigation is preserved in [API-FLOW-AUDIT-2026-11-03.md](API-FLOW-AUDIT-2026-11-03.md).

## What caused the inconsistent results

Several failures looked identical to customers because the frontend converted failed API requests into empty data. An HTML hosting challenge, timeout, or server error could consequently appear as an empty category or a product 404. Separate search defects duplicated products through category joins and lost multi-word search terms. Category lists also stopped after the first 200 API records even though the local catalogue contains 217.

The audit initially received HTTP 403 HTML challenge responses from the live hosting layer. Later read-only checks of the live categories and Yellow Sampangi detail endpoints returned HTTP 200 JSON. This confirms intermittent behavior from this test environment, not a permanent hosting fix or the cause of every customer failure. Hosting settings were not changed.

## Changes implemented

### Frontend repository

- `src/lib/apiClient.js` centralizes JSON validation, HTTP error handling, a 10-second timeout per attempt, and at most one retry for eligible GET failures. It respects Retry-After by declining a retry that cannot be made within its short retry window. Order, payment, and other writes are never automatically retried.
- `hook/userCookie.js` and the authentication/metadata helpers now use the shared client. Personal cart, address, account, and order requests use no-store. Existing short caches remain for public settings and navigation data. Invalid endpoint names cannot generate requests such as `/api/null`.
- Product, category, content, cart, and checkout routes propagate service failures to an error boundary instead of silently treating them as missing or empty data. Genuine missing products still return 404. Optional SEO/default-setting fallbacks remain.
- Category and search consumers load all API pages, validate pagination, and deduplicate product IDs. A failed later page does not publish a partial catalogue. Abort handling prevents older requests from overwriting a newer category/search selection. Category refresh errors retain valid data and expose a retry action.
- Search URLs retain the original query in `q`. The navbar uses the actual total result count when choosing between a detail link and a search results page.
- Checkout no longer requests an unused null address endpoint. Client cart refreshes cancel stale requests, display errors, and prevent placing an order while cart refresh is pending or failed. Address/order screens expose loading failures and retry actions.
- `src/proxy.js` keeps the last valid SEO route configuration, backs off after failures, and signals a cold route-configuration outage to pages so a custom URL is not incorrectly considered missing. Incoming visitor headers cannot spoof that signal.
- Sitemap, global settings, and metadata fetching use the bounded API client. Existing metadata, tracking configuration, canonical URLs, and structured-data generation were preserved; tracking event behavior was not audited in this implementation pass.

### Admin repository

- `BaseController.php` now defaults validation errors to 422 rather than 404; invalid login credentials return 401. Actual missing products retain 404.
- `ProductController.php` uses the same unique-product listing query for search, including matching title, SKU, and slug. Review submission is POST, validates supported fields, rejects duplicates with 409, and no longer inserts arbitrary request fields.
- `CategoryController.php`, `CartController.php`, and `routes/api.php` repair the registered home-categories and cart-count handlers. A shadowed duplicate cancellation route was removed; cancellation still requires authentication.
- `HomeController.php` returns an explicitly unconfigured, empty brands result if that optional table is absent, instead of failing with a SQL exception.
- `OrderController.php` rejects checkout without a customer/session and scopes an authenticated order to that customer's cart rather than combining it with a supplied foreign guest cart.
- `config/cors.php` permits the local frontend at both localhost:3000 and 127.0.0.1:3000 when the app environment is local. Production origins were not broadened.
- Migration `2026_11_03_000001_expand_review_contact_fields.php` expands review name/email columns from 25 to 255 characters. Testing exposed email truncation that prevented duplicate detection. Its rollback deliberately keeps the wider columns to avoid truncating stored contacts; older code can use the wider columns.

## Validation

| Check | Result | Evidence and limits |
| --- | --- | --- |
| Frontend ESLint | PASS | `npm.cmd run lint` |
| Next.js production build | PASS | `npm.cmd run build`; existing Sass import deprecation warnings remain |
| Frontend API and SEO route tests | PASS | 14 tests: transport errors, retry/timeout rules, aborts, private cache behavior, pagination, and cold/cached SEO route outages |
| Backend regression tests | PASS | 18 tests, 176 assertions across ApiFlowReliabilityTest and ProductAvailabilityTest |
| Registered API controller handlers | PASS | All 63 registered routes resolve to existing controller methods; this does not prove every business workflow end to end |
| Complete catalogue retrieval | PASS | 217 products, 217 unique IDs; second API page supplies the final 17 |
| Multi-category product search | PASS | Yellow Sampangi title and hyphenated slug searches each return one unique product |
| Correct error statuses | PASS | Missing input 422, missing product 404, unauthenticated cancellation 401; review route/validation/duplicate responses tested |
| Local CORS preflight | PASS | 127.0.0.1:3000 receives matching Access-Control-Allow-Origin |
| Built storefront HTTP smoke test | PASS | Home, category, product, search, cart, and checkout return 200; fabricated missing product returns 404 |
| Simulated upstream HTML outage | PASS | Isolated production server returned 500 with ApiError for product, category, and checkout instead of an HTTP 404 or successful empty-data response |
| Structured-data JSON syntax | PASS | JSON-LD parsed on the six healthy smoke-test routes; this is not a Google Rich Results validation |
| Live public API spot checks | PASS, limited | Categories and Yellow Sampangi detail returned 200 JSON on the recheck; these local changes are not deployed |
| Browser retry UI and client navigation | NOT TESTED | Cancellation/retry paths were code-reviewed and covered at transport level; no browser interaction session was run |
| Live checkout, payment, account/admin writes | NOT TESTED | No production mutation or payment was submitted |
| GA4 / GTM / Meta event delivery | NOT TESTED | No claim of verified tracking events is made |

Backend test fixtures use database transactions and roll back. The review-column migration was applied only to the verified local database. Environment files were not edited and secret values were not printed.

Re-run automated checks from their respective repository roots:

```powershell
# Frontend
npm.cmd run lint
node --test tools/api-client.test.mjs tools/seo-proxy.test.mjs
npm.cmd run build

# Admin
php vendor/bin/phpunit tests/Feature/ApiFlowReliabilityTest.php tests/Feature/ProductAvailabilityTest.php
```

## Deployment order

1. Commit and push the frontend and admin changes to their separate repositories. Include the new API client, error boundaries, tests, and backend migration; they are new files, not only edits to tracked files.
2. Deploy the admin repository first. Back up the production database and run migration commands from the Laravel application directory that actually contains `artisan`. The domain directory alone may not be the application directory. Check pending migrations before applying them:

   ```bash
   php artisan migrate:status
   php artisan migrate --force
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   ```

   This change needs a migration, not a full local SQL import into the live database. Do not overwrite live orders/customers with a local database export.

3. Confirm the production frontend's `NEXT_PUBLIC_MANIDVIPA_URL` points to `https://admin.manidvipaflowers.com/api`. Preserve the other production settings. Rebuild and redeploy the frontend; NEXT_PUBLIC values are included in the build.
4. Check home, a category, a product, search, cart, and checkout in a real browser. Confirm API responses are JSON and check the server/runtime logs for errors. Exercise one authorized checkout/payment test separately before treating the entire purchase flow as verified.
5. If HTTP 403 HTML challenges recur on public API requests, inspect Hostinger/CDN/WAF logs and the affected API request rules. Keep the exception limited to the necessary public JSON endpoints; an application error-handling fix cannot remove a hosting-layer block.

## Remaining limits

The initial audit also noted shared API rate limits and account/address authorization paths that require a separate review with authenticated test users and production logs. No production load test, hosting-rule change, or full security audit was performed. The implemented cart-owner guards improve the demonstrated checkout path but do not establish that every account endpoint has been security-tested.
