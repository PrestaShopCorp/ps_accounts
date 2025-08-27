document.addEventListener('DOMContentLoaded', async function() {
  function getParams() {
    // Replace 'my-script.js' with your actual script filename
    // FIXME: get the right script filename
    const scriptFilename = 'header-alert.js';

    // Find the script tag by looking at all script elements
    const scripts = document.querySelectorAll('script');

    let currentScript: HTMLScriptElement|null = null;
    scripts.forEach(script => {
      if (script.src.includes(scriptFilename)) {
        currentScript = script;
      }
    });

    if (currentScript) {
      // Get the full URL of the script
      const scriptSrc = currentScript['src'];

      // Use the URL API to parse the URL
      const url = new URL(scriptSrc);

      // Access the URLSearchParams object
      return url.searchParams;
    }
    return null;
  }

  async function getContext(uri: string, token: string) {
    return await fetch(uri, {
      method: 'GET',
      headers: {
        'X-Prestashop-Authorization': token,
        'Content-Type': 'application/json'
      }
    })
      .then(response => response.json())
      .then(data => {
        console.log(data);
        return data;
      })
      .catch(error => {
        console.error('Error:', error);
        return null;
      });
  }

  function injectAlert(context: any, settingsUrl: string, container: Element) {
    // FIXME: get the right shop
    //const shop = context?.groups[0]?.shops[0];
    const Break = '';
    try {
      context?.groups[0]?.shops.forEach((shop: any) => {
        const localUrl = shop?.frontendUrl;
        const cloudUrl = shop?.shopStatus?.frontendUrl;

        if (localUrl !== cloudUrl) {
          const alert = document.createElement('div');
          alert.id = 'psacc-alert';
          alert.className = 'bootstrap';
          alert.innerHTML =
            '    <div class="alert alert-danger alert-dismissible">' +
            '        <button type="button" class="close" data-dismiss="alert">×</button>' +
            '        <strong>Warning!</strong> We detected a change in your shop URL..' +
            '        <br/>' +
            '        <ul>' +
            '            <li>PrestaShop Account URL&nbsp;: <em>' + cloudUrl + '</em></li>' +
            '            <li>Your Shop URL&nbsp;: <em>' + localUrl + '</em></li>' +
            '        </ul>' +
            '        Please review your <a href="' + settingsUrl + '">PrestaShop Account settings</a>' +
            '    </div>\n';
          container.prepend(alert);
          throw Break;
        }
      })
    } catch (Break) {
      return true;
    }
    return false;
  }

  const container = document.querySelector('#main-div .content-div, #main #content');
  if (! container) return;

  const params = getParams();
  if (!params) return;

  const uri = params.get('ctx') || '';
  const token = params.get('tok') || '';
  const settingsUrl = params.get('settings') || '';

  const context = await getContext(uri, token);

  // FIXME: double triggered here
  if (context != null && !document.querySelector('#psacc-alert')) {
    injectAlert(context, settingsUrl, container);
  }
});

