import { WebSocketServer } from "ws";
import { Request } from "express";

const MAX_AGE = 30 * 1000;
const MAX_SIZE = 1000;

/**
 * Websocket server used by [mock-probe.ts] to wait for and inspect requests during testing.
 */
export class WsServer {
  private server: WebSocketServer;

  // keep some history in a FIFO in case probe connects after ps_accounts request
  private history: { timestamp: number; data: unknown }[] = [];

  constructor(port: number) {
    this.server = new WebSocketServer({ port });

    this.server.on("error", (err) => {
      console.error(err);
    });

    this.server.on("connection", (client) => {
      console.log(
        `Probe connected. ${this.history.length} messages to replay.`
      );
      for (const el of this.history) {
        client.send(JSON.stringify(el.data));
      }
    });

    console.log(`Probe listening on port \x1b[96m${port}\x1b[0m`);
  }

  /**
   * send data to all connected clients
   * @param apiName
   * @param request
   */
  public sendDataToWS(apiName: string, request: Request) {
    const data = {
      apiName,
      method: request.method,
      headers: request.headers,
      url: request.url,
      query: request.query,
      params: request.params,
      body: request.body ?? {},
    };

    const now = Date.now();

    this.history.push({ timestamp: now, data });
    // evict data older than MAX_AGE
    this.history = this.history
      .slice(-MAX_SIZE)
      .filter((msg) => now - msg.timestamp <= MAX_AGE);

    // there may be no client if we're not runing an automated test suite
    if (this.server.clients.size > 0) {
      this.server.clients.forEach((client) => {
        client.send(JSON.stringify(data));
      });
    }
  }
}
