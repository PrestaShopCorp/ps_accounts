document.addEventListener('DOMContentLoaded', async function() {
  function getParams() {
    // Replace 'my-script.js' with your actual script filename
    // FIXME: get the right script filename
    const scriptFilename = 'alert.js';

    // Find the script tag by looking at all script elements
    const scripts = document.querySelectorAll('script');

    let currentScript = null;
    scripts.forEach(script => {
      if (script.src.includes(scriptFilename)) {
        currentScript = script;
      }
    });

    if (currentScript) {
      // Get the full URL of the script
      const scriptSrc = currentScript.src;

      // Use the URL API to parse the URL
      const url = new URL(scriptSrc);

      // Access the URLSearchParams object
      return url.searchParams;
    }
    return null;
  }

  async function getContext(uri, token) {
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

  function displayAlert(context, settingsUrl) {
    // FIXME: get the right shop
    //const shop = context?.groups[0]?.shops[0];
    const Break = '';
    try {
      context?.groups[0]?.shops.forEach(shop => {
        const localUrl = shop?.frontendUrl;
        const cloudUrl = shop?.shopStatus?.frontendUrl;

        if (localUrl !== cloudUrl) {
          myContent.id = 'psacc-alert';
          myContent.className = 'bootstrap';
          myContent.innerHTML =
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
          mainHeader.prepend(myContent);
          throw Break;
        }
      })
    } catch (Break) {
      return true;
    }
    return false;
  }

  const mainHeader = document.querySelector('#main-div .content-div, #main #content');
  const myContent = document.createElement('div');

  if (! mainHeader) return;

  const params = getParams();
  const uri = params.get('ctx');
  const token = params.get('tok');
  const settingsUrl = params.get('settings');
  const context = await getContext(uri, token);

  // FIXME: double triggered here
  if (context != null && !document.querySelector('#psacc-alert')) {
    displayAlert(context, settingsUrl);
  }
});
