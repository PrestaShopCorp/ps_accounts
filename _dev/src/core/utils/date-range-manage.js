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
import * as moment from "moment";

export function getWeekRange(week = 0) {
  const weekStart = moment().add(week, "weeks").startOf("week");

  return [...Array(7)].map((_, i) =>
    weekStart.clone().add(i, "days").format("YYYY-MM-DD")
  );
}

export function getPrevMonthDays(days = 90) {
  const dateStart = moment();
  const daysArray = [];
  const dateEnd = moment().subtract(days, "days");
  while (dateEnd.diff(dateStart, "days") <= 0) {
    daysArray.push(dateStart.format("YYYY-MM-DD"));
    dateStart.subtract(1, "days");
  }
  return daysArray;
}

export function getDayRange(startDate, endDate, granularity) {
  const dateArray = [];
  let currentDate = moment(startDate);
  const stopDate = moment(endDate);
  return new Promise((resolve) => {
    while (currentDate <= stopDate) {
      dateArray.push(moment(currentDate).format("YYYY-MM-DD"));
      currentDate = moment(currentDate).add(1, "days");
    }
    if (granularity === "weeks") {
      const dateArrayWeek = groupsByWeek(dateArray);
      resolve(dateArrayWeek);
    } else if (granularity === "months") {
      const dateArrayMonth = groupsByMonth(dateArray);
      resolve(dateArrayMonth);
    }
    resolve(dateArray);
  });
}

export function groupsByWeek(dates) {
  return dates
    .map((date) => {
      return `${moment(date).year()}-${moment(date).format("WW")}`;
    })
    .filter(onlyUnique);
}

export function groupsByMonth(dates) {
  return dates
    .map((date) => {
      return `${moment(date).year()}-${moment(date).format("MM")}`;
    })
    .filter(onlyUnique);
}

function onlyUnique(value, index, self) {
  return self.indexOf(value) === index;
}
