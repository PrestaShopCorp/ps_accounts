import R from "ramda";
import axios from "axios";
import testConfig from "./test.config";
import { HealthCheck } from "../type/healthcheck";

let cachedHealthCheck: HealthCheck = null;

export async function getShopHealthCheck(options?: {
  cache: boolean;
}): Promise<HealthCheck> {
  const { cache } = R.mergeLeft(options, { cache: true });
  let healthCheck: HealthCheck;
  if (cache && cachedHealthCheck) {
    healthCheck = cachedHealthCheck;
  } else {
    const res = await axios.get<HealthCheck>(
      `${testConfig.prestashopUrl}/index.php?fc=module&module=ps_accounts&controller=apiV2ShopHealthCheck`
    );
    healthCheck = res.data;
    cachedHealthCheck = healthCheck;
  }
  return healthCheck;
}
