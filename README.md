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
| Advanced Custom Fields **Pro** | 6.0 of hoger (vereist — de catalogus en de aanvragen draaien erop) |

ACF Pro is verplicht: de options page is een Pro-functie. Zonder ACF Pro toont de plugin een
admin-melding en doet verder niets.

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

Veldgroepen worden als lokale JSON opgeslagen in `acf-json/`, zodat ze in versiebeheer staan en
via **Custom Fields → Sync available** te synchroniseren zijn.

## Structuur

```
horex-quotation-form.php     bootstrap: constants, includes, activatie
includes/
  class-horex.php            hoofdklasse: hooks, assets, config doorgeven
  cpt.php                    registratie horex_aanvraag + adminkolommen
  options-page.php           acf_add_options_page + acf-json paden
acf-json/                    lokale JSON van de veldgroepen
templates/                   shortcode-markup
assets/js, assets/css        front-end
```

## Ontwikkeling

De build loopt in kleine, afzonderlijk testbare fases:

- [x] **0 — Scaffold:** plugin-header, hoofdklasse, CPT zichtbaar, lege options page, acf-json paden
- [ ] **1 — Catalogus:** producten, framekleuren, gaas, stof, doek, maten, meethulp, e-mail
- [ ] **2 — Aanvraagvelden:** `group_horex_aanvraag` + adminkolommen
- [ ] **3 — Front-end shell:** shortcode, config doorgeven, productstap
- [ ] **4 — Stap-engine:** auto-advance, transities, terugknop, voortgangsbalk
- [ ] **5 — Matenscherm:** live preview, meethulp-popup, waarschuwing buiten bereik
- [ ] **6 — Overzicht:** samenvatting + "Nog iets toevoegen" met overname van kleur en gaas
- [ ] **7 — Gegevens + verzenden:** AJAX, sanitize-gate, opslaan, referentienummer, e-mails
- [ ] **8 — Afwerking:** foto per maat, voortgang bewaren, spambescherming

## Licentie

GPL-2.0-or-later
