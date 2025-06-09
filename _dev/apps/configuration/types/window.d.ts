type PsAccountsInitParams = {
    mode: 1 | 2 | 4;
    shopId: number;
    groupId: number;
    getContextUrl: string;
    manageAccountUrl: string;
    psxName: string;
}

interface Window {
    psaccountsVue: any;
    signInComponent: any;
    storePsAccounts: any;
    psAccountsInitParams: PsAccountsInitParams;
}
