# DaaS widget — always from hub

This site must load the embeddable widget **only** from:

`https://daas.alfabusiness.app/widget-v2.js?pk=<DAAS_PUBLIC_KEY>`

Do **not** restore `assets/js/daas-widget.js` or any local fork. Hub flags (`widget_queue_only`, on-demand quote billing) only apply when the script comes from the hub.

Origin returns **404** for `/assets/js/daas-widget.js` (nginx in `vhost_nginx.conf`). If Cloudflare still serves a cached 200 of the old fork, purge that URL in CF Dashboard → Caching → Configuration → Custom Purge.

Embed: `themes/wowonder/layout/extra/daas-widget.phtml`
Keys: `.env` (`DAAS_PUBLIC_KEY`, `DAAS_BILLING_EXTERNAL_ID=zuitch`, …)
Full guide: https://daas.alfabusiness.app/admin/projects/2/INTEGRATION.md
