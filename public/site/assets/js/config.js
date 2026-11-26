/**
 * KANGURO — Configuração do website
 * ---------------------------------------------------------------
 * Este é o ÚNICO ficheiro que precisas de editar para ligar o site
 * aos serviços reais. Campos vazios ("") ficam desativados de forma
 * segura: o site mostra "Em breve" ou um estado vazio, e nunca
 * simula um envio ou um link que não existe.
 */
window.KANGURO_CONFIG = {
  /* Domínio público final (usado em partilhas e dados estruturados). */
  siteUrl: "https://kanguro.co.mz",

  /* Cidades activas (lançamento nacional). Lista ou string; vazio = sem destaque de cidade. */
  launchCity: "Moçambique",
  launchCities: ["Beira", "Maputo", "Matola", "Nampula", "Quelimane", "Tete", "Pemba", "Chimoio", "Inhambane", "Xai-Xai"],

  /* Contactos oficiais. Vazio = não aparecem no site. */
  contact: {
    email: "",          // ex.: "ola@kanguro.co.mz"
    phone: "",          // ex.: "+258 84 000 0000"
    whatsapp: ""        // só dígitos com indicativo, ex.: "258840000000"
  },

  /* Links das lojas de aplicações. Preenche só depois da publicação. */
  apps: {
    ios: "",            // ex.: "https://apps.apple.com/app/id000000000"
    android: ""         // ex.: "https://play.google.com/store/apps/details?id=mz.kanguro"
  },

  /* Lista de espera externa (Google Forms, Typeform, etc.).
     Vazio = usa o formulário interno de lista de espera. */
  waitlistUrl: "",

  /* Envio de formulários.
     `endpoint` recebe todos os formulários via POST JSON
     (compatível com Formspree, Getform, Basin, ou um backend próprio).
     `endpoints` permite um destino diferente por formulário.
     Sem endpoint, o formulário mostra uma mensagem de erro honesta. */
  forms: {
    endpoint: "/api/v1/leads",
    endpoints: {
      contacto: "",
      parceiro: "",
      colaborador: "",
      lista: ""
    },
    timeoutMs: 15000,
    cooldownSeconds: 30
  },

  /* Redes sociais oficiais. Só as preenchidas são mostradas. */
  social: {
    instagram: "",
    facebook: "",
    tiktok: "",
    linkedin: ""
  },

  /* Analytics e marketing. Só carregam se estiverem preenchidos. */
  analytics: {
    ga4Id: "",          // ex.: "G-XXXXXXXXXX"
    metaPixelId: ""     // ex.: "000000000000000"
  },

  /* Fotografias locais reais (secção "Mais perto de ti").
     Coloca os ficheiros em assets/img/local/ e lista-os aqui.
     Vazio = é mostrada a ilustração da marca. */
  localPhotos: [
    { src: "/site/assets/img/local/beira-1.jpg", alt: "Beira", caption: "Beira" },
    { src: "/site/assets/img/local/beira-2.jpg", alt: "Beira", caption: "Beira" },
    { src: "/site/assets/img/local/beira-3.jpg", alt: "Beira", caption: "Beira" },
    { src: "/site/assets/img/local/beira-4.jpg", alt: "Beira", caption: "Beira" }
  ],

  /* Montra "Os nossos parceiros". Coloca os logótipos em
     assets/img/parceiros/ (quadrados, idealmente 256x256) e lista-os aqui.
     `url` é opcional (site, Instagram ou página do parceiro).
     Vazio = são mostrados espaços "O teu negócio aqui". */
  partners: [
    // { name: "Nome do restaurante", category: "Restaurante", logo: "assets/img/parceiros/nome.webp", url: "" }
  ]
};
