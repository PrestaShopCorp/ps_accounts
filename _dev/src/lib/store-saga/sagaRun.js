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
function isGenFunc(func) {
  return typeof func.prototype.next === "function";
}

export default function sagaRun(genFunc, store = {}) {
  return new Promise((resolve, reject) => {
    const iter = typeof genFunc === "function" ? genFunc(store) : genFunc;
    function runNext(iter, response = null) {
      const nextRun = iter.next(response);
      const data = nextRun.value;
      const isDone = nextRun.done;

      if (!isDone) {
        if (!data)
          throw new Error(
            '[Store Saga]: Please wrap the function next to yield statement inside the effects e.g. "call" or "put"'
          );
        const isOrdinaryGenFunc = data.func;
        const isArrayGenFunc = typeof data === "object" && data.length !== 0;
        const runSingleIter = (data, done, single = true) => {
          const { func, args } = data;
          if (typeof func === "function") {
            if (data.method === "PUT") {
              const [mutation, payload] = data.args;
              store.commit(mutation, payload);
              if (single) {
                return runNext(iter);
              }
              return done ? done() : false;
            }
            if (data.method === "CALL") {
              const functionApplied = func.apply(null, args);
              const isPromise = functionApplied.then !== undefined;
              if (isPromise) {
                return functionApplied.then((res) => {
                  if (single) return runNext(iter, res);
                  return done ? done(res) : false;
                });
              }
              if (isGenFunc(func)) {
                if (single) {
                  return sagaRun(func, store, (res) => {
                    return runNext(iter, res);
                  });
                }
                throw new Error(
                  "[Store Saga]: Can't run parallel generator function at once. But you can still run parallel ordinary function"
                );
              } else {
                if (single) return runNext(iter);
                return done ? done() : false;
              }
            }
          }
        };
        const runArrayIter = (data) => {
          const arrayIter = data;
          const allResponse = [];
          for (const data of arrayIter) {
            runSingleIter(
              data,
              (res) => {
                allResponse.push(res);
                const isDone = allResponse.length === arrayIter.length;
                if (isDone) return runNext(iter, allResponse);
              },
              false
            );
          }
        };

        if (isOrdinaryGenFunc) return runSingleIter(data);
        if (isArrayGenFunc) return runArrayIter(data);
        return runNext(iter, data);
      }

      resolve(nextRun.value);
    }

    return runNext(iter);
  });
}
