export default {
  // client test configuration
  prestashopUrl:
    process.env.RUN_IN_DOCKER === "1"
      ? "http://prestashop"
      : "http://localhost:8000",
  prestaShopHostHeader: "localhost:8000",
  testRunTime: new Date().toISOString(),
};
