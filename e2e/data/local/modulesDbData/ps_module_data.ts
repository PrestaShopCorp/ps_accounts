import {ModulePsAccount} from '~/types/dbInformation';
import dotenv from 'dotenv';
dotenv.config({path: '../e2e-env/.env'});

export const modulePsAccount: ModulePsAccount = {
  name: 'ps_accounts',
  version: (process.env.PS_ACCOUNTS_VERSION || '').replace(/^v/, '').replace(/-beta\.\d+$/, ''),
  isActive: 1
};
