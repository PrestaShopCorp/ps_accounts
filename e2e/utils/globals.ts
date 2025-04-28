import {GlobalVars} from '@prestashopcorp/tests-framework';
import * as process from 'process';

export class Globals extends GlobalVars {
  //URLs
  public static base_url = process.env.BASE_URL ?? 'http://localhost:8000/admin-dev/';
  public static base_url_fo = process.env.BASE_URL_FO ?? 'http://localhost:8000/';
  public static account_base_url_prefixe = process.env.ACCOUNT_BASE_URL_PREFIXE ?? 'url no reachable';
  //Secrets
  public static admin_email = process.env.ADMIN_EMAIL ?? 'email empty';
  public static admin_password = process.env.ADMIN_PASSWORD ?? 'password empty';
  public static account_email = process.env.ACCOUNT_EMAIL ?? 'email empty';
  public static account_password = process.env.ADMIN_PASSWORD ?? 'password empty';
  public static user_agent = process.env.USER_AGENT ?? 'password empty';

  //Health Check Urls
  public static curl = {
    oauth2Url: process.env.OAUTH2URL ?? 'url no reachable',
    accountsApiUrl: process.env.ACCOUNTSAPIURL ?? 'url no reachable',
    accountsUiUrl: process.env.ACCOUNTSUIURL ?? 'url no reachable'
  };
}
