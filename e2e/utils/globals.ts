import {GlobalVars} from '@prestashopcorp/tests-framework';
import * as process from 'process';

export class Globals extends GlobalVars {
  //URLs
  public static base_url = process.env.BASE_URL ?? 'localhost empty';
  public static base_url_fo = process.env.BASE_URL_FO ?? 'localhost empty';
  public static mbe_base_url = process.env.MBE_BASE_URL ?? 'localhost empty';
  public static account_base_url_prefixe = process.env.ACCOUNT_BASE_URL_PREFIXE ?? 'localhost empty';
  //Secrets
  public static admin_email = process.env.ADMIN_EMAIL ?? 'email empty';
  public static admin_password = process.env.ADMIN_PASSWORD ?? 'password empty';
  public static account_email = process.env.ACCOUNT_EMAIL ?? 'email empty';
  public static account_password = process.env.ADMIN_PASSWORD ?? 'password empty';

  // DB helper vars and functions
  public static db = {
    host: process.env.DB_HOST ?? 'localhost',
    // port: process.env.DB_PORT ? parseInt(process.env.DB_PORT, 10) : 3307,
    port: parseInt(process.env.DB_PORT ?? '3307', 10),
    user: process.env.DB_USER ?? 'root',
    password: process.env.DB_PASSWORD ?? '',
    database: process.env.DB_NAME ?? 'mydatabase'
  };

  //Health Check Urls
  public static curl = {
    oauth2Url: process.env.OAUTH2URL ?? 'localhost empty',
    accountsApiUrl: process.env.ACCOUNTSAPIURL ?? 'localhost empty',
    accountsUiUrl: process.env.ACCOUNTSUIURL ?? 'localhost empty'
  };
}
