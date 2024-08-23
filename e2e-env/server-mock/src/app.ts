import {CollectorApiServer} from "./lib/collector-api";
import {LiveSyncApiServer} from "./lib/live-sync-api";
import {SyncApiServer} from "./lib/sync-api";
import {WsServer} from "./lib/ws-server";

const probe = new WsServer(+process.env.PROBE_PORT || 8080)

new SyncApiServer(probe).listen(+process.env.SYNC_API_PORT || 3232);
new CollectorApiServer(probe).listen(+process.env.COLLECTOR_API_PORT || 3333);
new LiveSyncApiServer(probe).listen(+process.env.LIVE_SYNC_API_PORT || 3434);
