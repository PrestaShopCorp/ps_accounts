/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
function destructuring(args) {
  const func = args[0];
  args = Array.prototype.slice.call(args, 1, args.length);

  if (typeof func !== "function")
    throw new Error("[Store Saga]: First Argument Should Be a Function");
  return { args, func };
}

function wrapIt(method, func, args) {
  return {
    wrapped: true,
    method,
    func,
    args,
  };
}

export function call() {
  const { func, args } = destructuring(arguments);
  return wrapIt("CALL", func, args);
}

export function delay(time) {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      resolve(true);
    }, time);
  });
}

function FakeFunction() {}

export function put() {
  const args = arguments;
  const mutation = args[0];
  const payload = args[1];
  return wrapIt("PUT", FakeFunction, [mutation, payload]);
}

export default call;
