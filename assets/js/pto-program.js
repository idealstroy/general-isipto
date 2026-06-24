(function () {
  "use strict";

  function escapeHtml(s) {
    var d = document.createElement("div");
    d.textContent = s;
    return d.innerHTML;
  }

  var toggle = document.querySelector(".pto-tariffs__toggle");
  if (toggle) {
    var tButtons = toggle.querySelectorAll("button[data-billing]");
    var cards = document.querySelectorAll("[data-price-base]");
    var formatter = new Intl.NumberFormat("ru-RU");

    function formatMoney(value) {
      return formatter.format(Math.round(value)) + " ₽";
    }

    function formatMonthlyNote(value) {
      return (
        '<span class="pto-tariff-card__price-note-value">' +
        formatMoney(value) +
        '</span><span class="pto-tariff-card__price-note-period">/ мес</span>'
      );
    }

    function getPriceState(card, mode) {
      var base = Number(card.getAttribute("data-price-base")) || 0;
      if (!base) {
        return {
          value: card.getAttribute("data-price-label") || "",
          period: "",
          note: "",
        };
      }

      if (mode === "quarter") {
        var quarterTotal = Math.round(base * 3 * 0.95);
        return {
          value: formatMoney(quarterTotal),
          period: "/ 3 мес",
          note: formatMonthlyNote(quarterTotal / 3),
        };
      }

      if (mode === "halfyear") {
        var halfYearTotal = Math.round(base * 6 * 0.93);
        return {
          value: formatMoney(halfYearTotal),
          period: "/ 6 мес",
          note: formatMonthlyNote(halfYearTotal / 6),
        };
      }

      if (mode === "year") {
        var yearTotal = Math.round(base * 12 * 0.9);
        return {
          value: formatMoney(yearTotal),
          period: "/ год",
          note: formatMonthlyNote(yearTotal / 12),
        };
      }

      return {
        value: formatMoney(base),
        period: "/ мес",
        note: "",
      };
    }

    tButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var mode = btn.getAttribute("data-billing");
        tButtons.forEach(function (b) {
          b.classList.toggle("is-active", b === btn);
        });
        cards.forEach(function (card) {
          var priceEl = card.querySelector(".pto-tariff-card__price-value");
          var periodEl = card.querySelector(".pto-tariff-card__price-period");
          var noteEl = card.querySelector(".pto-tariff-card__price-note");
          if (!priceEl || !periodEl || !noteEl) return;
          var state = getPriceState(card, mode);
          priceEl.textContent = state.value;
          periodEl.textContent = state.period;
          noteEl.innerHTML = state.note;
        });
      });
    });
  }

  var funcNav = document.getElementById("pto-func-nav");
  var funcPanel = document.getElementById("pto-func-panel");
  var items = window.PTO_FUNCTIONALITY;

  if (!funcNav || !funcPanel || !items || !items.length) return;

  var navButtons = [];
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var switchDelay = reduceMotion ? 0 : 165;
  var switching = false;

  function createNavButton(item, idx) {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.setAttribute("data-id", item.id);
    btn.setAttribute("aria-controls", "pto-func-panel");
    if (item.isRoot) btn.classList.add("pto-func-nav__root");
    btn.innerHTML =
      '<span class="pto-func-nav__num">' +
      escapeHtml(item.navNum || String(idx)) +
      '</span><span class="pto-func-nav__label">' +
      escapeHtml(item.navLabel || item.title) +
      "</span>";
    return btn;
  }

  function renderPanel(id) {
    var item = items.find(function (x) {
      return x.id === id;
    });
    if (!item) return "";

    var bullets = (item.bullets || [])
      .slice(0, 3)
      .map(function (b) {
        return (
          '<li><span class="pto-func-panel__bullet" aria-hidden="true"></span><span>' +
          escapeHtml(b) +
          "</span></li>"
        );
      })
      .join("");

    var imageHtml = item.image
      ? '<div class="pto-func-panel__media"><img class="pto-func-panel__img" src="' +
        escapeHtml(item.image) +
        '" alt="' +
        escapeHtml(item.imageAlt || item.title) +
        '" loading="lazy" decoding="async" width="1337" height="614"></div>'
      : "";

    var bulletsBlock = bullets
      ? '<div class="pto-func-panel__bullets-label">Что входит в обзор</div><ul class="pto-func-panel__bullets">' +
        bullets +
        "</ul>"
      : "";

    return (
      '<div class="pto-func-panel__inner">' +
      '<h3 class="pto-func-panel__title" id="pto-func-panel-heading">' +
      escapeHtml(item.title) +
      "</h3>" +
      '<div class="pto-func-panel__body">' +
      '<div class="pto-func-panel__text">' +
      '<p class="pto-func-panel__summary">' +
      escapeHtml(item.summary) +
      "</p>" +
      bulletsBlock +
      "</div>" +
      imageHtml +
      "</div>" +
      '<div class="pto-func-panel__foot">' +
      '<p class="pto-func-panel__foot-text">Покажем на вашем примере — обычно хватает 15–20 минут.</p>' +
      '<a class="btn btn-modal krasnya_knopka" href="#form_up">Заказать демонстрацию</a>' +
      "</div>" +
      "</div>"
    );
  }

  function setActive(id) {
    navButtons.forEach(function (btn) {
      var active = btn.getAttribute("data-id") === id;
      btn.classList.toggle("is-active", active);
      if (active) {
        btn.setAttribute("aria-current", "true");
        btn.tabIndex = 0;
      } else {
        btn.removeAttribute("aria-current");
        btn.tabIndex = -1;
      }
    });
  }

  function scrollNavToButton(btn) {
    if (!btn) return;
    try {
      btn.scrollIntoView({
        inline: "center",
        block: "nearest",
        behavior: reduceMotion ? "auto" : "smooth"
      });
    } catch (err) {
      btn.scrollIntoView(true);
    }
  }

  function show(id) {
    if (switching) return;
    var activeBtn = navButtons.find(function (b) {
      return b.classList.contains("is-active");
    });
    if (activeBtn && activeBtn.getAttribute("data-id") === id) return;

    setActive(id);
    funcPanel.setAttribute("aria-busy", "true");
    funcPanel.classList.add("is-switching");

    window.setTimeout(function () {
      funcPanel.innerHTML = renderPanel(id);
      funcPanel.classList.remove("is-switching");
      funcPanel.setAttribute("aria-busy", "false");
      switching = false;
      try {
        funcPanel.focus({ preventScroll: true });
      } catch (e) {
        funcPanel.focus();
      }
    }, switchDelay);
    switching = true;
  }

  function activateByIndex(idx) {
    if (idx < 0 || idx >= navButtons.length) return;
    var btn = navButtons[idx];
    show(btn.getAttribute("data-id"));
    scrollNavToButton(btn);
    btn.focus();
  }

  items.forEach(function (item, idx) {
    var btn = createNavButton(item, idx);
    funcNav.appendChild(btn);
    navButtons.push(btn);
  });

  navButtons.forEach(function (btn, idx) {
    btn.addEventListener("click", function () {
      activateByIndex(idx);
    });
  });

  setActive(items[0].id);
  funcPanel.innerHTML = renderPanel(items[0].id);
  funcPanel.setAttribute("aria-busy", "false");

  funcNav.addEventListener("keydown", function (e) {
    var idx = navButtons.indexOf(document.activeElement);
    if (idx === -1) return;
    var horiz = window.matchMedia("(max-width: 991px)").matches;
    var next = idx;
    switch (e.key) {
      case "ArrowDown":
        e.preventDefault();
        next = Math.min(idx + 1, navButtons.length - 1);
        break;
      case "ArrowUp":
        e.preventDefault();
        next = Math.max(idx - 1, 0);
        break;
      case "ArrowRight":
        if (!horiz) return;
        e.preventDefault();
        next = Math.min(idx + 1, navButtons.length - 1);
        break;
      case "ArrowLeft":
        if (!horiz) return;
        e.preventDefault();
        next = Math.max(idx - 1, 0);
        break;
      case "Home":
        e.preventDefault();
        next = 0;
        break;
      case "End":
        e.preventDefault();
        next = navButtons.length - 1;
        break;
      default:
        return;
    }
    if (next !== idx) activateByIndex(next);
  });
})();
