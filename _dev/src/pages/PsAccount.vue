<template>
  <div>
    <div
      v-if="params_loaded"
      class="ps_account text-center"
    >
      <button
        @click.stop.prevent="connectSvcUi()"
        class="btn btn-primary"
      >
        {{ buttonText }}
      </button>
    </div>
    <div
      v-else
      class="forbidden text-center"
    >
      Restart onboarding
    </div>
  </div>
</template>
<script>
/* eslint-disable no-console */
  export default {
    name: 'PsAccount',
    props: {
      buttonText: {
        type: String,
        default: '',
      },
    },
    data() {
      return {
        svc_ui_url: null,
        params_loaded: false,
        queryParams: {
          pubKey: null,
          name: null,
          bo: null,
          next: null,
        },
      };
    },
    mounted() {
      this.getSvcUiUrl(
        process.env.VUE_APP_SSO_URL,
        window.protocolDomainToValidate,
        window.domainNameDomainToValidate,
        window.location.protocol.slice(0, -1),
        window.location.host,
        window.queryParams,
        window.pubKey,
        window.shopName,
        '2',
      );
    },

    methods: {
      connectSvcUi() {
        window.location.replace(this.svc_ui_url);
      },
      getSvcUiUrl(
        ssoUrl,
        protocolDomainToValidate,
        domainNameDomainToValidate,
        protocolBo,
        domainNameBo,
        queryParams,
        pubKey,
        shopName,
        nextStep,
      ) {
        this.svc_ui_url = `${ssoUrl
        }/link-shop/${
          protocolDomainToValidate
        }/${
          domainNameDomainToValidate
        }/${
          protocolBo
        }/${
          domainNameBo
        }/PSXEmoji.Deluxe.Fake.Service`;

        let boPath = '';
        for (const [key, value] of Object.entries(queryParams)) {
          boPath += `${key}=${value}&`;
        }
        console.log('ddddddddd');
        this.queryParams.bo = typeof boPath === 'string'
          ? encodeURIComponent(boPath.slice(0, -1))
          : null;
        this.queryParams.pubKey = typeof pubKey === 'string' ? encodeURIComponent(pubKey) : null;
        this.queryParams.name = typeof window.shopName === 'string'
          ? encodeURIComponent(window.shopName)
          : null;
        this.queryParams.next = typeof nextStep === 'string' ? encodeURIComponent(nextStep) : null;
        this.queryParamsLoaded();

        return this.svc_ui_url;
      },

      queryParamsLoaded() {
        const countInitQueryParams = Object.keys(this.queryParams).length;
        let counterValideParams = 0;
        this.svc_ui_url += '?';
        for (const [key, value] of Object.entries(this.queryParams)) {
          if (value !== null) {
            counterValideParams++;
            this.svc_ui_url += `${key}=${value}&`;
          }
        }

        if (countInitQueryParams === counterValideParams) {
          this.params_loaded = true;
          this.svc_ui_url = this.svc_ui_url.slice(0, -1);
        }
      },
    },
  };
</script>
<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h3 {
  margin: 40px 0 0;
}
ul {
  list-style-type: none;
  padding: 0;
}
li {
  display: inline-block;
  margin: 0 10px;
}
a {
  color: #42b983;
}
</style>
