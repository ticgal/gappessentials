# GappEssentials CHANGELOG

## 3.2.1
### Changes
- Classes moved from `inc/` to `src/` with the `GlpiPlugin\Gappessentials` namespace (`Config`, `Api\Rest`)
- The configuration form is now a Twig template (`templates/config.html.twig`)
- The REST API class now extends core's `Glpi\Api\APIRest` instead of keeping a copy of its request parsing, app token check, logging and session handling
- Plugin strings use the `gappessentials` text domain (`locales/gappessentials.pot` added)
- `getDocuments` error message for unsupported types is now explicit; documents with a missing file report size 0 instead of a PHP warning
- CI: added PHPCS, PHP-CS-Fixer, PHPStan and TwigCS configuration (`.phpcs.xml`, `.php-cs-fixer.php`, `phpstan.neon`, `.twig_cs.dist.php`, `tools/phpstan/`) and the TICGAL-Dev workflows in `.github/workflows/`; licence headers unified with `tools/HEADER`
### Security
- Fix IDOR in getDocument: load the document before checking view rights
- Register the API route as stateless (boot hook) so cookie sessions are not touched
- pluginList now requires config READ right
- getDocument no longer breaks the API session when access depends on the ticket/item context
- Uploads are no longer processed (this API has no upload endpoint); removed the multipart branch
- itilCategory only returns helpdesk-visible categories to self-service users
- API responses are always JSON (the `debug` parameter no longer switches to HTML) and escape `<`/`&`
- The API session token is decrypted from any source (query string, body, header); an unencrypted session id is rejected
- getDocuments only accepts Ticket, Change and Problem
- Documented by design: the location endpoint is available to self-service users (active entity only), as the Gapp app needs it
- Control characters are stripped from the endpoint name before it is written to the API log
- Uninstall drops the configuration table; install uses a SQL schema file

## 3.2.0
### Features
- Add a new method to get documents by API

## 3.1.0
### Bugfixes
- Fix session token decryption in API header handling

## 3.0.0
### Features
- GLPI 11 Compatibility
- Add Controller

## 2.3.0
### Features
- Endpoint get item documents

## 2.2.0
### Features
- Config for request source

## 2.1.2
### Features
- Return visibility of the category
### Bugfixes
- Fix use with non-compound name 

## 2.1.1
### Bugfixes
- Fix include

## 2.1.0
### Features
- Restrict support to GLPI 10.0.3+

## 2.0.1
### Features
- GLPI 10.0.1 Compatibility #10120

## 2.0.0
### Features
- GLPI 10 Compatibility #9316

## 1.3.0
### Features
- Add a warning if Gapp Essentials is installed in the plugins folder #9374
- Migrate essentials functions to newest version #9370

## 1.2.1
### Bugfixes
- Fix: Error 500 on "pluginList" api request #4508 #9355
