import {expect} from '@playwright/test';
import {dbHelper} from '~/utils/helper/dbHelper';
import {modulePsAccount} from 'data/local/modules/modulePsAccount';
import {RowDataPacket} from 'mysql2';

export default class DbRequest {
  /**
   * Method to Retrieves details of the 'ps_accounts' module from the database.
   * @returns {Promise<RowDataPacket>} A promise that resolves to a `RowDataPacket` containing the 'ps_accounts' module details.
   */
  async getModuleDetails(): Promise<RowDataPacket> {
    const results = await dbHelper.getResultsCustomSelectQuery('ps_module', '*', `name = "ps_accounts"`);
    expect(results).not.toBeNull();
    const module = (results as RowDataPacket[])[0];
    return module;
  }
  // Method to check the module name
  async checkModuleName() {
    const module = await this.getModuleDetails();
    expect(module.name).toBe(modulePsAccount.name);
  }

  // Method to check the module version
  async checkModuleVersion() {
    const module = await this.getModuleDetails();
    expect(module.version).toContain(modulePsAccount.version);
  }

  // Method to check if the module is active
  async checkModuleIsActive() {
    const module = await this.getModuleDetails();
    expect(module.active).toBe(modulePsAccount.isActive);
  }
}
