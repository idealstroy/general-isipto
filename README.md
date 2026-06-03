# Раздел «Автоматизация ПТО» — статический прототип isipto.ru

Прототип для переноса в **MODX Revolution**. Дизайн и интеграции соответствуют [isipto.ru](https://isipto.ru/).

## Страницы

| URL | Файл | Описание |
|-----|------|----------|
| `/vedenie-id/avto-pto/` | [vedenie-id/avto-pto/index.html](vedenie-id/avto-pto/index.html) | Полный перенос продакшен-страницы (разметка, JSON-LD, формы AjaxForm, Metrika, Roistat, WhatsApp) |
| `/vedenie-id/pto-program/` | [vedenie-id/pto-program/index.html](vedenie-id/pto-program/index.html) | Программа для инженеров ПТО: контент с pto-app.ru, UI как avto-pto |

## Локальный просмотр

```powershell
cd c:\Users\marke\isipto-main
npx --yes serve .
```

Откройте:

- http://localhost:3000/vedenie-id/avto-pto/
- http://localhost:3000/vedenie-id/pto-program/

`<base href="https://isipto.ru/">` подтягивает тему, изображения и общие скрипты с боевого сайта. Файлы `assets/css/page-shared.css` и `assets/js/pto-program.js` грузятся из репозитория.

Формы `ajax_form` на localhost не отправятся (нужен MODX и домен isipto.ru).

## Структура

```
assets/css/          — bootstrap (vendor), theme.css (копия style_new.css), page-shared.css
assets/js/           — pto-program.js (тарифы, навигация функционала)
partials/            — чанки для MODX (head, header, footer, modals, scripts, avto-pto-main, pto-program-main)
docs/                — pto-program-functionality.json (14 модулей)
_reference/          — эталон HTML и inventory
scripts/             — assemble-pto-program.ps1
```

## Перенос в MODX

### Ресурсы

| Ресурс | URI | Шаблон |
|--------|-----|--------|
| Автоматизация ПТО | `vedenie-id/avto-pto` | `tpl.VedenieIdPage` |
| Программа для ПТО | `vedenie-id/pto-program` | `tpl.VedenieIdPage` |

Родитель: раздел «Ведение ИД» (`vedenie-id`).

### Чанки (рекомендуемая карта)

| Чанк | Источник |
|------|----------|
| `chunk.site.head` | `partials/head.html` (+ TV для title/description на pto-program) |
| `chunk.site.header` | `partials/header.html` |
| `chunk.site.footer` | `partials/footer.html` |
| `chunk.site.modals` | `partials/modals.html` |
| `chunk.site.scripts` | `partials/scripts.html` |
| `chunk.avtoPto.main` | `partials/avto-pto-main.html` |
| `chunk.ptoProgram.main` | `partials/pto-program-main.html` |

Шаблон:

```html
[[$chunk.site.head]]
[[$chunk.site.header]]
[[*content]] <!-- или [[$chunk.avtoPto.main]] / [[$chunk.ptoProgram.main]] -->
[[$chunk.site.footer]]
[[$chunk.site.modals]]
[[$chunk.site.scripts]]
[[+ptoProgramJs]] <!-- только для pto-program -->
```

### Ассеты на сервере

1. Загрузить `assets/css/page-shared.css` → `/assets/css/page-shared.css` (или в тему).
2. Загрузить `assets/js/pto-program.js` → `/assets/js/pto-program.js`.
3. На странице pto-program подключить JSON: `window.PTO_FUNCTIONALITY = ...` из `docs/pto-program-functionality.json` или сниппета.

### Формы и интеграции (avto-pto — не менять ID)

- **AjaxForm**: `form.ajax_form`, `af_action`, `pageId` 5237 — см. `partials/scripts.html`.
- **Yandex Metrika**: 48630242.
- **Roistat**: `9eeaab6aa1565e2aebc52d9060fef0f8`.
- **WhatsApp**: `js-whatsapp-message-container`, интеграция с Roistat в `partials/scripts.html`.
- **Модалка** `#form_up`: поле `page` = URI ресурса.

На pto-program в формах указано `page` = `/vedenie-id/pto-program/`.

### TV (опционально)

- `tariff_basic_month`, `tariff_pro_month` — для правки цен без кода.
- `seo_title`, `seo_description` — для pto-program.

## Сборка pto-program

После правки `partials/pto-program-main.html` или JSON:

```powershell
powershell -File scripts/assemble-pto-program.ps1
```

## Чеклист перед выкладкой

- [ ] Canonical и JSON-LD совпадают с URI ресурса
- [ ] Все скрипты из `partials/scripts.html` на месте
- [ ] FAQ accordion на avto-pto открывается (Bootstrap 5)
- [ ] 14 пунктов функционала на pto-program переключаются
- [ ] Переключатель тарифов месяц/год обновляет цены
- [ ] Адаптив: 320 / 768 / 1280 px
- [ ] Пункт меню «Программа для ПТО» ведёт на `vedenie-id/pto-program`

## Справка

- Инвентаризация avto-pto: [_reference/inventory-avto-pto.md](_reference/inventory-avto-pto.md)
- Эталон HTML: [_reference/avto-pto-source.html](_reference/avto-pto-source.html)
