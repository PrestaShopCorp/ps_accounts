
#!/bin/sh
set -eu
cd "$(dirname $0)" || exit 1

# Function to download, unzip, and install the module
install_module_from_zip() {
  local zip_name="$1"

  echo "* [ps_eventbus] downloading..."
  wget -q -O /tmp/ps_eventbus.zip "https://github.com/PrestaShopCorp/ps_eventbus/releases/download/${PS_EVENTBUS_VERSION}/${zip_name}"

  echo "* [ps_eventbus] unziping..."
  unzip -qq /tmp/ps_eventbus.zip -d /var/www/html/modules

  echo "* [ps_eventbus] installing the module..."
  cd "$PS_FOLDER"
  php -d memory_limit=-1 bin/console prestashop:module --no-interaction install "ps_eventbus"

  echo "* [ps_eventbus] installation complete."
}

# Check if the PS_EVENTBUS_VERSION is staging
if [ "$PS_EVENTBUS_VERSION" = "staging" ]; then
  # We use directly the e2e zip
  install_module_from_zip "ps_eventbus-${PS_EVENTBUS_VERSION}_e2e.zip"
else
  # Check if the PS_EVENTBUS_VERSION is less than 4
  major_version=$(echo "$PS_EVENTBUS_VERSION" | cut -d'.' -f1 | sed 's/v//')

  if [ "$major_version" -lt 4 ]; then
    install_module_from_zip "ps_eventbus-${PS_EVENTBUS_VERSION}.zip"
    # Some versions don't have the e2e zip
    echo "* [ps_eventbus] overriding the default parameters with the E2E settings..."
    # this file disappear 4 and more
    wget -O "/var/www/html/modules/ps_eventbus/config/parameters.yml" \
      "https://raw.githubusercontent.com/PrestaShopCorp/ps_eventbus/refs/tags/v3.2.3/config/parameters.yml"
  else
    install_module_from_zip "ps_eventbus-${PS_EVENTBUS_VERSION}_e2e.zip"
    echo "* [ps_eventbus] skipping parameters override (version $PS_EVENTBUS_VERSION >= 4)"
  fi
fi
