// Import modules
import {createPool} from 'mysql2/promise';

// Import types
import type {FieldPacket, Pool, RowDataPacket, ResultSetHeader} from 'mysql2/promise';
import {Globals} from 'utils/globals'

class DbHelper {
  /**
   * Create a pool
   * @param db
   * @returns {Pool}
   */
  createPool(db = Globals.db): Pool {
    return createPool(db);
  }

  /**
   * Execute an sql query
   * @param query {string} Query to execute
   */
  async executeQuery(query: string): Promise<[RowDataPacket[] | RowDataPacket[][] | ResultSetHeader, FieldPacket[]]> {
    const connection = this.createPool();
    const results = (await connection.execute(query)) as [
      RowDataPacket[] | RowDataPacket[][] | ResultSetHeader,
      FieldPacket[]
    ];
    await this.destroyConnection(connection);
    return results;
  }

  /**
   * Get query results
   * @param query {string} Query to execute
   * @returns {Promise<Array<Object>>}
   */
  async getQueryResults(query: string): Promise<RowDataPacket[] | RowDataPacket[][] | ResultSetHeader> {
    return (await this.executeQuery(query))[0];
  }

  /**
   * Create a custom 'SELECT' query
   * @param table {string} Name of the table
   * @param fields {string|Array<string>} Fields to add to the request
   * @param conditions {?string} Fields to add to the request
   * @return {string}
   */
  createCustomSelectQuery(table: string, fields: string | string[] = '*', conditions?: string): string {
    const customFields = Array.isArray(fields) ? fields.join(',') : fields;
    return `SELECT ${customFields} FROM ${table} ${conditions ? `WHERE ${conditions}` : ''}`;
  }

  /**
   * Execute a custom 'SELECT' query
   * @param table {string} Name of the table
   * @param fields {string|Array<string>} Fields to add to the request
   * @param conditions {?string} Fields to add to the request
   * @return {Promise<Array<Object>>}
   */
  async getResultsCustomSelectQuery(
    table: string,
    fields: string | string[] = '*',
    conditions?: string
  ): Promise<RowDataPacket[] | RowDataPacket[][] | ResultSetHeader> {
    return this.getQueryResults(this.createCustomSelectQuery(table, fields, conditions));
  }

  /**
   * Get query fields
   * @param query {string} Query to execute
   * @returns {Promise<Array<Object>>}
   */
  async getQueryFields(query: string): Promise<FieldPacket[]> {
    return (await this.executeQuery(query))[1];
  }

  /**
   * Destroy sql connection
   * @return {Promise<void>}
   */
  async destroyConnection(connection: Pool): Promise<void> {
    await connection.end();
  }
}

export const dbHelper = new DbHelper();
