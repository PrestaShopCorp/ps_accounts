import {expect} from '@playwright/test';
import {dbHelper} from '~/utils/helper/dbHelper';
import {modulePsAccount} from '~/data/local/modulesDbData/ps_module_data';
import {RowDataPacket} from 'mysql2';
import {config} from 'cypress/types/bluebird';

export default class DbRequest {
  /**
   * Method to Retrieves details of the 'ps_accounts' module info from the database ps_module.
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
    return module.version;
  }

  // Method to check if the module is active
  async checkModuleIsActive() {
    const module = await this.getModuleDetails();
    expect(module.active).toBe(modulePsAccount.isActive);
  }

  /**
   * Method to return details from the database ps_configuration
   * @param name - the cell name
   * @returns {Promise<RowDataPacket>} A promise that resolves to a `RowDataPacket` containing the 'ps_accounts' module details.
   */
  async getPsConfigurationData(name: string): Promise<RowDataPacket> {
    const results = await dbHelper.getResultsCustomSelectQuery('ps_configuration', '*', `name = "${name}"`);
    expect(results).not.toBeNull();
    const module = (results as RowDataPacket[])[0];
    return module;
  }
  /**
   * Method to check if expected Name isvisible
   * @param name - the cell name to verify
   * @return boolean on value
   */
  async checkPsConfigurationData(name: string): Promise<boolean> {
    const data = await this.getPsConfigurationData(name);
    expect(data.name).toBeDefined
    expect(data.name).toBe(name);
    return data.value;
  }
}
