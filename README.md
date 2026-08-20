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
tests/
  test-sanitize.php          controle op de sanitizer, draait zonder WordPress
templates/                   shortcode-markup
assets/js, assets/css        admin- en front-end assets
```

## Testen

```bash
php tests/test-sanitize.php
```

Draait zonder WordPress en controleert de sanitizer: welke velden overleven, of lege
rijen verdwijnen, of slugs uniek en stabiel blijven, en of de startcatalogus past op het
schema dat het formulier rendert.

## Ontwikkeling

De build loopt in kleine, afzonderlijk testbare fases:

- [x] **0 — Scaffold:** plugin-header, hoofdklasse, CPT zichtbaar, instellingenpagina met
  schema-gestuurde velden (Maten, E-mail, Branding)
- [x] **1 — Catalogus:** repeatercomponent + producten, framekleuren, gaas, stof, doek,
  meethulp, en de seeder met de bevestigde gegevens
- [ ] **2 — Aanvraagvelden:** opslag en adminweergave van aanvragen + adminkolommen
- [ ] **3 — Front-end shell:** shortcode, config doorgeven, productstap
- [ ] **4 — Stap-engine:** auto-advance, transities, terugknop, voortgangsbalk
- [ ] **5 — Matenscherm:** live preview, meethulp-popup, waarschuwing buiten bereik
- [ ] **6 — Overzicht:** samenvatting + "Nog iets toevoegen" met overname van kleur en gaas
- [ ] **7 — Gegevens + verzenden:** AJAX, sanitize-gate, opslaan, referentienummer, e-mails
- [ ] **8 — Afwerking:** foto per maat, voortgang bewaren, spambescherming

## Licentie

GPL-2.0-or-later
