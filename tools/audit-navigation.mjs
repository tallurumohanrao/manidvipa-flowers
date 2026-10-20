const frontendUrl = (process.env.FRONTEND_URL || "http://localhost:3000").replace(
  /\/$/,
  ""
);
const apiUrl = (
  process.env.API_URL || "http://127.0.0.1:8000/api"
).replace(/\/$/, "");

const fixedRoutes = [
  "/about",
  "/contact-us",
  "/flowers",
  "/cart",
  "/login",
  "/register",
  "/privacy-policy",
  "/terms-conditions",
  "/refund-cancellation",
  "/testimonials",
  "/my-account",
  "/watchlist",
];

const navigationResponse = await fetch(`${apiUrl}/navigation`);
if (!navigationResponse.ok) {
  throw new Error(
    `Navigation API returned HTTP ${navigationResponse.status}.`
  );
}

const navigationPayload = await navigationResponse.json();
const navigationRoutes = (navigationPayload?.data || []).flatMap((item) => [
  item.href,
  ...(item.children || []).map((child) => child.href),
]);
const routes = [
  ...new Set(
    [...navigationRoutes, ...fixedRoutes].filter((route) =>
      String(route).startsWith("/")
    )
  ),
];

async function auditRoute(route) {
  try {
    const response = await fetch(`${frontendUrl}${route}`);
    // Consume the full server-rendered response before opening the next page.
    // This keeps the audit reliable with Laravel's single-worker local server.
    await response.arrayBuffer();
    return {
      route,
      status: response.status,
      finalRoute: new URL(response.url).pathname,
    };
  } catch (error) {
    return { route, status: "ERROR", error: error.message };
  }
}

const results = [];
for (const route of routes) {
  results.push(await auditRoute(route));
}

// A local single-worker PHP server can temporarily reject a burst of SSR API
// calls. Retry only failed destinations after the first pass has drained.
for (let index = 0; index < results.length; index += 1) {
  if (results[index].status === "ERROR" || results[index].status >= 400) {
    await new Promise((resolve) => setTimeout(resolve, 300));
    results[index] = await auditRoute(results[index].route);
  }
}

console.table(results);
const failures = results.filter(
  (result) =>
    result.status === "ERROR" || result.status >= 400
);
if (failures.length) {
  console.error(`Navigation audit failed for ${failures.length} route(s).`);
  process.exitCode = 1;
} else {
  console.log(`Navigation audit passed for ${results.length} routes.`);
}
