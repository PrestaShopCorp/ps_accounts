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
import request from "@/core/utils/request";
import { forEach } from "lodash";

function prepareForm(params) {
  const form = new FormData();
  form.append("ajax", true);
  form.append("action", params.action);
  form.append("controller", params.controller);
  forEach(params.data, (value, key) => {
    form.append(key, value);
  });
  return form;
}

export default {};

export function fetchTotal(url, params) {
  const requestParams = {
    action: "RetrieveData",
    controller: "AdminAjaxDashboard",
    data: {
      type: "total",
      dateRange: JSON.stringify({
        startDate: params.date.start,
        endDate: params.date.end,
      }),
      granularity: params.granularity,
    },
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function fetchRevenues(url, params) {
  const requestParams = {
    action: "RetrieveData",
    controller: "AdminAjaxDashboard",
    data: {
      type: "revenues",
      dateRange: JSON.stringify({
        startDate: params.date.start,
        endDate: params.date.end,
      }),
      granularity: params.granularity,
    },
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function fetchConversions(url, params) {
  const requestParams = {
    action: "RetrieveData",
    controller: "AdminAjaxDashboard",
    data: {
      type: "conversion",
      dateRange: JSON.stringify({
        startDate: params.date.start,
        endDate: params.date.end,
      }),
      granularity: params.granularity,
    },
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function fetchOrders(url, params) {
  const requestParams = {
    action: "RetrieveData",
    controller: "AdminAjaxDashboard",
    data: {
      type: "orders",
      dateRange: JSON.stringify({
        startDate: params.date.start,
        endDate: params.date.end,
      }),
      granularity: params.granularity,
    },
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function fetchTipsCards(url) {
  const requestParams = {
    action: "RetrieveTipsCards",
    controller: "AdminAjaxDashboard",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function fetchVisits(url, params) {
  const requestParams = {
    action: "RetrieveData",
    controller: "AdminAjaxDashboard",
    data: {
      type: "visits",
      dateRange: JSON.stringify({
        startDate: params.date.start,
        endDate: params.date.end,
      }),
      granularity: params.granularity,
    },
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function gaLogOut(url) {
  const requestParams = {
    action: "LogOut",
    controller: "AdminAjaxSettings",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function selectAccountAnalytics(url, params) {
  const requestParams = {
    action: "SelectAccountAnalytics",
    controller: "AdminAjaxSettings",
    data: {
      webPropertyId: params.webPropertyId,
      viewId: params.viewId,
      username: params.username,
      webPropertyName: params.webPropertyName,
    },
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function getAvailableGoogleTags(url) {
  const requestParams = {
    action: "GetExistingGoogleTags",
    controller: "AdminAjaxSettings",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function gaRefresh(url) {
  const requestParams = {
    action: "RefreshGA",
    controller: "AdminAjaxSettings",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function getListProperty(url) {
  const requestParams = {
    action: "ListProperty",
    controller: "AdminAjaxSettings",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function installModuleGA(url) {
  request.createApi();
  return request.api.post(url);
}

export function enableDashboardModules(url) {
  const requestParams = {
    action: "EnableDashboardModules",
    controller: "AdminAjaxDashboard",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}

export function initBillingFree(url) {
  const requestParams = {
    action: "BillingFree",
    controller: "AdminAjaxSettings",
  };
  const form = prepareForm(requestParams);
  request.createApi();
  return request.api.post(url, form);
}
