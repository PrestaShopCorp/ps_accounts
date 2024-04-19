# How to test an upgrade from the marketplace

## 1. Install module version n-1

Install any version **before** the latest release to trigger upgrade button.

## 2. Prestashop classes to modify locally

Return a local `ps_accounts.zip` from upload dir for example :

### Prestashop 1.6

Modify method `Tools::addonsRequest` (upload local ./upload/ps_accounts.zip) :

```php
// Bypass accounts
if ($request == 'module' && $params['id_module'] == 49648) {
    $context = stream_context_create(array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
        ),
        'http' => array(
            'method'  => 'GET',
            'timeout' => 5,
        )
    ));
    // "https://api.addons.prestashop.com?method=module&id_module=49648&version=1.6.1"
    $zip =  Tools::file_get_contents('http://localhost/upload/ps_accounts.zip', false, $context);
    return $zip;
}
```

### Prestashop 1.7+

Modify method `AddonsDataProvider::request` (upload local ./upload/ps_accounts.zip) :

```php
  if (($action == 'module_download') && $params['id_module'] == 49648) {
      $context = stream_context_create(array(
          'ssl' => array(
              'verify_peer' => false,
              'verify_peer_name' => false,
          ),
          'http' => array(
              'method'  => 'GET',
              'timeout' => 5,
          )
      ));
      // "https://api.addons.prestashop.com?method=module&id_module=49648&version=1.7.5"
      $zip =  \Tools::file_get_contents('http://localhost/upload/ps_accounts.zip', false, $context);
      return $zip;
  }
```
