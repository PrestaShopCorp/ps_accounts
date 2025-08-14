type ContextParamsInit = {
    mode: 1 | 2 | 4;
    shopId: number;
    groupId: number;
    getContextUrl: string;
    manageAccountUrl: string;
    token: string;
    psxName: string;
}

interface Window {
    psaccountsVue: any;
    signInComponent: any;
    storePsAccounts: any;
    contextPsAccounts: {
      [key: string]: any;
      component_params_init: ContextParamsInit;
    };
}
