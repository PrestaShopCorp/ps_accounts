<link href="{$pathVendor|escape:'htmlall':'UTF-8'}" rel=preload as=script>
<link href="{$pathApp|escape:'htmlall':'UTF-8'}" rel=preload as=script>

<div id="app"></div>

<script src="{$pathVendor|escape:'htmlall':'UTF-8'}"></script>
<script src="{$pathApp|escape:'htmlall':'UTF-8'}"></script>

<style>
  /** Hide native multistore module activation panel, because of visual regressions on non-bootstrap content */
  #content.nobootstrap div.bootstrap.panel {
    display: none;
  }
</style>
