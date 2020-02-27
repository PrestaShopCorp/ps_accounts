import generateSvcUiUrl from '../src/services/generateSvcUiUrl';

// eslint-disable-next-line no-undef
describe('PsAccount.vue', () => {
  // eslint-disable-next-line no-undef
  it('Generate Ui Service Url when we have all infomations', () => {
    const generator = generateSvcUiUrl.generate(
      'svcUiDomainName',
      'getProtocolDomainToValidate',
      'getDomainNameDomainToValidate',
      'protocolBo',
      'domainNameBo',
      {
        boUrl: 'getBoUrl',
        pubKey: 'getPubKey',
        shopName: 'getShopName',
        next: 'getNextStep',
      },
    );
    const outputExpected = {
      SvcUiUrlIsGenerated: true,
      queryParams: {
        bo: 'getBoUrl',
        name: 'getShopName',
        next: 'getNextStep',
        pubKey: 'getPubKey',
      },
      svcUiUrl: 'svcUiDomainName/link-shop/getProtocolDomainToValidate/getDomainNameDomainToValidate/protocolBo/domainNameBo/PSXEmoji.Deluxe.Fake.Service?bo=getBoUrl&pubKey=getPubKey&name=getShopName&next=getNextStep',
    };

    // eslint-disable-next-line no-undef
    expect(generator).toEqual(outputExpected);
  });

  // eslint-disable-next-line no-undef
  it('Do not generate Ui Service Url when we do not have all infomations', () => {
    const generator = generateSvcUiUrl.generate(
      'svcUiDomainName',
      'getProtocolDomainToValidate',
      'getDomainNameDomainToValidate',
      null,
      'domainNameBo',
      {
        boUrl: 'getBoUrl',
        pubKey: 'getPubKey',
        shopName: 'getShopName',
        next: null,
      },
    );
    const outputExpected = {
      SvcUiUrlIsGenerated: false,
      queryParams: {
        bo: 'getBoUrl',
        name: 'getShopName',
        next: null,
        pubKey: 'getPubKey',
      },
      svcUiUrl: 'svcUiDomainName/link-shop/getProtocolDomainToValidate/getDomainNameDomainToValidate/null/domainNameBo/PSXEmoji.Deluxe.Fake.Service?bo=getBoUrl&pubKey=getPubKey&name=getShopName&',
    };

    // eslint-disable-next-line no-undef
    expect(generator).toEqual(outputExpected);
  });
});
