# Hor-Ex Offerteaanvraag

WordPress-plugin met een stap-voor-stap offerteconfigurator voor [Hor-Ex](https://hor-ex.nl/) —
horren, gordijnen en zonwering op maat.

De klant kiest een product, een uitvoering, een kleur en een gaassoort, en voert daarna per
raam of deur de maten in (in millimeters, met ruimtenaam). Meerdere producten kunnen in één
aanvraag worden toegevoegd. De aanvraag wordt opgeslagen en per e-mail verstuurd — als
meettabel naar Hor-Ex, en als kopie naar de klant.

**Geen prijzen.** Alles wordt op maat gemaakt en Hor-Ex meet altijd ter plaatse op. Dit is een
aanvraag, geen bestelling.

## Vereisten

| | |
|---|---|
| WordPress | 6.0 of hoger |
| PHP | 7.4 of hoger |

**Geen afhankelijkheden.** Geen ACF, geen Composer, geen npm, geen buildstap — alleen PHP, JS
en CSS. De plugin werkt op elke standaard WordPress-installatie.

## Installatie

1. Plaats deze map in `wp-content/plugins/horex-quotation-form`.
2. Activeer **Hor-Ex Offerteaanvraag** in het plugin-overzicht.
3. Ga naar **Hor-Ex → Instellingen** en vul de catalogus aan.
4. Plaats de shortcode `[horex_offerte]` op de offertepagina.

De shortcode rendert alleen de configurator zelf. Header en footer komen van het thema of
de pagebuilder, dus hij past zonder aanpassing in een kolom in Breakdance.

| Attribuut | Standaard | |
|---|---|---|
| `sticky` | `ja` | Laat de balk met de voortgang meelopen bij het scrollen |
| `offset` | `0` | Aantal pixels dat vrijgehouden wordt voor een meelopende site-header |

```
[horex_offerte]
[horex_offerte sticky="nee"]
[horex_offerte offset="80"]
```

## Aanvragen

Een aanvraag wordt opgeslagen als één post meta-array bij het `horex_aanvraag` posttype.
`horex_save_submission()` is het enige schrijfpad: zowel de beheerkant als straks het
formulier op de site lopen daar doorheen, zodat ze niet uit elkaar kunnen groeien.

Daarbij gebeurt automatisch:

- maten buiten het ingestelde bereik worden **serverzijdig** gemarkeerd — wat de browser
  daarover beweert, telt niet mee;
- een referentienummer (`HX-2026-0001`) wordt eenmalig gegenereerd, per jaar oplopend, en
  nooit opnieuw als de aanvraag later nog eens opgeslagen wordt;
- de titel volgt als `{referentienummer} — {naam}`.

## Beheer

Alles wat de klant ziet is instelbaar in de WordPress-admin, zodat het assortiment zonder
ontwikkelaar aangepast kan worden:

- **Hor-Ex → Aanvragen** — binnengekomen aanvragen (`horex_aanvraag`)
- **Hor-Ex → Instellingen** — producten, uitvoeringen, kleuren, gaas, maatregels, meethulp en
  e-mailinstellingen

## Architectuur

De hele instellingenpagina wordt beschreven in één PHP-array: `horex_settings_schema()` in
`includes/settings-schema.php`. Dat schema is de enige bron van waarheid — het formulier, de
opslag, de sanitizer en straks de front-end payload worden er allemaal uit gegenereerd.

Een veld toevoegen is daardoor één wijziging op één plek, en de sanitizer kan per definitie
niet uit de pas lopen met het formulier: wat niet in het schema staat, wordt bij het opslaan
weggegooid.

Alle instellingen staan in één geautoloade optie (`horex_settings`); aanvragen staan in post
meta bij het `horex_aanvraag` posttype.

Het schema bepaalt ook hoe een veld getoond wordt. Een repeater met één tekstveld wordt een
eenvoudige lijst, een repeater met meer velden wordt een inklapbare kaart, en velden met
`'advanced' => true` — zoals de slugs — staan achter een uitklapbaar blok, omdat ze
automatisch gevuld worden en zelden aangepast hoeven te worden.

## Structuur

```
horex-quotation-form.php     bootstrap: constants, includes, activatie
includes/
  class-horex.php            hoofdklasse: hooks en assets
  cpt.php                    registratie horex_aanvraag + adminkolommen
  settings-schema.php        het schema: tabbladen, velden, standaardwaarden
  settings.php               instellingenpagina: opslag, opslaan, sanitizen
  settings-render.php        veldrenderers (tekst, kleur, afbeelding, repeater, …)
  defaults.php               de startcatalogus, eenmalig ingeladen
  submission-schema.php      wat een aanvraag bevat
  submission.php             opslag, metaboxen, adminkolommen, referentienummers
  frontend.php               shortcode, assets, catalogus richting JavaScript
  ajax.php                   horex_submit: nonce, honeypot, sleutels omzetten
  email.php                  meettabel voor Hor-Ex en de kopie voor de klant
  illustrations.php          de meegeleverde tekeningen en meetdiagrammen
tests/
  bootstrap.php              WordPress-stubs voor de tests
  test-sanitize.php          controle op de sanitizer
  test-submission.php        controle op opslaan van aanvragen
  test-render.php            controle op de veldrenderers en repeater-templates
  test-catalogue.php         controle op de seeder en de catalogusmigratie
  test-frontend.php          controle op de catalogus richting JavaScript en de stapvolgorde
  test-submit.php            controle op de verzendpoort en de e-mails
  browser/                   de configurator in een echte browser doorlopen
  run.php                    draait alle suites
bin/
  lib-po.php                 gedeelde .po/.mo-functies
  make-pot.php               haalt teksten uit de code, vult .po aan
  po2mo.php                  compileert .po naar .mo zonder gettext
languages/
  horex.pot                  vertaalsjabloon
  horex-nl_NL.po/.mo         Nederlandse vertaling
templates/                   shortcode-markup
assets/js, assets/css        admin- en front-end assets
```

## De configurator

De stapvolgorde wordt nergens vastgelegd, maar afgeleid van het gekozen product:

| Type | Stappen |
|---|---|
| `horren` | Product → Uitvoering → Kleur (frame) → Gaas → Maten |
| `gordijn` | Product → Kleur (stof) → Maten |
| `zonwering` | Product → Kleur (doek) → Maten |

Een stap waarvan de lijst leeg is, verdwijnt in plaats van dood te lopen. Op het eerste
scherm staat alleen "Stap 1", zonder totaal — dat is pas bekend zodra er een product
gekozen is, en "van 5" dat omslaat naar "van 3" leest als een storing.

## Taal

De beheerkant volgt de taal van de ingelogde gebruiker (**Gebruikers → Profiel → Taal**).
De brontekst in de code is Engels; Nederlands wordt meegeleverd als vertaling in
`languages/horex-nl_NL.mo`. Een beheerder die Engels instelt krijgt dus een Engelse
beheerkant, terwijl de site zelf Nederlands blijft.

Wat de klant ziet, verandert daar niet door. De configurator is **altijd Nederlands**,
ongeacht de taal van de site of van de ingelogde beheerder. Productnamen, meetstappen, de
waarschuwingstekst en de e-mailteksten zijn opgeslagen inhoud, en de teksten in de
configurator zelf staan als Nederlandse tekst in de code in plaats van als vertaalbare
tekst. De brief is daar stellig over: de app is Nederlands.

Vertaling aanpassen of een taal toevoegen:

```bash
# bewerk languages/horex-nl_NL.po (of kopieer horex.pot naar horex-<locale>.po)
php bin/po2mo.php
```

`bin/po2mo.php` compileert alle `.po`-bestanden in `languages/` naar `.mo`, zodat er geen
gettext-tooling nodig is.

## Testen

```bash
php tests/run.php
```

Draait zonder WordPress. Gecontroleerd wordt onder meer: welke velden de sanitizer
overleven, of lege rijen verdwijnen, of slugs uniek en stabiel blijven, of maten buiten
het bereik serverzijdig gemarkeerd worden, of referentienummers uniek zijn en niet
opnieuw gegenereerd worden bij een tweede keer opslaan, en of de repeater-templates
correcte veldnamen opleveren als er een rij bijkomt.

## Ontwikkeling

De build loopt in kleine, afzonderlijk testbare fases:

- [x] **0 — Scaffold:** plugin-header, hoofdklasse, CPT zichtbaar, instellingenpagina met
  schema-gestuurde velden (Maten, E-mail, Branding)
- [x] **1 — Catalogus:** repeatercomponent + producten, framekleuren, gaas, stof, doek,
  meethulp, en de seeder met de bevestigde gegevens
- [x] **2 — Aanvraagvelden:** opslag en adminweergave van aanvragen + adminkolommen
- [x] **3 — Front-end shell:** shortcode, config doorgeven, productstap
- [x] **4 — Stap-engine:** auto-advance, transities, terugknop, voortgangsbalk
- [x] **5 — Matenscherm:** live preview, meethulp-popup, waarschuwing buiten bereik
- [x] **6 — Overzicht:** samenvatting + "Nog iets toevoegen" met overname van kleur en gaas
- [x] **7 — Gegevens + verzenden:** AJAX, sanitize-gate, opslaan, referentienummer, e-mails
- [ ] **8 — Afwerking:** foto per maat, voortgang bewaren, spambescherming

## Licentie

GPL-2.0-or-later
