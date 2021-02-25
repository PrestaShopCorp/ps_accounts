/* eslint-disable import/prefer-default-export */
/* eslint no-console: ["off"] */
import { environment } from "@/environment";
import * as Sentry from "@sentry/browser";
/**
 * @description Logger class
 * This is responsible for logging of all kind of stuff in the application
 * Default, we are using the console api for logging and this provides the basic level of logging such as
 * you can use the available method of console in developement but in production these will be replaced with empty methods
 * This can be extended with the help of adding Log level functionality
 */
class AppLogger {
  /**
   * @constructor AppLogger
   */
  constructor() {
    /** Initializing the configuration of logger */
    this.initLogger();
  }

  /**
   * @description Initializing the configuration such as if environment is production then all log method will be replaced with empty methods
   * except logToServer, which will be responsible for logging the important stuff on server
   */
  initLogger() {
    /** Checking the environment */
    if (environment !== "production") {
      this.log = console.log.bind(console);

      this.debug = console.debug.bind(console);

      this.info = console.info.bind(console);

      this.warn = console.warn.bind(console);

      this.error = console.error.bind(console);

      this.logToServer = this.error;
    } else {
      /** In case of production replace the functions definition */
      // eslint-disable-next-line no-multi-assign
      this.log = this.debug = this.info = this.warn = this.error = () => {};

      this.logToServer = (err) => {
        /** temp added to print in the console during production */
        Sentry.captureException(err);
      };
    }
  }
}

/** Creating the instance of logger */
const logger = new AppLogger();

export { logger };
