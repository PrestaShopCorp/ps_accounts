import { defineConfig } from "vite";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  build: {
    lib: {
      entry: path.resolve(__dirname, "apps/notifications/index.ts"),
      name: "AccountsHeaderAlert",
      formats: ["es"],
      fileName: () => `js/notifications.js`,
    },
    outDir: "../views",
    assetsDir: "../views/css",
    emptyOutDir: false,
    rollupOptions: {
      output: {
        assetFileNames: `css/notifications.[ext]`,
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
