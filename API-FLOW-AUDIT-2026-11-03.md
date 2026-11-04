# API flow audit — 3 November 2026

The intermittent failures have more than one cause. The strongest shared failure path is: an unavailable/non-JSON API response becomes `null` or `[]`, then a page treats that as a missing product/category or a successful empty listing. Search and pagination also have independent, reproducible defects.

> Historical baseline, captured before implementation. The findings and failed tests below describe the original code. See [API-FLOW-FIXES-2026-11-03.md](API-FLOW-FIXES-2026-11-03.md) for the implemented fixes, current validation results, and deployment instructions.

At the time of this audit, no application source, environment settings, product/order records, or production configuration had been changed. Laravel request tests used the local application kernel and local MySQL configuration; these are not production browser tests. Normal framework cache/log writes may occur during those requests.

## Coverage and limits

- Checked all 63 registered Laravel API routes for controller-method existence.
- Compared 61 statically identifiable frontend helper calls, covering 30 endpoint names, against the route methods. Dynamic URLs and direct `fetch` calls were also reviewed, but this comparison is not proof that every possible runtime request is valid.
- Tested all 31 active category slugs through local listing requests.
- Tested representative public endpoints, product details, search, pagination, anonymous cart/auth responses, CORS preflights, and selected error cases.
- Reproduced error-to-empty conversion using the actual frontend helper source with mocked responses, without contacting a service or modifying that source.
- Reviewed Next.js configuration, API base URL handling, public/private caching, SEO route resolution, cart/checkout consumers, account/order consumers, and shipping-service error handling.
- Live checks were four read-only GET requests: the homepage and three public API URLs. All received an HTML challenge from `hcdn`. Production application logs, hosting rules, and authenticated customer sessions were not available for this audit.
- No production orders, reviews, payments, emails, account changes, or inventory mutations were submitted. Successful authenticated checkout/payment and every admin action have **not** been verified end to end.

## Confirmed findings

| Priority | Finding | Evidence and effect |
| --- | --- | --- |
| High | Live public requests receive an HTML challenge | Requests from this audit environment to the homepage, `/api/categories`, `/api/products-by-category?category_slug=puja-flowers&per_page=2`, and `/api/product-details?product_slug=yellow-sampangi` returned **403**, `text/html`, server `hcdn`, with a browser-check challenge. This confirms blocking for these requests, not that every customer is blocked or that the origin is down. |
| High | API failures become empty data | `hook/userCookie.js:88–107` parses JSON even for failed responses, drops the HTTP status, and returns `null` on network/JSON errors. Category/settings/cart helpers also return empty arrays on exceptions. An actual-helper simulation of a 403 HTML response returned `null`; consumers resolved it to `[]`. A 500 JSON error similarly reached consumers without a usable status. |
| High | Existing pages can become false 404s | `src/app/(pages)/[...category_path]/page.js:120–138` converts failed categories data to `[]`, then calls `notFound()`. `src/app/(pages)/flowers/[slug]/page.js:230–289` eventually calls `notFound()` after failed detail/fallback resolution. `src/app/(pages)/content/[slug]/page.js:32–36` has the same missing-data assumption. An upstream failure is not evidence that a resource does not exist. Next.js `notFound()` also adds `noindex`. |
| High | Category hydration can remove good server data | `src/components/pages/CategoryProducts.js:1124–1144` fetches again on mount and clears the existing products first. Its helper at `:598–615` returns `[]` when `success` is absent/false, so the error handler is bypassed and a failed browser fetch becomes an empty listing. No cancellation/request-version guard protects against older category/sort requests completing last. The race is a code-level finding, not a reproduced live timing trace. |
| High | Multi-word search changes meaning after navigation | `src/components/Navbar.js:31–51` converts search text into a hyphenated route. `src/app/(pages)/search/[...searchvalue]/page.js:13–32` sends that hyphenated text to an API searching product-title/SKU prefixes. Local `q=Yellow%20Sampangi` returned results; `q=yellow-sampangi` returned zero. |
| High | Search duplicates products for their categories | `admin/app/Http/Controllers/API/ProductController.php:308–371` joins the product-category pivot without deduplicating products before pagination. Local search for `Yellow Sampangi` returned **5 rows for 1 product**; `lotus` returned **3 rows for 1 product**. The navbar interprets duplicate rows as multiple matches and chooses the search-page branch, where the hyphen issue can then produce zero results. |
| High | Catalogue stops at the first 200 records | `src/components/pages/CategoryProducts.js:26,598–615,1285` requests one API page with `per_page=200`, then paginates that result in the browser. It does not consume the API's subsequent pages. Local all-flowers returned **217 total**, **200 records**, **2 API pages**. The remaining **17 products** are outside the client list and its filters. |
| Medium | Guest checkout requests `/api/null` | `src/app/(pages)/checkout/page.js:38–65` leaves the address endpoint null for a guest without a saved address, but calls it anyway. `fetchCartSessionData(null)` was verified to construct `/api/null`; that route returned 404 locally. The fetched `userAddress` variable is not passed into the checkout component. |
| Medium | Validation errors are reported as 404 | `admin/app/Http/Controllers/API/BaseController.php:36` defaults `sendError` to HTTP 404. Product, login/register, cart, and account validation use it without an explicit status. Local `/api/product-details` without `product_slug` returned **404** with validation data. This is an invalid request, not a missing route. |
| Medium | Review form and API disagree on HTTP method | `src/components/pages/ProductDetails.js:644` sends POST `add-review`; `admin/routes/api.php:49` registers GET. A local empty POST was rejected by routing with **405**, before any review controller action ran. |
| Medium | Two registered routes have missing methods | `/api/home-categories` points to missing `HomeController::categories`; `/api/cart-count` points to missing `CartController::getCartCount`. Both returned **500 BadMethodCallException** locally. The current navbar uses `get-cart`, and the current homepage uses `categories?scope=home`, so these legacy routes are not established causes of those current screens failing. |
| Medium | Brands route queries a missing table | `/api/brands` returned **500 QueryException** locally; `Schema::hasTable('brands')` was false. The route is registered and `HomeController.php:221–225` queries that table. No active frontend caller was found. Production schema was not checked. |
| Medium | Duplicate cancellation registration | `admin/routes/api.php:97,105` defines POST `cancel-order` twice with different controllers. The registered route resolves to `AccountController::cancelorder` under `auth:sanctum`; the earlier public handler is shadowed. No cancellation was submitted. |

