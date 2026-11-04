import test from "node:test";
import assert from "node:assert/strict";
import { requestApi, isApiNotFound, fetchAllProducts } from "../src/lib/apiClient.js";

const json = (body, status = 200, headers = {}) => new Response(JSON.stringify(body), {
  status, headers: { "content-type": "application/json", ...headers },
});

test("HTML challenges are errors, never empty catalog data or a product 404", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch", async () => new Response("Checking your browser", { status: 403 }));
  await assert.rejects(requestApi("categories"), (error) => error.status === 403 && !isApiNotFound(error));
  assert.equal(fetch.mock.callCount(), 1);
});

test("only a real JSON not-found response qualifies for a product 404", async (t) => {
  t.mock.method(globalThis, "fetch", async () => json({ success: false, message: "Page not found." }, 404));
  await assert.rejects(requestApi("product-details?product_slug=missing"), isApiNotFound);
});

test("one safe retry recovers a transient GET failure", async (t) => {
  let calls = 0;
  t.mock.method(globalThis, "fetch", async () => ++calls === 1 ? json({}, 503) : json({ success: true, data: [] }));
  assert.deepEqual(await requestApi("categories"), { success: true, data: [] });
  assert.equal(calls, 2);
});

test("writes are never retried and preserve validation status", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch", async () => json({ success: false, message: "Try again" }, 503));
  await assert.rejects(requestApi("store-order", { method: "POST", body: {} }), { status: 503 });
  assert.equal(fetch.mock.callCount(), 1);
});

test("rate limiting does not retry before Retry-After", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch", async () => json({}, 429, { "retry-after": "60" }));
  await assert.rejects(requestApi("search"), { status: 429 });
  assert.equal(fetch.mock.callCount(), 1);
});

test("timeouts terminate hung requests", async (t) => {
  t.mock.method(globalThis, "fetch", (_url, { signal }) => new Promise((_resolve, reject) => {
    signal.addEventListener("abort", () => reject(new DOMException("Aborted", "AbortError")));
  }));
  await assert.rejects(requestApi("categories", { timeoutMs: 10, retries: 0 }), { code: "TIMEOUT" });
});

test("cancelled navigation does not retry or return stale data", async (t) => {
  const controller = new AbortController();
  controller.abort();
  const fetch = t.mock.method(globalThis, "fetch");
  await assert.rejects(requestApi("search", { signal: controller.signal }), { name: "AbortError" });
  assert.equal(fetch.mock.callCount(), 0);
});

test("personal data is uncached; GET avoids JSON Content-Type preflights", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch", async () => json({ success: true, data: [] }));
  await requestApi("get-cart", { token: "test-only" });
  const options = fetch.mock.calls[0].arguments[1];
  assert.equal(options.cache, "no-store");
  assert.equal(options.headers.Accept, "application/json");
  assert.equal(options.headers.Authorization, "Bearer test-only");
  assert.equal(options.headers["Content-Type"], undefined);
});

test("invalid endpoints never make an HTTP request", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch");
  for (const endpoint of [null, undefined, "null", "undefined", "https://example.com/"]) {
    await assert.rejects(requestApi(endpoint), { code: "INVALID_ENDPOINT" });
  }
  assert.equal(fetch.mock.callCount(), 0);
});

test("pagination loads all 217 products and keeps the original search query", async (t) => {
  const query = "Yellow Sampangi & flowers";
  t.mock.method(globalThis, "fetch", async (url) => {
    const params = new URL(url).searchParams;
    assert.equal(params.get("q"), query);
    const page = Number(params.get("page"));
    const data = Array.from({ length: page === 1 ? 200 : 17 }, (_, i) => ({ id: (page - 1) * 200 + i + 1 }));
    return json({ success: true, data: { data, current_page: page, last_page: 2 } });
  });
  const products = await fetchAllProducts(`search?${new URLSearchParams({ q: query })}`);
  assert.equal(products.length, 217);
  assert.equal(products[216].id, 217);
});

test("failed or malformed later pages never produce a partial catalog", async (t) => {
  t.mock.method(globalThis, "fetch", async (url) => new URL(url).searchParams.get("page") === "1"
    ? json({ success: true, data: { data: [{ id: 1 }], current_page: 1, last_page: 2 } })
    : json({ success: true, data: null }));
  await assert.rejects(fetchAllProducts("search"), { code: "INVALID_RESPONSE" });
});

test("successful empty results stay valid; API-level failures do not", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch", async () => json({ success: true, data: { data: [], current_page: 1, last_page: 1 } }));
  assert.deepEqual(await fetchAllProducts("search?q=missing"), []);
  fetch.mock.mockImplementation(async () => json({ success: false, message: "Unavailable" }));
  await assert.rejects(requestApi("search"), { code: "API_REJECTED" });
});
