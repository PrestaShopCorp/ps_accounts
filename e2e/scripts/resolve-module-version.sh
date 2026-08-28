#!/bin/bash
# Résout la version du module ps_accounts à installer dans les TNR.
#
# Une seule ligne de version du module est supportée : on prend donc la
# dernière release publiée, sans épingler de tag. Un pin figé finit par
# désigner un zip qui ne sait plus s'enrôler quand l'infra évolue (cf. #655).
#
# Override : exporter PS_ACCOUNTS_VERSION avant l'appel (utile en local pour
# rejouer une TNR sur une version précise).

resolve_module_version() {
  local repo="PrestaShopCorp/ps_accounts"

  if [ -z "${PS_ACCOUNTS_VERSION:-}" ]; then
    # On suit la redirection de /releases/latest plutôt que d'interroger
    # l'API : pas de token requis et pas de rate limit à 60 req/h/IP.
    PS_ACCOUNTS_VERSION="$(curl -fsSL -o /dev/null -w '%{url_effective}' \
      "https://github.com/${repo}/releases/latest" | sed 's#.*/tag/##')"
  fi

  if ! echo "${PS_ACCOUNTS_VERSION}" | grep -qE '^v[0-9]+\.[0-9]+\.[0-9]+'; then
    echo "[ps_accounts] impossible de résoudre la dernière release (obtenu: '${PS_ACCOUNTS_VERSION}')" >&2
    return 1
  fi

  export PS_ACCOUNTS_VERSION
  echo "[ps_accounts] version cible : ${PS_ACCOUNTS_VERSION}"
}
