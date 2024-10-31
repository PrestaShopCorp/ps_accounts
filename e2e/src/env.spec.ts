import { expect } from "@jest/globals";
import { getShopHealthCheck } from "./helpers/data-helper";

describe("e2e setup", () => {
  it("ps_accounts should be up and ready", async () => {
    const healthCheck = await getShopHealthCheck({ cache: false });
    expect(healthCheck.shopLinked).toEqual(false);
    expect(healthCheck.isSsoEnabled).toEqual(false);
    expect(healthCheck.env).toMatchObject({
      oauth2Url: "",
      accountsApiUrl: "",
      accountsUiUrl: "",
      accountsCdnUrl: "",
      testimonialsUrl: "",
      checkApiSslCert: "",
    });
  });
});
