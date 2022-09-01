import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  define: {
    "process.env.NODE_ENV": '"production"',
  },
  build: {
    lib: {
      entry: path.resolve(__dirname, "src/main.ts"),
      name: "Accounts",
      formats: ["es"],
      fileName: () => `js/app.${process.env.npm_package_version}.js`,
    },
    outDir: "../views",
    assetsDir: "../views/css",
    emptyOutDir: false,
    rollupOptions: {
      output: {
        assetFileNames: `css/app.${process.env.npm_package_version}.[ext]`,
        chunkFileNames: `js/chunk-vendors.${process.env.npm_package_version}.js`,
      },
    },
  },
  resolve: {
    alias: [
      {
        find: "@",
        replacement: path.resolve(__dirname, "/src"),
      },
    ],
  },
});
