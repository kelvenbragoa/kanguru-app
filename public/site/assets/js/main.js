/* =========================================================
   KANGURO — Comportamento do website
   Sem dependências. Toda a configuração vem de config.js.
   ========================================================= */
(function () {
  "use strict";

  var cfg = window.KANGURO_CONFIG || {};
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- Analytics (só se configurado) ---------- */
  window.dataLayer = window.dataLayer || [];
  function track(event, params) {
    var payload = Object.assign({ event: event }, params || {});
    window.dataLayer.push(payload);
    if (typeof window.gtag === "function") window.gtag("event", event, params || {});
    if (typeof window.fbq === "function" && event.indexOf("form_submit_success") === 0) window.fbq("track", "Lead", params || {});
  }
  function loadAnalytics() {
    var a = cfg.analytics || {};
    if (a.ga4Id) {
      var s = document.createElement("script");
      s.async = true;
      s.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(a.ga4Id);
      document.head.appendChild(s);
      window.gtag = function () { window.dataLayer.push(arguments); };
      window.gtag("js", new Date());
      window.gtag("config", a.ga4Id, { anonymize_ip: true });
    }
    if (a.metaPixelId) {
      /* Snippet oficial do Meta Pixel, condensado */
      !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = "2.0"; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
      window.fbq("init", a.metaPixelId);
      window.fbq("track", "PageView");
    }
  }

  /* ---------- Ano dinâmico ---------- */
  $$("[data-year]").forEach(function (el) { el.textContent = new Date().getFullYear(); });

  /* ---------- Header ---------- */
  var header = $(".header");
  function onScroll() { if (header) header.classList.toggle("is-scrolled", window.scrollY > 8); }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* ---------- Menu mobile ---------- */
  var toggle = $(".menu-toggle");
  var mobileNav = $("#menu-mobile");
  function setMenu(open) {
    if (!toggle || !mobileNav) return;
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Fechar menu" : "Abrir menu");
    mobileNav.classList.toggle("is-open", open);
    if (open) mobileNav.removeAttribute("inert"); else mobileNav.setAttribute("inert", "");
    document.body.classList.toggle("nav-open", open);
    if (open) { var first = $("a", mobileNav); if (first) first.focus(); }
  }
  if (toggle) {
    toggle.addEventListener("click", function () { setMenu(toggle.getAttribute("aria-expanded") !== "true"); });
    $$("a", mobileNav).forEach(function (a) { a.addEventListener("click", function () { setMenu(false); }); });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") { setMenu(false); toggle.focus(); }
    });
    window.matchMedia("(min-width: 1200px)").addEventListener("change", function (m) { if (m.matches) setMenu(false); });
  }

  /* ---------- Link ativo no menu ---------- */
  var navLinks = $$(".nav__link");
  if ("IntersectionObserver" in window && navLinks.length) {
    var map = {};
    navLinks.forEach(function (l) { map[l.getAttribute("href").slice(1)] = l; });
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting && map[en.target.id]) {
          navLinks.forEach(function (l) { l.classList.remove("is-active"); l.removeAttribute("aria-current"); });
          map[en.target.id].classList.add("is-active");
          map[en.target.id].setAttribute("aria-current", "true");
        }
      });
    }, { rootMargin: "-45% 0px -50% 0px" });
    Object.keys(map).forEach(function (id) { var s = document.getElementById(id); if (s) spy.observe(s); });
  }

  /* ---------- Animações de entrada ---------- */
  var reveals = $$(".reveal");
  if (!reduceMotion && "IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add("is-visible"); io.unobserve(en.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
    reveals.forEach(function (el, i) { el.style.transitionDelay = (i % 4) * 60 + "ms"; io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add("is-visible"); });
  }

  /* ---------- Galeria de ecrãs ---------- */
  var screens = $("#screens");
  $$("[data-scroll]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (!screens) return;
      var step = screens.firstElementChild ? screens.firstElementChild.getBoundingClientRect().width + 22 : 260;
      screens.scrollBy({ left: step * Number(btn.dataset.scroll), behavior: reduceMotion ? "auto" : "smooth" });
    });
  });
  $$("[data-screen]").forEach(function (link) {
    link.addEventListener("click", function (e) {
      var target = document.getElementById(link.dataset.screen);
      if (!target || !screens) return;
      e.preventDefault();
      $("#kanguro-app").scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth" });
      // centrar o ecrã na galeria horizontal (mobile/tablet)
      var left = target.offsetLeft - (screens.clientWidth - target.clientWidth) / 2;
      screens.scrollTo({ left: left, behavior: reduceMotion ? "auto" : "smooth" });
      $$(".screen-fig").forEach(function (f) { f.classList.remove("is-highlight"); });
      target.classList.add("is-highlight");
      setTimeout(function () { target.classList.remove("is-highlight"); }, 2600);
      track("service_learn_more", { screen: link.dataset.screen });
    });
  });

  /* ---------- Rastreio de CTAs ---------- */
  document.addEventListener("click", function (e) {
    var el = e.target.closest("[data-track]");
    if (el) track("cta_click", { cta: el.dataset.track });
  });

  /* ---------- Aplicar configuração ---------- */
  // Cidade de arranque
  if (cfg.launchCity) {
    $$("[data-launch-city]").forEach(function (el) { el.textContent = cfg.launchCity; });
    $$("[data-launch-city-wrap]").forEach(function (el) { el.hidden = false; });
    $$("[data-city-label]").forEach(function (el) { el.textContent = cfg.launchCity + ", Moçambique"; });
  }

  // Lojas de aplicações
  var apps = cfg.apps || {};
  var anyApp = false;
  $$("[data-app]").forEach(function (btn) {
    var url = apps[btn.dataset.app];
    if (url) {
      anyApp = true;
      btn.href = url;
      btn.target = "_blank";
      btn.rel = "noopener";
      var soon = $("[data-soon]", btn); if (soon) soon.remove();
    } else {
      btn.classList.add("is-soon");
      btn.setAttribute("aria-disabled", "true");
      btn.setAttribute("aria-label", btn.textContent.replace(/\s+/g, " ").trim().replace("Em breve", "") + " — em breve. Entrar na lista de espera");
      btn.addEventListener("click", function (e) { e.preventDefault(); openWaitlist(); });
    }
  });
  if (anyApp && apps.ios && apps.android) {
    var wb = $("[data-waitlist-block]"); if (wb) wb.hidden = true;
  }

  // Redes sociais
  var social = cfg.social || {};
  var socialList = $("[data-social]");
  if (socialList) {
    var shown = 0;
    $$("[data-network]", socialList).forEach(function (li) {
      var url = social[li.dataset.network];
      if (url) { $("a", li).href = url; shown++; } else { li.remove(); }
    });
    if (!shown) { socialList.hidden = true; var se = $("[data-social-empty]"); if (se) se.hidden = false; }
  }

  // Contactos
  var contact = cfg.contact || {};
  var contactShown = 0;
  $$("[data-contact]").forEach(function (item) {
    var type = item.dataset.contact, value = contact[type];
    if (!value) return;
    var v = $("[data-contact-value]", item);
    if (type === "email") { item.href = "mailto:" + value; v.textContent = value; }
    if (type === "phone") { item.href = "tel:" + value.replace(/[^\d+]/g, ""); v.textContent = value; }
    if (type === "whatsapp") { var d = value.replace(/\D/g, ""); item.href = "https://wa.me/" + d; v.textContent = "+" + d; }
    item.hidden = false;
    contactShown++;
  });
  if (contactShown) { var ce = $("[data-contact-empty]"); if (ce) ce.hidden = true; }

  // Fotografias locais
  var photos = Array.isArray(cfg.localPhotos) ? cfg.localPhotos.filter(function (p) { return p && p.src; }) : [];
  if (photos.length) {
    var grid = $("[data-local-photos]");
    photos.slice(0, 3).forEach(function (p) {
      var fig = document.createElement("figure");
      var img = document.createElement("img");
      img.src = p.src; img.alt = p.alt || ""; img.loading = "lazy"; img.decoding = "async";
      fig.appendChild(img);
      if (p.caption) { var fc = document.createElement("figcaption"); fc.textContent = p.caption; fig.appendChild(fc); }
      grid.appendChild(fig);
    });
    grid.hidden = false;
    var ill = $("[data-local-illustration]"); if (ill) ill.hidden = true;
  }

  // Montra de parceiros
  var partners = Array.isArray(cfg.partners) ? cfg.partners.filter(function (p) { return p && p.name; }) : [];
  var partnersList = $("[data-partners]");
  if (partners.length && partnersList) {
    partnersList.textContent = "";
    partners.forEach(function (p) {
      var li = document.createElement("li");
      var card = document.createElement(p.url ? "a" : "div");
      card.className = "partner";
      if (p.url) { card.href = p.url; card.target = "_blank"; card.rel = "noopener"; }
      var logo = document.createElement("span");
      logo.className = "partner__logo";
      if (p.logo) {
        var img = document.createElement("img");
        img.src = p.logo; img.alt = ""; img.loading = "lazy"; img.decoding = "async";
        logo.appendChild(img);
      } else {
        logo.textContent = p.name.trim().charAt(0).toUpperCase();
        logo.classList.add("partner__logo--initial");
      }
      var name = document.createElement("b"); name.textContent = p.name;
      card.appendChild(logo); card.appendChild(name);
      if (p.category) { var cat = document.createElement("span"); cat.textContent = p.category; card.appendChild(cat); }
      li.appendChild(card);
      partnersList.appendChild(li);
    });
  }

  // Ligações com assunto pré-definido para o formulário de contacto
  $$("[data-subject]").forEach(function (a) {
    a.addEventListener("click", function () {
      var sel = $("#c-assunto");
      if (sel) { sel.value = a.dataset.subject; setTimeout(function () { sel.focus({ preventScroll: true }); }, 600); }
    });
  });

  /* ---------- Diálogos ---------- */
  var lastTrigger = null;
  function openDialog(name, preset) {
    var dlg = document.getElementById("dlg-" + name);
    if (!dlg) return;
    if (typeof dlg.showModal !== "function") { dlg.setAttribute("open", ""); return; }
    var form = $("form", dlg);
    resetFormView(form);
    if (preset) {
      var radio = $$('input[type="radio"]', form).filter(function (r) { return r.value === preset; })[0];
      if (radio) radio.checked = true;
    }
    form.dataset.start = String(Date.now());
    dlg.showModal();
    document.body.classList.add("dialog-open");
    track("form_open", { form: name });
  }
  function openWaitlist() {
    if (cfg.waitlistUrl) { window.open(cfg.waitlistUrl, "_blank", "noopener"); track("cta_click", { cta: "waitlist_external" }); return; }
    lastTrigger = document.activeElement;
    openDialog("lista");
  }
  $$("[data-open-form]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      lastTrigger = btn;
      if (btn.dataset.openForm === "lista") return openWaitlist();
      openDialog(btn.dataset.openForm, btn.dataset.preset);
    });
  });
  $$(".dialog").forEach(function (dlg) {
    $$("[data-close]", dlg).forEach(function (b) { b.addEventListener("click", function () { dlg.close(); }); });
    dlg.addEventListener("click", function (e) { if (e.target === dlg) dlg.close(); }); // clique no fundo
    dlg.addEventListener("close", function () {
      document.body.classList.remove("dialog-open");
      if (lastTrigger && typeof lastTrigger.focus === "function") lastTrigger.focus();
    });
  });

  /* ---------- Formulários ---------- */
  var MSG = {
    required: "Este campo é obrigatório.",
    choice: "Seleciona uma opção.",
    consent: "Precisas de aceitar para continuar.",
    email: "Indica um email válido (ex.: nome@exemplo.com).",
    phone: "Indica um número moçambicano válido (ex.: 84 123 4567).",
    min: function (n) { return "Escreve pelo menos " + n + " caracteres."; }
  };

  function isPhone(v) {
    var d = v.replace(/[\s().-]/g, "");
    return /^(\+?258)?(8[2-7]\d{7}|2\d{7,8})$/.test(d);
  }
  function isEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); }

  function setError(el, msg) {
    var group = el.type === "radio" ? el.closest(".choice-group") : null;
    var errId = group ? group.getAttribute("aria-describedby") : el.id + "-err";
    var err = errId ? document.getElementById(errId) : null;
    var target = group || el;
    if (msg) {
      target.setAttribute("aria-invalid", "true");
      if (!group && err) el.setAttribute("aria-describedby", err.id);
    } else {
      target.removeAttribute("aria-invalid");
    }
    if (err) err.textContent = msg || "";
  }

  function validateField(el) {
    var v = (el.value || "").trim();
    var msg = "";
    if (el.type === "checkbox") {
      if (el.required && !el.checked) msg = MSG.consent;
    } else if (el.type === "radio") {
      var form = el.form;
      var group = $$('input[name="' + el.name + '"]', form);
      var required = group.some(function (r) { return r.required; });
      if (required && !group.some(function (r) { return r.checked; })) msg = MSG.choice;
    } else if (el.required && !v) {
      msg = MSG.required;
    } else if (v && el.type === "email" && !isEmail(v)) {
      msg = MSG.email;
    } else if (v && el.dataset.validate === "phone" && !isPhone(v)) {
      msg = MSG.phone;
    } else if (v && el.minLength > 0 && v.length < el.minLength) {
      msg = MSG.min(el.minLength);
    }
    setError(el, msg);
    return !msg;
  }

  function fieldsOf(form) {
    var seenRadio = {};
    return $$("input, select, textarea", form).filter(function (el) {
      if (el.closest(".hp") || el.type === "hidden" || el.type === "submit") return false;
      if (el.type === "radio") { if (seenRadio[el.name]) return false; seenRadio[el.name] = true; }
      return true;
    });
  }

  function showStatus(form, type, html) {
    var box = $(".form-status", form);
    if (!box) return;
    var icon = type === "success" ? "i-check" : "i-alert";
    box.className = "form-status form-status--" + type;
    box.innerHTML = '<svg class="icon" aria-hidden="true"><use href="#' + icon + '"/></svg><span>' + html + "</span>";
    box.hidden = false;
  }
  function hideStatus(form) { var box = $(".form-status", form); if (box) box.hidden = true; }

  function resetFormView(form) {
    if (!form) return;
    var success = form.parentNode.querySelector(".form-success");
    if (success) success.remove();
    form.hidden = false;
    hideStatus(form);
  }

  function contactFallback() {
    var c = cfg.contact || {};
    if (c.email) return ' Podes também escrever-nos para <a href="mailto:' + c.email + '">' + c.email + "</a>.";
    if (c.whatsapp) return ' Podes também falar connosco por <a href="https://wa.me/' + c.whatsapp.replace(/\D/g, "") + '" target="_blank" rel="noopener">WhatsApp</a>.';
    return " Tenta novamente mais tarde.";
  }

  var SUCCESS = {
    contacto: ["Mensagem enviada!", "Obrigado pelo contacto. A equipa Kanguro responde assim que possível."],
    parceiro: ["Pedido recebido!", "Obrigado pelo interesse. A nossa equipa vai analisar e entrar em contacto."],
    colaborador: ["Interesse registado!", "Obrigado. Entraremos em contacto quando houver oportunidades na tua zona."],
    lista: ["Estás na lista!", "Obrigado. Vamos avisar-te quando a Kanguro estiver disponível."]
  };

  function showSuccess(form, type) {
    var s = SUCCESS[type] || SUCCESS.contacto;
    var wrap = document.createElement("div");
    wrap.className = "form-success";
    wrap.setAttribute("role", "status");
    wrap.setAttribute("tabindex", "-1");
    wrap.innerHTML = '<span class="form-success__ic"><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg></span>' +
      "<h3></h3><p></p>" +
      '<button type="button" class="btn btn--outline btn--sm">Enviar outro</button>';
    $("h3", wrap).textContent = s[0];
    $("p", wrap).textContent = s[1];
    $("button", wrap).addEventListener("click", function () {
      resetFormView(form);
      form.dataset.start = String(Date.now());
      var first = fieldsOf(form)[0]; if (first) first.focus();
    });
    form.hidden = true;
    form.parentNode.insertBefore(wrap, form.nextSibling);
    wrap.focus();
  }

  function getCooldownLeft(type) {
    try {
      var last = Number(localStorage.getItem("kg_last_submit_" + type) || 0);
      var left = (cfg.forms && cfg.forms.cooldownSeconds || 30) * 1000 - (Date.now() - last);
      return left > 0 ? Math.ceil(left / 1000) : 0;
    } catch (e) { return 0; }
  }
  function setCooldown(type) { try { localStorage.setItem("kg_last_submit_" + type, String(Date.now())); } catch (e) { /* ignorar */ } }

  function collect(form, type) {
    var data = { formulario: type };
    $$("input, select, textarea", form).forEach(function (el) {
      if (!el.name || el.closest(".hp")) return;
      if (el.type === "radio") { if (el.checked) data[el.name] = el.value; return; }
      if (el.type === "checkbox") { data[el.name] = el.checked ? "sim" : "não"; return; }
      data[el.name] = el.value.trim();
    });
    data.pagina = location.href.split("#")[0];
    data.enviado_em = new Date().toISOString();
    data._subject = "Kanguro — novo formulário: " + type;
    return data;
  }

  function handleSubmit(e) {
    e.preventDefault();
    var form = e.currentTarget;
    var type = form.dataset.form;
    var btn = $('button[type="submit"]', form);
    hideStatus(form);

    // 1. Validação
    var fields = fieldsOf(form);
    var firstInvalid = null;
    fields.forEach(function (el) { if (!validateField(el) && !firstInvalid) firstInvalid = el; });
    if (firstInvalid) {
      showStatus(form, "error", "Verifica os campos assinalados.");
      firstInvalid.focus();
      return;
    }

    // 2. Proteção anti-spam (honeypot + tempo mínimo + intervalo entre envios)
    var hp = $(".hp input", form);
    if (hp && hp.value) { form.reset(); return; }
    var started = Number(form.dataset.start || 0);
    if (started && Date.now() - started < 2500) {
      showStatus(form, "error", "Aguarda um momento e tenta novamente.");
      return;
    }
    var wait = getCooldownLeft(type);
    if (wait) {
      showStatus(form, "error", "Já recebemos um envio deste formulário. Aguarda " + wait + " s antes de enviar novamente.");
      return;
    }

    // 3. Destino configurado?
    var forms = cfg.forms || {};
    var endpoint = (forms.endpoints && forms.endpoints[type]) || forms.endpoint;
    if (!endpoint) {
      showStatus(form, "error", "O envio de formulários ainda não está ativo, por isso a tua mensagem <strong>não foi enviada</strong>." + contactFallback());
      track("form_submit_unconfigured", { form: type });
      return;
    }

    // 4. Envio real
    var controller = "AbortController" in window ? new AbortController() : null;
    var timer = controller ? setTimeout(function () { controller.abort(); }, forms.timeoutMs || 15000) : null;
    btn.setAttribute("aria-busy", "true");
    btn.disabled = true;

    fetch(endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify(collect(form, type)),
      signal: controller ? controller.signal : undefined
    }).then(function (res) {
      if (!res.ok) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          var detail = body && body.errors && body.errors.length ? body.errors.map(function (x) { return x.message; }).join(" ") : "";
          throw new Error(detail || "HTTP " + res.status);
        });
      }
      setCooldown(type);
      form.reset();
      showSuccess(form, type);
      track("form_submit_success", { form: type });
    }).catch(function (err) {
      var aborted = err && err.name === "AbortError";
      showStatus(form, "error", (aborted ? "O servidor demorou demasiado a responder." : "Não foi possível enviar neste momento.") + contactFallback());
      track("form_submit_error", { form: type, reason: aborted ? "timeout" : String(err && err.message || "erro") });
    }).then(function () {
      if (timer) clearTimeout(timer);
      btn.removeAttribute("aria-busy");
      btn.disabled = false;
    });
  }

  $$("form[data-form]").forEach(function (form) {
    form.dataset.start = String(Date.now());
    form.addEventListener("submit", handleSubmit);
    // validação ao sair do campo e correção em tempo real
    fieldsOf(form).forEach(function (el) {
      el.addEventListener("blur", function () { if (el.value || el.getAttribute("aria-invalid")) validateField(el); });
    });
    form.addEventListener("input", function (e) {
      var el = e.target;
      if (el.getAttribute("aria-invalid") === "true" || (el.type === "radio" && el.closest('[aria-invalid="true"]'))) validateField(el);
    });
    form.addEventListener("change", function (e) {
      var el = e.target;
      if (el.type === "radio" || el.type === "checkbox" || el.tagName === "SELECT") {
        if (el.getAttribute("aria-invalid") || el.closest('[aria-invalid="true"]')) validateField(el);
      }
    });
  });

  loadAnalytics();
})();
