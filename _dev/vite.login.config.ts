import { defineConfig } from "vite";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  build: {
    lib: {
      entry: path.resolve(__dirname, "apps/login/index.ts"),
      name: "AccountsLogin",
      formats: ["es"],
      fileName: () => `js/login.js`,
    },
    outDir: "../views",
    assetsDir: "../views/css",
    emptyOutDir: false,
    rollupOptions: {
      output: {
        assetFileNames: `css/login.[ext]`,
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