## Additional reliability and configuration findings

1. **Retry and timeout policy is inconsistent.** The shared frontend helper and SEO proxy fetches have no explicit timeout. The product resolver runs four direct attempts, then nested loops that can download the complete product catalogue. They do not distinguish a genuine 404 from rate limits or server outages. Next.js may memoize identical requests within a render, so the loop count is not a measured network-request count. Replace this with a bounded policy for retryable GET failures, respecting `Retry-After`; do not automatically retry order/payment writes.

2. **The shared rate limit can be a pressure point.** `admin/app/Providers/RouteServiceProvider.php:30–31` applies 120 requests/minute per resolved user or IP to the API group. Homepage rendering, SEO resolution, navbar requests, client re-fetches, and detail fallback loops use many endpoints. Server-rendered requests from multiple visitors can share a frontend-server IP. Verify the effective trusted-proxy/user resolution and 429/access logs before choosing limits. No live 429 was reproduced, and no load test was performed.

3. **SEO aliases depend on another API call.** `src/proxy.js:34–84` fetches `seo-routes` and retains the prior process-local routes on failure. A cold process has no prior routes. Failed refreshes do not set a retry backoff, so later requests try again. It also accepts malformed successful payloads as empty route sets. This can affect admin-managed aliases during an API outage; it does not prove every canonical URL will fail.

4. **Public and personal cache policies need separation.** Known product endpoints correctly request `no-store`. Several public endpoints intentionally revalidate at 60–300 seconds. Other GET requests fall through to `next.revalidate=60`, including personalised endpoints when called on the server; `hook/loginAuth.js:13` caches auth checks for 120 seconds. These should not retain stale cart/account/auth information. Next.js-specific options do not control browser fetch caching. The unused `fetchPageData` helper also incorrectly places `cache` inside `next`.

5. **API origins are not handled consistently.** Some modules have a production fallback and strip trailing slashes; others directly use `NEXT_PUBLIC_MANIDVIPA_URL`. Current local `.env.local` points to a local API, includes `/api`, and has no trailing slash. This is correct for local use. It does not establish what Hostinger built into the deployed frontend. `NEXT_PUBLIC_*` values are embedded at build time, so a production build must use the production URLs. Environment secret values were not printed.

6. **CORS differs by frontend hostname.** In the effective local configuration, preflights for `http://localhost:3000` and `https://www.manidvipaflowers.com` returned the corresponding allow-origin header. `http://127.0.0.1:3000` and the non-www HTTPS frontend did not. The latter is normally redirected by `next.config.mjs`, but localhost and 127.0.0.1 are different browser origins. This is a local observation; live CORS could not be assessed because the CDN intercepted the requests.

7. **Local configuration and routes are cached.** The effective local APP_URL and debug setting match `.env`; there is no demonstrated mismatch for those values. No pending local migrations were reported. After future environment/route changes, deployment must refresh Laravel caches from the actual application directory. These checks say nothing about production migration/configuration state.

8. **Some empty data is legitimate.** All 31 active category slugs returned 200 locally. Garlands returned a valid empty result with total 0. Reviews and clients also returned successful empty results in the sampled requests. These need ordinary empty-state messages, while service failures need an error/retry message. They must remain distinct.

9. **Cart visibility can intentionally differ from stored cart rows.** `CartController::getcart` skips inactive products, non-purchasable products, and disabled/missing selling options. This is another way a cart can shrink without a transport failure. Returning an explanation for removed/unavailable items would make this understandable to customers. The sampled new audit cart session was correctly empty; no existing customer cart was accessed.

