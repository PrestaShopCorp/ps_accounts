require('jsdom-global')();
var testUtils=require('@vue/test-utils'), Vue=require('vue');
import PsAccount from '../../ui/components/PsAccount';

describe('PsAccount.vue', () => {
  beforeEach(() => {
    delete window.publicKey
    delete window.shopName
    delete window.boUrl
  })

  it('should generate uri for redirecting later and set loaded', () => {
    window.publicKey="test"
    window.shopName="shopName"
    window.boUrl="boUrl"

    const wrapper = testUtils.mount(PsAccount, {
      propsData: {
        buttonText: 'Mon beau button',
        vue_app_svc_ui_url: 'http://exemple.com'
      }
    })

    expect(wrapper.vm.getLoaded()).toBe(true)
    expect(wrapper.vm.getSvcUiUrl()).toBe('http://exemple.com/link-shop/boUrl/to/PSXEmoji.Deluxe.Fake.Service?publicKey=test&shopName=shopName')
  })

  it('should not generate uri for redirecting later and set loaded', () => {
    window.shopName="shopName"
    window.boUrl="boUrl"

    const wrapper = testUtils.mount(PsAccount, {
      propsData: {
        buttonText: 'Mon beau button',
        vue_app_svc_ui_url: 'http://exemple.com'
      }
    })

    expect(wrapper.vm.getLoaded()).toBe(false)
    expect(wrapper.vm.getSvcUiUrl()).toBe('http://exemple.com/link-shop/boUrl/to/PSXEmoji.Deluxe.Fake.Service?shopName=shopName&')
  })
})
