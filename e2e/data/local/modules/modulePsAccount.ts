import {ModulePsAccount} from 'types/module';
import dotenv from 'dotenv';
dotenv.config({path: '../e2e-env/.env'});

export const modulePsAccount: ModulePsAccount = {
  name: 'ps_accounts',
  version: process.env.PS_ACCOUNTS_VERSION || '',
  isActive: 1
};
