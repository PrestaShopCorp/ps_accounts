/* eslint-disable import/prefer-default-export */
/* eslint-disable func-names */
import Vue from "vue";
import * as Sentry from "@sentry/browser";
import { Integrations as TracingIntegrations } from "@sentry/tracing";
import { Vue as VueIntegration } from "@sentry/integrations";
import ErrorBoundary from "./error-boundary.vue";

import { logger } from "./app-logger";

Sentry.init({
  dsn:
    "https://2a5c0f00362b455b987cb2c6a5499ebe@o298402.ingest.sentry.io/5378452",
  integrations: [
    new TracingIntegrations.BrowserTracing(),
    new VueIntegration({
      Vue,
      tracing: true,
      attachProps: true,
      logErrors: true,
      tracingOptions: {
        trackComponents: true,
        hooks: ["mount", "update", "destroy"],
        timeout: 4000,
      },
    }),
  ],
  debug: process.env.NODE_ENV === "production",
  tracesSampleRate: 1.0,
  environment: process.env.NODE_ENV,
});

Vue.config.errorHandler = (err, vm, info) => {
  logger.logToServer({ err, vm, info });
};

window.onerror = function (message, source, lineno, colno, error) {
  logger.logToServer({ message, source, lineno, colno, error });
};

Vue.component(ErrorBoundary.name, ErrorBoundary);
