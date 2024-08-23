import {Server} from "./server";
import {WsServer} from "./ws-server";

export class SyncApiServer extends Server {
  public constructor(probe: WsServer) {
    super(probe);

    this.api.get("/", (_req, res) => {
      res.status(200).end();
    });

    this.api.get("/job/:id", (req, res) => {
      const jobId = req.params.id;
      if (jobId.startsWith("valid-job-")) {
        res.status(201).end();
      } else {
        res.status(500).end();
      }
    });

    this.api.get("/notify/:shopId", (req, res) => {
      const shopId = req.params.shopId;
      if (shopId.startsWith("valid-shopid-")) {
        res.status(201).end();
      } else {
        res.status(500).end();
      }
    });
  }
}
