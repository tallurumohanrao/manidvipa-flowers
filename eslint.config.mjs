import { defineConfig, globalIgnores } from "eslint/config";
import nextVitals from "eslint-config-next/core-web-vitals";

const config = defineConfig([
  ...nextVitals,
  globalIgnores([
    ".next/**",
    "node_modules/**",
    "out/**",
    "build/**",
    "next-env.d.ts",
    "admin/node_modules/**",
    "admin/vendor/**",
    "admin/public/assets/admin/vendor/**",
    "admin/public/build/**",
    "admin/storage/**",
    "admin/bootstrap/cache/**",
  ]),
  {
    rules: {
      "react-hooks/immutability": "off",
      "react-hooks/set-state-in-effect": "off",
      "react-hooks/use-memo": "off",
    },
  },
]);

export default config;
