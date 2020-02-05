require('jsdom-global')();
var testUtils=require('@vue/test-utils'), Vue=require('vue');
import PsAccount from '../../ui/components/PsAccount';
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
    )).toBe('sso_url/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo%2Fto%2FPSXEmoji.Deluxe.Fake.Service?pubKey=pubKey&name=shopName&bo=boPath&next=nextStep')
  })
  it('should not generate uri for redirecting later and set loaded', () => {

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
    )).toBe('sso_url/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo%2Fto%2FPSXEmoji.Deluxe.Fake.Service?pubKey=pubKey&bo=boPath&next=nextStep&')
  })
})
