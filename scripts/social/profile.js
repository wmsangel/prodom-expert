/*
 * profile.js — ПОЛНОЕ оформление профиля Instagram ДомЭксперт как данные.
 * Единый источник: gen-profile.js рисует аватар + обложки «Актуального»,
 * telegram-daily.mjs --profile шлёт всё владельцу в бот для копипаста.
 * Тон честный, под органику. Фирменный стиль — графит + охра (как слайды).
 */
'use strict';

const LINK = 'https://prodom-expert.ru/?utm_source=instagram&utm_medium=social&utm_campaign=bio';

// Имя профиля (≤30) с ключевым словом для поиска IG.
const NAME = 'ДомЭксперт · Ремонт квартиры'; // 28 символов

// Варианты @username (IG: буквы/цифры/точки/подчёркивания). Занять первый свободный.
const USERNAMES = ['prodom.expert', 'domexpert.remont', 'prodom.expert.ru'];

// Bio ≤150 символов, понятно «что получишь». \n — перенос строки в IG.
const BIO = [
  'Ремонт своими руками: электрика, сантехника, отделка.',
  'Калькуляторы материалов и сметы — бесплатно.',
  'Практика, не реклама 👇',
].join('\n');

const CATEGORY = 'Образование'; // в IG-списке; альт.: «Блогер» / «Издание»
const ACCOUNT_TYPE = 'Автор (Creator)'; // альт.: Бизнес — если нужна кнопка «Эл. адрес»

const CONTACT_EMAIL = 'info@prodom-expert.ru';

// Обложки «Актуального»: id → имя (для IG) + иконка (рисует gen-profile.js).
const HIGHLIGHTS = [
  { id: 'electrika',  name: 'Электрика',  icon: 'bolt' },
  { id: 'santehnika', name: 'Сантехника', icon: 'drop' },
  { id: 'otdelka',    name: 'Отделка',    icon: 'roller' },
  { id: 'raschety',   name: 'Расчёты',    icon: 'calc' },
  { id: 'cheklist',   name: 'Чек-листы',  icon: 'clipboard' },
  { id: 'mify',       name: 'Мифы',       icon: 'bulb' },
];

// Какие 3 поста закрепить (id из posts.js) + зачем.
const PINS = [
  { id: 'p1', why: 'Разбор «сечение кабеля» — показывает пользу и экспертизу' },
  { id: 'p3', why: 'Чек-лист закупок — широкий интерес, легко сохраняют' },
  { id: 'p4', why: 'Калькулятор сметы — ведёт на инструмент сайта' },
];

// Чек-лист «что нажать в Instagram».
const CHECKLIST = [
  'Профиль → «Редактировать профиль».',
  'Фото профиля → загрузить avatar.png.',
  `Имя → вставить: ${NAME}`,
  `Имя пользователя → занять свободный из вариантов: @${USERNAMES.join(', @')}`,
  'Описание → вставить Bio (3 строки).',
  `Сайт → вставить ссылку с UTM.`,
  `Настройки → Аккаунт → тип: ${ACCOUNT_TYPE}, категория «${CATEGORY}»; показать категорию в профиле. Контакт — ${CONTACT_EMAIL}.`,
  'Актуальное → создать 6 историй, обложки hl-*.png, имена из списка; закрепить 3 поста (p1, p3, p4).',
];

module.exports = {
  LINK, NAME, USERNAMES, BIO, CATEGORY, ACCOUNT_TYPE, CONTACT_EMAIL,
  HIGHLIGHTS, PINS, CHECKLIST,
};
