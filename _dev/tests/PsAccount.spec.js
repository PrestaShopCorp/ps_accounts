import generateSvcUiUrl from '../src/services/generateSvcUiUrl'

// eslint-disable-next-line no-undef
describe('PsAccount.vue', () => {
  // eslint-disable-next-line no-undef
  it('Generate Ui Service Url when we have all infomations', () => {
    const generator = generateSvcUiUrl.generate(
      'svcUiDomainName',
      'protocolDomainToValidate',
      'domainNameDomainToValidate',
      'protocolBo',
      'domainNameBo',
      {
        boUrl: 'boUrl',
        pubKey: 'getPubKey',
        shopName: 'shopName',
        next: 'nextStep',
      }
    )
    const outputExpected = {
      SvcUiUrlIsGenerated: true,
      queryParams: {
        bo: 'boUrl',
        name: 'shopName',
        next: 'nextStep',
        pubKey: 'getPubKey',
      },
      svcUiUrl:
        'svcUiDomainName/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo/PSXEmoji.Deluxe.Fake.Service?bo=boUrl&pubKey=getPubKey&name=shopName&next=nextStep',
    }

    // eslint-disable-next-line no-undef
    expect(generator).toEqual(outputExpected)
  })

  // eslint-disable-next-line no-undef
  it('Do not generate Ui Service Url when we do not have all infomations', () => {
    const generator = generateSvcUiUrl.generate(
      'svcUiDomainName',
      'protocolDomainToValidate',
      'domainNameDomainToValidate',
      null,
      'domainNameBo',
      {
        boUrl: 'boUrl',
        pubKey: 'getPubKey',
        shopName: 'shopName',
        next: null,
      }
    )
    const outputExpected = {
      SvcUiUrlIsGenerated: false,
      queryParams: {
        bo: 'boUrl',
        name: 'shopName',
        next: null,
        pubKey: 'getPubKey',
      },
      svcUiUrl:
        'svcUiDomainName/link-shop/protocolDomainToValidate/domainNameDomainToValidate/null/domainNameBo/PSXEmoji.Deluxe.Fake.Service?bo=boUrl&pubKey=getPubKey&name=shopName&',
    }

    // eslint-disable-next-line no-undef
    expect(generator).toEqual(outputExpected)
  })
})
