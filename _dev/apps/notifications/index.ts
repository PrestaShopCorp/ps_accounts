
function getParams() {
  const scriptFilename = 'ps_accounts/views/js/notifications.js';
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
    const url = new URL(scriptSrc);

    return url.searchParams;
  }
  return null;
}

async function getNotifications(uri: string) {
  return await fetch(uri, {
    method: 'GET'
  })
    .then(response => response.json())
    .then(data => {
      // console.log(data);
      return data;
    })
    .catch(error => {
      console.error('Error:', error);
      return null;
    });
}

function injectNotifications(notifications: [], container: Element)
{
  notifications.forEach((notif: any) => {
    const alert = document.createElement('div');
    alert.innerHTML = notif?.html;
    container.prepend(alert);
  });
}

document.addEventListener('DOMContentLoaded', async function() {

  const container = document.querySelector('#main-div .content-div, #main #content');
  if (! container) return;

  const params = getParams();
  if (!params) return;

  injectNotifications(await getNotifications(params.get('ctx') || ''), container);
});