10. **Checkout input scoping needs a separate fix.** Code review of `OrderController::store` at `:289–315` found that its cart query only adds ownership predicates when a user/session exists, without the early missing-session check present in `storeWhatsAppOrder`. An invalid request with neither identifier must be rejected before querying carts. This was not exercised with an order-creation request. Also validate address ownership/session association before using an address. Do not remove authentication to resolve the duplicate cancellation route.

11. **Secondary response contracts differ.** Some failures return `success:false` with HTTP 200; some order-detail responses use `status` instead of `success`. `src/app/api/route.js` converts different upstream failures into a generic 400. Current consumers often handle their own shape, but a shared client must preserve compatibility while retaining actual HTTP status and validation errors.

12. **Historical logs contain database failures.** The pre-audit local log had missing-table/column errors, connectivity errors, and deadlocks among its last 100 error entries. These are historical local evidence, not proof of a current production database fault. Production timestamps/request IDs are needed to correlate the user's intermittent failures.

## Verification results

| Check | Result | Scope |
| --- | --- | --- |
| All registered API controller targets exist | FAIL | 2 missing methods out of 63 registered API routes |
| Static frontend helper methods match routes | FAIL | POST `add-review` vs GET route; 61 calls checked |
| Active categories resolve through listing API | PASS | All 31 local active category slugs returned 200 |
| Sample product detail resolves | PASS | Local Yellow Sampangi returned 200 with product, images and selling options |
| Public homepage/settings/navigation/SEO endpoints | PASS for sampled local endpoints | Live JSON availability remains blocked by challenge |
| Live homepage/public API accessible to audit requests | FAIL | 4/4 returned 403 HTML from `hcdn` |
| API errors stay distinguishable from empty results | FAIL | Actual-helper simulations return null/error payloads that consumers convert to empty arrays |
| Missing required product slug uses a validation status | FAIL | Returned 404 instead of a validation response such as 422 |
| Product search deduplicates products | FAIL | 5 rows for 1 Yellow Sampangi; 3 rows for 1 Lotus product |
| Multi-word search survives navigation | FAIL | Space-separated query finds results; hyphenated query returns zero |
| All-products listing covers the complete dataset | FAIL | Client loads 200 out of 217 local products |
| Empty new guest cart | PASS | 200, success true, zero items |
| Anonymous orders/wishlist protected | PASS | Both returned 401 locally |
| Guest without address avoids invalid endpoint | FAIL | Source constructs `/api/null`; local route returns 404 |
| CORS for configured local/www origins | PASS | Local preflights returned exact allow-origin headers |
| Local migrations pending | PASS | No pending entries reported; this does not validate production schema |
| Real browser navigation under delayed/failing requests | NOT RUN | Race/fallback findings come from code and helper simulation |
| Authenticated checkout, payments and admin writes | NOT RUN | No customer/order/inventory changes made |
| ESLint and production build after fixes | NOT RUN | This turn made no application-code changes |

## Recommended repair order

1. **Restore reliable live API access.** Inspect Hostinger/CDN access and challenge logs for the tested endpoints. Configure the API hostname/path for JSON browser and server-to-server traffic without an interactive challenge, keeping authentication, appropriate rate limits, and narrowly scoped security protections. Confirm both the deployed Next.js server and a real customer browser can fetch JSON. Do not disable site-wide security as a blanket workaround.

2. **Fix the shared API contract and failure UI.** Introduce a typed/structured API error with status, endpoint, retryability and safe diagnostic context. Validate status, content type and response shape. Add explicit timeouts and limited retries for safe reads. Preserve existing valid data during refresh; show a retry state on failure. Call `notFound()` only after a successful authoritative lookup confirms absence or the resource API returns a genuine resource-not-found response. Never show guessed prices or fabricate products to conceal outages.

3. **Correct search and pagination.** Preserve search text in a query parameter (support old search URLs), return unique products before API pagination, and use API pagination/filtering consistently. Ensure stock ordering and filters apply to the complete matching set before page slicing. Guard category/sort requests against stale completion.

4. **Correct individual route contracts.** Register review submission as POST; guard optional checkout requests; repair or explicitly retire the unused broken endpoints with compatibility considered; use 422 for validation, 401 for authentication, 403 for permission failures, and 404 for missing resources. Consolidate cancellation into the intended authenticated/ownership-checked handler. Add the missing normal-checkout session guard.

5. **Align caching and deployment.** Use no-store for personal/account/cart/auth data. Keep deliberate public caching with invalidation and validated last-good public data where appropriate. Verify production API/image origins at build time, live CORS origins, Laravel route/config caches, and schema migration status. Preserve SEO metadata, schema, admin-managed routes, and stock/price visibility rules.

6. **Retest before deployment completion.** Test home → category → product → cart → checkout as guest and signed-in user in an isolated test environment. Exercise success, valid empty result, invalid slug, invalid input, expired authentication, 403 HTML, 429, 500, timeout, and out-of-order requests. Verify more than 200 products, multi-category search, review submission, missing-session checkout rejection, and correct server/client data after admin updates. Run lint/build and isolated backend regression tests after changes. Do not point write/checkout tests at production.

The current evidence supports a concrete repair plan. It does not support calling the entire live project fixed or verified yet.
