import { SignIn } from "@prestashopcorp/ps-accounts-sdk";
// // Function in prestashop core (global actually)
// // @ts-ignore
// // eslint-disable-next-line
// displayErrors(res.data.errors);
// const error = document.querySelector("#error");
// error.innerHTML = `<h3>TECHNICAL ERROR:</h3><p>Details: Error thrown: ${err.getMessage()}</p>`;
// error.classList.toggle("hide");

const cdc = new SignIn({
  workflow: "loginWithHydra",
  app: "ps_accounts",
  // redirectUri: oauth2Uri,
  closeModalOnLogin: true,
  env: "dev",
  lang: "fr",
  isPopup: true,
});

// cdc.mount("#ps-accounts-login");

window.signInComponent = cdc;

export default {
  cdc,
};
