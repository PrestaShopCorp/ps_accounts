import { SignIn } from "@prestashopcorp/ps-accounts-sdk";

// // Function in prestashop core (global actually)
// // @ts-ignore
// // eslint-disable-next-line
// displayErrors(res.data.errors);
// const error = document.querySelector("#error");
// error.innerHTML = `<h3>TECHNICAL ERROR:</h3><p>Details: Error thrown: ${err.getMessage()}</p>`;
// error.classList.toggle("hide");

const returnTo = ""; /* document
  .querySelector("input#redirect")
  .getAttribute("value"); */

const stayLoggedIn = 0; /* document
  .querySelector("#stay_logged_in:checked")
  .getAttribute("value"); */

const loginUri = "http://prestashop17.docker.localhost/administration/index.php?controller=AdminOAuth2PsAccounts";
const redirectUri = `${loginUri}&return_to=${returnTo}&stay_logged_in=${stayLoggedIn}`;

const cdc = new SignIn({
  workflow: "loginWithHydra",
  app: "ps_accounts",
  redirectUri,
  closeModalOnLogin: true,
  env: "dev",
  lang: "fr",
  isPopup: true,
});

cdc.mount("#ps-accounts-login");

export default {
  cdc,
};
