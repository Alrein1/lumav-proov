<template>
  <div>
    <NuxtRouteAnnouncer />
    <NuxtWelcome />
  </div>
</template>

<script>
import { sdk } from "./sdk.ts";
const products = await sdk.magento.products({});
console.log(products);
</script>
