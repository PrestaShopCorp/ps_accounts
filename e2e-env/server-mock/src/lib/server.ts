// @ts-expect-error Express is imported as commonjs module. Raises an error because there is no tsconfig.
import express, {Request, Response, Express, NextFunction} from "express";
import {WsServer} from "./ws-server";

export class Server {
  api: Express;

  public constructor(probe: WsServer) {
    this.api = express();

    this.api.get("/healthcheck", (_req, res) => {
      res.status(200).send({mock: this.constructor.name});
    });

    this.api.use((req: Request, res: Response, next: NextFunction) => {
      // send data to probe after parsing params
      req.on('close', () => {
        probe.sendDataToWS(this.constructor.name, req);
      })
      req.on('data', buf => console.log(buf.toString('utf8')));
      next();
    });
  }

  public async listen(port: number) {
    console.log(`${this.constructor.name} listening on port \x1b[96m${port}\x1b[0m`);
    return this.api.listen(port);
  }
}
