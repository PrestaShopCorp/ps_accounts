import { defineConfig } from "vite";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  build: {
    lib: {
      entry: path.resolve(__dirname, "apps/header-alert/index.ts"),
      name: "AccountsHeaderAlert",
      formats: ["es"],
      fileName: () => `js/header-alert.js`,
    },
    outDir: "../views",
    assetsDir: "../views/css",
    emptyOutDir: false,
    rollupOptions: {
      output: {
        assetFileNames: `css/header-alert.[ext]`,
      },
    },
  },
  resolve: {
    alias: [
      {
        find: "@",
        replacement: path.resolve(__dirname, "/apps"),
      },
    ],
  },
});
