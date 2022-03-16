const path = require("path");

module.exports = {
  entry: "./src/zoid.ts",
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: "ts-loader",
        exclude: /node_modules/,
      },
    ],
  },
  resolve: {
    extensions: [".tsx", ".ts", ".js"],
  },
  output: {
    filename: "zoid.js",
    path: path.resolve(__dirname, "../views/js"),
  },
};
