# Инвентаризация /vedenie-id/avto-pto/

Источник: https://isipto.ru/vedenie-id/avto-pto/ (скачан `_reference/avto-pto-source.html`)

## Head

| Элемент | Значение |
|---------|----------|
| canonical | https://isipto.ru/vedenie-id/avto-pto/ |
| title | Автоматизация ПТО в Москве |
| description | Программа для автоматизации ПТО в промышленном строительстве в Москве |
| og:image | https://isipto.ru/assets/css/img/ogimage.png |
| CSS | `/css/bootstrap.min.css`, `/assets/css/style_new.css` |
| Аналитика | Yandex Metrika `48630242` (head) |

## JSON-LD

- `@type`: WebPage + BreadcrumbList + Service (provider Organization, areaServed RU)

## JS (footer)

- jquery.min.js
- `/assets/components/minifyx/cache/scripts_6a58c300e2.min.js` (fancybox, modals, ga, fbq, gtag)
- bootstrap.min.js
- Roistat `9eeaab6aa1565e2aebc52d9060fef0f8`
- WhatsApp + Roistat message injection
- Mail.ru counter, VK pixel (noscript)
- ajaxform `default.js` + `AjaxForm.initialize` pageId `5237`
- Cookie popup (localStorage `cookieConsent`)
- af_complete → redirect `/spasibo`, fbq Lead, gtag event

## Формы (MODX AjaxForm, не inline B24 widget)

- `#form_up` — модалка «Заказать звонок», `af_action` form handler
- Footer `ajax_form` — «Не нашли, что искали?»
- reCAPTCHA token fields, honeypot `website`, `site-message` fields

## Секции main (порядок)

1. first-screen (hero + KPI row)
2. Кому подходит (4 карточки)
3. Возможности платформы (6 + CTA pto-app)
4. Почему переходить сейчас (4 + CTA strip)
5. Экономический эффект
6. Лёгкий старт (4 шага)
7. ПТО без автоматизации
8. Excel vs автоматизация
9. FAQ accordion `#faq` (6 items, `.custom-accordion`)
10. form-footer CTA

## Прочие UI

- Floating `cena_id` → /vedenie-id/ceny/
- Cookie banner `#cookie-popup`
