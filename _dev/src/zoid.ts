import axios from "axios";
import { SignIn } from "@prestashopcorp/ps-accounts-sdk";

const cdc = new SignIn({
  onLogin: (idToken, refreshToken) => {
    const redirect = document
      .querySelector("input#redirect")
      .getAttribute("value");

    const params = new URLSearchParams();
    params.append("ajax", "1");
    params.append("token", idToken);
    params.append("refreshToken", refreshToken);
    params.append("provider", "ps_accounts");
    params.append("submitLogin", "1");
    params.append("redirect", redirect);
    // @ts-ignore
    // eslint-disable-next-line
    params.append("stay_logged_in", $("#stay_logged_in:checked").val());

    // @ts-ignore
    // eslint-disable-next-line
    console.log(`id ${idToken} refresh ${refreshToken}`);
    // index.php?controller=AdminAjaxPsAccounts&ajax=1&action=unlinkShop&token=' . Tools::getAdminTokenLite('AdminAjaxPsAccounts')
    axios
      .post(
        `index.php?controller=AdminPsAccountsSsoConnect&ajax=1&action=ssoConnect`,
        params,
        {
          headers: {
            Accept: "application/json",
          },
        }
      )
      .then((res) => {
        if (res.data.hasErrors) {
          // Function in prestashop core (global actually)
          // @ts-ignore
          // eslint-disable-next-line
          displayErrors(res.data.errors);
        } else {
          window.location.assign(res.data.redirect);
        }
      })
      .catch((err) => {
        const error = document.querySelector("#error");
        error.innerHTML = `<h3>TECHNICAL ERROR:</h3><p>Details: Error thrown: ${err.getMessage()}</p>`;
        error.classList.toggle("hide");
      });
  },
  closeModalOnLogin: true,
  env: "dev",
});

cdc.mount(".prestashop-sso.picture");

export default {
  cdc,
};
