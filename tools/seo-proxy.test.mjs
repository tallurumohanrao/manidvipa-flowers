import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import { NextRequest } from "next/server.js";

// Load an isolated proxy cache per test while keeping the real Next response API.
async function loadProxy(id) {
  const source = (await readFile(new URL("../src/proxy.js", import.meta.url), "utf8"))
    .replace('"@/lib/apiClient"', JSON.stringify(new URL("../src/lib/apiClient.js", import.meta.url).href))
    .replace('"next/server"', JSON.stringify(new URL("../node_modules/next/server.js", import.meta.url).href));
  return import(`data:text/javascript;base64,${Buffer.from(source).toString("base64")}#${id}`);
}

const request = (path, headers) => new NextRequest(`http://localhost${path}`, { headers });
const routesResponse = () => new Response(JSON.stringify({
  success: true, data: { routes: [{ url: "/our-flowers", alias: "/pooja-flowers" }], redirects: [] },
}), { headers: { "content-type": "application/json" } });

test("a cold SEO route outage reaches the page instead of becoming a false missing route", async (t) => {
  const fetch = t.mock.method(globalThis, "fetch", async () => new Response("Blocked", { status: 403 }));
  const { proxy } = await loadProxy("cold-outage");
  const response = await proxy(request("/our-flowers"));
  assert.equal(response.headers.get("x-middleware-request-x-seo-routes-unavailable"), "1");
  await proxy(request("/another-page"));
  assert.equal(fetch.mock.callCount(), 1, "failure backoff avoids a request for each page");
});

test("valid SEO aliases survive a later outage and visitor headers cannot fake an outage", async (t) => {
  let now = 1000;
  t.mock.method(Date, "now", () => now);
  const fetch = t.mock.method(globalThis, "fetch", async () => routesResponse());
  const { proxy } = await loadProxy("cached-routes");
  const first = await proxy(request("/our-flowers"));
  assert.equal(first.headers.get("x-middleware-rewrite"), "http://localhost/pooja-flowers");
  now += 61000;
  fetch.mock.mockImplementation(async () => new Response("Blocked", { status: 403 }));
  const stale = await proxy(request("/our-flowers"));
  assert.equal(stale.headers.get("x-middleware-rewrite"), first.headers.get("x-middleware-rewrite"));
  const normal = await proxy(request("/cart", { "x-seo-routes-unavailable": "1" }));
  assert.equal(normal.headers.get("x-middleware-request-x-seo-routes-unavailable"), null);
  assert.equal(fetch.mock.callCount(), 2);
});
