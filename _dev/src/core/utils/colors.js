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
export function hexToRGB(hex, alpha) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);

  if (alpha) {
    return `rgba(${r},${g},${b}, ${alpha})`;
  }
  return `rgb(${r},${g},${b})`;
}

export function decimalToHexadecimal(value) {
  if (value <= 15) {
    return `0${value.toString(16)}`;
  }

  return value.toString(16);
}

export function rgbaToHex(rgba) {
  const parts = rgba.substring(rgba.indexOf("(")).split(",");
  const r = parseInt(parts[0].substring(1).trim(), 10);
  const g = parseInt(parts[1].trim(), 10);
  const b = parseInt(parts[2].trim(), 10);
  const a = parseFloat(
    parts[3].substring(0, parts[3].length - 1).trim()
  ).toFixed(2);

  return `#${decimalToHexadecimal(r)}${decimalToHexadecimal(
    g
  )}${decimalToHexadecimal(b)}${decimalToHexadecimal(a * 255).substring(0, 2)}`;
}

export function isRGBA(rgbaTest) {
  return /^rgba[(](?:\s*0*(?:\d\d?(?:\.\d+)?(?:\s*%)?|\.\d+\s*%|100(?:\.0*)?\s*%|(?:1\d\d|2[0-4]\d|25[0-5])(?:\.\d+)?)\s*,){3}\s*0*(?:\.\d+|1(?:\.0*)?)\s*[)]$/i.test(
    rgbaTest
  );
}

export function getRandomColor(length) {
  const colors = [
    "#2466D4",
    "#B81EFA",
    "#FAB21E",
    "#7A6C03",
    "#169412",
    "#D07F43",
    "#7C6DD0",
    "#563C61",
  ];
  if (length > colors.length) {
    const letters = "0123456789ABCDEF";
    for (let i = 0; i < length - colors.length; i += 1) {
      let color = "#";
      for (let j = 0; j < 6; j += 1) {
        color += letters[Math.floor(Math.random() * 16)];
      }
      colors.push(color);
    }
    return colors;
  }
  return colors.splice(0, length);
}
