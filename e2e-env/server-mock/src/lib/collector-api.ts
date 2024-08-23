import { Server } from "./server";
import {WsServer} from "./ws-server";
// @ts-expect-error we don't care about packaging here
import multer from "multer";

const storage = multer.memoryStorage()
const upload = multer({ storage })

export class CollectorApiServer extends Server {
  public constructor( probe: WsServer) {
    super( probe);

    this.api.get("/", (_req, res) => {
      res.status(200).end();
    });

    this.api.post("/upload/:jobid", upload.single('file'), (req, res) => {
      const jobId = req.params.jobid;
      if (jobId.startsWith("valid-job-")) {
        req.body.file = req.file.buffer.toString()
          .split('\n')
          .map(line => JSON.parse(line.trim()))
        res.status(201).end();
      } else {
        res.status(500).end();
      }
    });

    this.api.post("/delete/:jobid", (req, res) => {
      const jobId = req.params.jobid;
      if (jobId.startsWith("valid-job-")) {
        res.status(201).end();
      } else {
        res.status(500).end();
      }
    });
  }
}
