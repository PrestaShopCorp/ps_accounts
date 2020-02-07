require('jsdom-global')();
var testUtils=require('@vue/test-utils'), Vue=require('vue');
import {PsAccount} from '../components/PsAccount';

describe('PsAccount.vue', () => {
  beforeEach(() => {
    delete window.pubKey;
    delete window.shopName;
    delete window.protocolDomainToValidate;
    delete window.domainNameDomainToValidate;
  })
  it('should generate uri for redirecting later and set loaded', () => {
    window.pubKey="test";
    window.shopName="shopName";
    window.protocolDomainToValidate="http";
    window.domainNameDomainToValidate="/admin-dev/index.php";
    window.queryParams={'hmac': 'hmac', 'uid': 'uid', 'slug': 'slug'};

    const wrapper = testUtils.mount(PsAccount, {
      propsData: {
        buttonText: 'Mon beau button',
        vue_app_svc_ui_url: 'http://exemple.com'
      }
    })

    expect(wrapper.vm.getSvcUiUrl(
      'sso_url',
      'protocolDomainToValidate',
      'domainNameDomainToValidate',
      'protocolBo',
      'domainNameBo',
      'boPath',
      'pubKey',
      'shopName',
      'nextStep'
    )).toBe('sso_url/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo/PSXEmoji.Deluxe.Fake.Service?pubKey=pubKey&name=shopName&bo=0%3Db%261%3Do%262%3DP%263%3Da%264%3Dt%265%3Dh&next=nextStep')
  })

  it('should not generate uri for redirecting later and set loaded', () => {
    window.pubKey="test";
    window.shopName="shopName";
    window.protocolDomainToValidate="http";
    window.domainNameDomainToValidate="/admin-dev/index.php";
    window.queryParams={'hmac': 'hmac', 'uid': 'uid', 'slug': 'slug'};

    const wrapper = testUtils.mount(PsAccount, {
      propsData: {
        buttonText: 'Mon beau button',
        vue_app_svc_ui_url: 'http://exemple.com'
      }
    })
    expect(wrapper.vm.getSvcUiUrl(
      'sso_url',
      'protocolDomainToValidate',
      'domainNameDomainToValidate',
      'protocolBo',
      'domainNameBo',
      'boPath',
    )).toBe('sso_url/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo/PSXEmoji.Deluxe.Fake.Service?name=shopName&bo=0%3Db%261%3Do%262%3DP%263%3Da%264%3Dt%265%3Dh&')
  })
})
