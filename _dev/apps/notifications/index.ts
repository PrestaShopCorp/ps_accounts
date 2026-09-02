document.addEventListener('DOMContentLoaded', async function() {
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
        return data;
      })
      .catch(error => {
        console.error('Error:', error);
        return null;
      });
  }

  // not named `Notification`: that is a DOM global (Web Notifications API)
  type NotificationPayload = { html?: string };

  function injectNotifications(notifications: NotificationPayload[]|null, container: Element)
  {
    // getNotifications() resolves to null when the fetch fails
    if (!notifications) return;

    notifications.forEach((notif) => {
      // skip entries without markup: assigning undefined here used to render
      // the literal string "undefined"
      if (!notif?.html) return;

      const alert = document.createElement('div');
      alert.innerHTML = notif.html;
      container.prepend(alert);
    });
  }

  const container = document.querySelector('#main-div .content-div, #main #content');
  if (! container) return;

  const params = getParams();
  if (!params) return;

  injectNotifications(await getNotifications(params.get('ctx') || ''), container);
});
