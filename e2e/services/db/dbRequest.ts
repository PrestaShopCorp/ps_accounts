import {expect} from '@playwright/test';
import {dbHelper} from '~/utils/helper/dbHelper';
import {modulePsAccount} from '~/data/local/modulesDbData/ps_module_data';
import {RowDataPacket} from 'mysql2';
import {M} from '~/playwright-report/trace/assets/defaultSettingsView-BA25Usqk';

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

  // Method to return the module version
  async returnModuleVersion() {
    const module = await this.getModuleDetails();
    return module.version;
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
    expect(data.name).toBeDefined();
    expect(data.name).toBe(name);
    return data.value;
  }

  /**
   * Method to delete entries from ps_configuration
   * @param names - list of configuration names to delete
   * @returns {Promise<void>}
   */
  async deletePsConfigurationData(names: string[]): Promise<void> {
    if (!names.length) return;

    const condition = `name IN (${names.map((n) => `"${n}"`).join(', ')})`;
    await dbHelper.executeCustomDeleteQuery('ps_configuration', condition);
  }

  /**
   * Method to delete accounts informations to unverify the shop
   */
  async deleteAccountsInfo() {
    await this.deletePsConfigurationData([
      'PS_ACCOUNTS_ACCESS_TOKEN',
      'PS_ACCOUNTS_OAUTH2_CLIENT_ID',
      'PS_ACCOUNTS_OAUTH2_CLIENT_SECRET',
      'PS_ACCOUNTS_SHOP_STATUS',
      'PS_ACCOUNTS_SHOP_PROOF'
    ]);
  }

  /**
   * Method to delete accounts informations to unverify the shop and block the reverification
   */
  async deleteAccountsInfoAndBlockReverification() {
    await this.deletePsConfigurationData([
      'PS_ACCOUNTS_ACCOUNTS_CLIENT_FAILURE_COUNT',
      'PS_ACCOUNTS_ACCOUNTS_CLIENT_LAST_FAILURE_TIME',
      'PS_ACCOUNTS_OAUTH2_SERVICE_FAILURE_COUNT',
      'PS_ACCOUNTS_OAUTH2_SERVICE_LAST_FAILURE_TIME',
      'PS_ACCOUNTS_LAST_UPGRADE',
      'PS_ACCOUNTS_ACCOUNTS_SERVICE_FAILURE_COUNT',
      'PS_ACCOUNTS_ACCOUNTS_SERVICE_LAST_FAILURE_TIME',
      'PS_ACCOUNTS_ACCESS_TOKEN',
      'PS_ACCOUNTS_OAUTH2_CLIENT_ID',
      'PS_ACCOUNTS_OAUTH2_CLIENT_SECRET',
      'PS_ACCOUNTS_SHOP_STATUS',
      'PS_ACCOUNTS_SHOP_PROOF',
      'PS_ACCOUNTS_TOKEN_SIGNATURE'
    ]);
  }

  /**
   * Method to delete accounts informations to unverify the shop and block the reverification
   */
  async deleteTokens() {
    await this.deletePsConfigurationData([
      'PS_TOKEN_ENABLE',
      'PS_SECURITY_TOKEN',
      'PS_PSX_FIREBASE_ID_TOKEN',
      'PS_PSX_FIREBASE_REFRESH_TOKEN',
      'PS_ACCOUNTS_FIREBASE_ID_TOKEN',
      'PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN',
      'PS_ACCOUNTS_ACCESS_TOKEN'
    ]);
  }

  /**
   * Update virtual_uri in ps_shop_url for a given shop id
   * @param shopId - id_shop in ps_shop_url
   * @param newVirtualUri - new value for virtual_uri
   */
  async updatePsShopVirtualUri(shopId: number, newVirtualUri: string): Promise<void> {
    const updates = `virtual_uri = "${newVirtualUri}"`;
    const conditions = `id_shop = ${shopId}`;
    await dbHelper.executeCustomUpdateQuery('ps_shop_url', updates, conditions);
  }

  /**
   * Method to update virtual uri from ps_shop_url
   */
  async updateUri() {
    await this.updatePsShopVirtualUri(2, 'shop2/');
  }

  /**
   * Method to return details from the database ps_shop_url
   * @expect Uri toBe 'shop2/'
   */
  async getPsShopUrlUri(){
    const results = await dbHelper.getResultsCustomSelectQuery('ps_shop_url', 'virtual_uri', `id_shop = 2`);
    expect(results).not.toBeNull();
    const row = (results as RowDataPacket[])[0];
    const uri = row.virtual_uri as string;
    expect(uri).toBe('shop2/');
  }
}
