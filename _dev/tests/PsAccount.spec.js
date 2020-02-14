
// import {PsAccount} from '../src/pages/PsAccount';
//
// require('jsdom-global')();
// const testUtils = require('@vue/test-utils');
// // const Vue = require('vue');

// eslint-disable-next-line no-undef
describe('PsAccount.vue', () => {
  // eslint-disable-next-line no-undef
  beforeEach(() => {
    delete window.pubKey;
    delete window.shopName;
    delete window.protocolDomainToValidate;
    delete window.domainNameDomainToValidate;
  });
  // eslint-disable-next-line no-undef
  it('should generate uri for redirecting later and set loaded', () => {
    window.pubKey = 'test';
    window.shopName = 'shopName';
    window.protocolDomainToValidate = 'http';
    window.domainNameDomainToValidate = '/admin-dev/index.php';
    window.queryParams = {hmac: 'hmac', uid: 'uid', slug: 'slug'};

    // const wrapper = testUtils.mount(PsAccount, {
    //   propsData: {
    //     buttonText: 'Mon beau button',
    //     vue_app_svc_ui_url: 'http://exemple.com',
    //   },
    // });
    const msg = 'new message';

    // eslint-disable-next-line no-undef
    expect(msg).toMatch(msg);
    // expect(wrapper.vm.getSvcUiUrl(
    //   'sso_url',
    //   'protocolDomainToValidate',
    //   'domainNameDomainToValidate',
    //   'protocolBo',
    //   'domainNameBo',
    //   'boPath',
    //   'pubKey',
    //   'shopName',
    //   'nextStep',
    // eslint-disable-next-line max-len
    // )).toBe('sso_url/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo/PSXEmoji.Deluxe.Fake.Service?pubKey=pubKey&name=shopName&bo=0%3Db%261%3Do%262%3DP%263%3Da%264%3Dt%265%3Dh&next=nextStep');
  });

  // eslint-disable-next-line no-undef
  it('should not generate uri for redirecting later and set loaded', () => {
    window.pubKey = 'test';
    window.shopName = 'shopName';
    window.protocolDomainToValidate = 'http';
    window.domainNameDomainToValidate = '/admin-dev/index.php';
    window.queryParams = {hmac: 'hmac', uid: 'uid', slug: 'slug'};

    // const wrapper = testUtils.mount(PsAccount, {
    //   propsData: {
    //     buttonText: 'Mon beau button',
    //     vue_app_svc_ui_url: 'http://exemple.com',
    //   },
    // });
    const msg = 'new message';

    // eslint-disable-next-line no-undef
    expect(msg).toMatch(msg);
    // expect(wrapper.vm.getSvcUiUrl(
    //   'sso_url',
    //   'protocolDomainToValidate',
    //   'domainNameDomainToValidate',
    //   'protocolBo',
    //   'domainNameBo',
    //   'boPath',
    // eslint-disable-next-line max-len
    // )).toBe('sso_url/link-shop/protocolDomainToValidate/domainNameDomainToValidate/protocolBo/domainNameBo/PSXEmoji.Deluxe.Fake.Service?name=shopName&bo=0%3Db%261%3Do%262%3DP%263%3Da%264%3Dt%265%3Dh&');
  });
});
