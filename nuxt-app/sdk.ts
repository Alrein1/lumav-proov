import { buildModule, initSDK } from "@vue-storefront/sdk";
import { magentoModule } from "@vue-storefront/magento-sdk"; // ❌ no MagentoModuleType

const sdkConfig = {
  magento: buildModule(magentoModule, {
    apiUrl: "http://localhost:8181/magento",
  }),
};

export const sdk = initSDK(sdkConfig);
