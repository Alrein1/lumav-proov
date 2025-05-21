import cors from "cors";
import { createServer } from "@vue-storefront/middleware";
import { integrations } from "./middleware.config.js";

(async () => {
  const corsMiddleware = cors({
    origin: [
      "http://localhost:3000",
      ...(process.env.MIDDLEWARE_ALLOWED_ORIGINS?.split(",") ?? []),
    ],
    credentials: true,
  });

  const app = await createServer({
    integrations,
    middleware: [corsMiddleware],
  });

  const host = process.argv[2] ?? "0.0.0.0";
  const port = process.argv[3] ?? 8181;

  app.listen(port, host, () => {
    console.log(`Middleware started: ${host}:${port}`);
  });
})();
