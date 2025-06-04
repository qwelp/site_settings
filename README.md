# Site Settings Module

This repository contains the custom Bitrix module `qwelp.site_settings` along with example components and templates.  

## Example Usage

A very basic page is provided at `index.php` that loads the `qwelp:site.settings` component using the default site template from `local/templates/.default`.

```
<?php
require_once __DIR__.'/local/templates/.default/header.php';
$APPLICATION->IncludeComponent('qwelp:site.settings', '', []);
require_once __DIR__.'/local/templates/.default/footer.php';
?>
```

This is meant for demonstration and can be adjusted to fit a typical Bitrix installation.
