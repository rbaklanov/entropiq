# Отчёт о ходе реализации проекта «Entropiq — Персональный финансовый помощник»

**Дата отчёта:** 22 сентября 2026
**Основание:** [Бизнес-план проекта](https://github.com/rbaklanov/entropiq/blob/main/docs/business_plan.pdf)
**Предыдущий отчёт:** [12 апреля 2026](https://github.com/rbaklanov/entropiq/blob/main/docs/project-status-2026-04-12.md)

---

## Этап 1: Январь 2026 — Исследование и постановка задачи

**Статус: ВЫПОЛНЕН**

Проведено теоретическое исследование инфляции, индекса потребительских цен (ИПЦ), моделей накоплений и сложных процентов. Сформулирована бизнес-модель (freemium + подписка), определены две группы целевой аудитории, выполнен анализ конкурентов (Дзен-Мани, Wallet, CoinKeeper, Monefy).

**Документы:**

| Документ | Файл |
|----------|------|
| Бизнес-план проекта | [business_plan.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/business_plan.pdf) |
| Исследование инфляции и покупательной способности | [research.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/research.pdf) |

---

## Этап 2: Февраль 2026 — Проектирование

**Статус: ВЫПОЛНЕН**

Спроектированы пользовательские сценарии и экраны (дашборд, операции, цели, аналитика, профиль). Разработана финансовая модель с прогнозом доходов, расходов и точкой безубыточности. Выбран технологический стек (PHP 8.4/Laravel 12, Livewire, Tailwind CSS, PostgreSQL). Спроектирована архитектура (Actions/Services/Contracts) и структура БД (14 моделей). Составлено техническое задание, декомпозированное на 119 задач в Jira.

**Документы:**

| Документ | Файл |
|----------|------|
| UX-спецификация и макеты экранов | [ux-design.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/ux-design.pdf) |
| Финансовая модель | [financial-model.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/financial-model.pdf) |
| Технические требования | [technical-requirements.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/technical-requirements.pdf) |
| План разработки (техническое задание) | [development-plan.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/development-plan.pdf) |
| Перечень задач Jira (119 задач) | [jira-tasks.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/jira-tasks.pdf) |

---

## Этап 3: Март 2026 — Юридическое оформление и начало разработки

**Статус: ВЫПОЛНЕН (разработка и инфраструктура); юр. оформление ожидает**

Разработка backend и frontend для фаз 0–14 завершена. Боевая инфраструктура запущена: домен `entropiq.ru` зарегистрирован, приложение развёрнуто на VPS (Docker Compose production, SSL, Nginx), CI (Pint, Larastan, Pest) и деплой работают. Отдельного stage-сервера нет, мониторинг и бэкапы идут на production.

Реализовано:

- **Фундамент:** 23 миграции, 14 моделей, 14 фабрик, 8 enum-классов, 10 сервисных контрактов, 2 layout (app/guest), UI-компоненты дизайн-системы
- **Аутентификация:** SMS-авторизация без пароля (Actions, Livewire-экраны ввода телефона и кода, rate limiting). Провайдер SMS Aero подключён на проде, доставка на произвольный номер пока замокана (демо-телефон и фиксированный OTP), пока оператор не одобрит имя отправителя
- **Операции:** учёт доходов/расходов, системные категории, повторяющиеся операции, API v1, фильтрация по периодам и типам
- **Финансовые цели:** пошаговая форма создания, детальная страница с метриками, 3 сценария (оптимистичный/базовый/пессимистичный), слайдер «Что если»
- **Инфляция:** импорт данных ИПЦ Росстата, InflationService, расчёт реальной покупательной способности, персональная инфляция по структуре расходов
- **Дашборд:** четыре итерации (v1: баланс и метрики, v2: лента целей, v3: инфляция и реальный баланс, v4: карточка «Совет дня»)

Юридическое оформление (регистрация ИП) и подключение ЮKassa по-прежнему ожидают. Платежи в продукте идут через `FakePaymentService`.

**Документы:**

| Документ | Файл |
|----------|------|
| Гайд по деплою | [deployment-guide.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/deployment-guide.pdf) |
| ❌ Документ о регистрации ИП | Отсутствует |

---

## Этап 4: Апрель 2026 — Завершение MVP

**Статус: ВЫПОЛНЕН**

Все основные функциональные блоки MVP реализованы. Помимо экранов регистрации, дашборда, операций и целей, завершены:

- **Аналитика и графики (Фаза 5):** AnalyticsService, API v1, DonutChart и LineChart (ApexCharts), Livewire-страница с тремя вкладками, переключатель периодов, тесты (Pest)
- **AI-советы, каркас (Фаза 6):** Rule Engine (5 правил), AiAdviceService, команда `advice:generate`, API и frontend (список, детали, карточка «Совет дня»)
- **Freemium и подписки (Фаза 7):** SubscriptionService, middleware `subscription`, FakePaymentService, лимиты Free-плана (50 операций/месяц, 1 цель), экран подписки, PremiumLock, upsell-баннеры
- **Профиль и настройки (Фаза 8):** профиль, locale/валюта, настройки уведомлений, WeeklyDigestMail, экспорт CSV, soft delete аккаунта

---

## Этап 5: Май 2026 — Альфа-тестирование и AI-советы

**Статус: ВЫПОЛНЕН (AI); альфа-тест на реальных пользователях не оформлен**

AI-советы доведены до живого LLM. Подключён GigaChat (Saloon-коннекторы OAuth и chat completions, кэш access token, разбор JSON-ответа). При ошибке API, таймауте или отсутствии ключей генерация незаметно уходит на `FakeLlmService`, команда `advice:generate` для пользователя не падает. Драйвер выбирается через `LLM_DRIVER`.

Автотесты (Pest, Pint, Larastan level 6) и GitHub Actions CI закрывают регрессию. Внутренний проход 12a записан в [internal-test-protocol-2026-09-22.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/internal-test-protocol-2026-09-22.pdf). Отчёта об альфа-тесте на 5–10 внешних пользователях нет.

**Документы:**

| Документ | Файл |
|----------|------|
| Протокол внутреннего тестирования (фаза 12a) | [internal-test-protocol-2026-09-22.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/internal-test-protocol-2026-09-22.pdf) |

---

## Этап 6: Июнь 2026 — Запуск и первые клиенты

**Статус: ВЫПОЛНЕН (продукт и инфраструктура запуска); первые клиенты ожидают**

Закрыты блоки, без которых публичный запуск был невозможен:

- **Лендинг и публичные страницы (Фаза 9):** вёрстка лендинга, интерактивный калькулятор инфляции, политика конфиденциальности, пользовательское соглашение
- **Онбординг (Фаза 10):** middleware, 3 экрана, компонент OnboardingSlide
- **Демо-данные (Фаза 11, ENQ-125):** DemoSeeder на production. Пользователь Алексей залогинен на `entropiq.ru`, операции за 6 месяцев, цели и советы на месте.
- **Деплой и домен (Фаза 13):** зарегистрирован `entropiq.ru`, production на VPS, SSL, `docker-compose.prod.yml`, скрипт `scripts/deploy.sh`, Laravel Pulse, Horizon, бэкапы Postgres на диск VPS
- **SMS-уведомления:** интеграция SMS Aero выполнена, боевая доставка OTP пока замокана (демо-номер `79990000000`, код `1111`)

Не закрыто для коммерческого запуска:

- приём реальных платежей (ЮKassa)
- одобренное имя отправителя SMS Aero и доставка OTP на произвольный номер
- маркетинговые материалы и набор первых платящих клиентов

---

## Этап 7: Июль–Декабрь 2026 — Развитие

**Статус: В РАБОТЕ (инфраструктурный хвост, не продуктовые фазы плана)**

Запланированные фазы разработки 0–14 по коду закрыты. Дальнейшая работа идёт вокруг внешних зависимостей и эксплуатационного долга:

- боевая отправка SMS после одобрения имени в SMS Aero
- подключение ЮKassa вместо FakePaymentService
- Sentry и offsite-копия бэкапов (зафиксировано в `docs/tech-debt.md`)
- альфа-тест на реальных пользователях и юридическое оформление ИП

---

## Сводка

| Этап | Срок по плану | Статус | Документ |
|------|--------------|--------|----------|
| 1. Исследование | Январь 2026 | ✅ Выполнен | [research.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/research.pdf), [business_plan.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/business_plan.pdf) |
| 2. Проектирование | Февраль 2026 | ✅ Выполнен | [ux-design.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/ux-design.pdf), [financial-model.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/financial-model.pdf), [technical-requirements.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/technical-requirements.pdf), [development-plan.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/development-plan.pdf) |
| 3. Оформление и разработка | Март 2026 | ⚠️ Разработка и инфраструктура выполнены, юр. оформление ожидает | [deployment-guide.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/deployment-guide.pdf) |
| 4. Завершение MVP | Апрель 2026 | ✅ Выполнен | — |
| 5. Альфа-тест и AI | Май 2026 | ⚠️ AI выполнен (GigaChat), отчёт об альфа-тесте отсутствует | [internal-test-protocol-2026-09-22.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/internal-test-protocol-2026-09-22.pdf) |
| 6. Запуск | Июнь 2026 | ⚠️ Продукт и деплой выполнены, первые клиенты ожидают | — |
| 7. Развитие | Июль+ 2026 | ⚠️ В работе (SMS в бою, платежи, юр. оформление) | — |

## Реализованные фазы разработки

| Фаза | Задачи | Статус |
|------|--------|--------|
| 0. Фундамент и дизайн-система | ENQ-9 — ENQ-27 | ✅ |
| 1. Аутентификация | ENQ-28 — ENQ-36 | ✅ |
| 2. Операции (доходы/расходы) | ENQ-37 — ENQ-48 | ✅ |
| 3. Финансовые цели | ENQ-49 — ENQ-58 | ✅ |
| 4. Инфляция и покупательная способность | ENQ-59 — ENQ-67 | ✅ |
| 5. Аналитика и графики | ENQ-68 — ENQ-74 | ✅ |
| 6. AI-советы | ENQ-75 — ENQ-83, ENQ-128 | ✅ |
| 7. Freemium и подписки | ENQ-84 — ENQ-90 | ✅ |
| 8. Профиль, настройки, уведомления | ENQ-91 — ENQ-96 | ✅ |
| 9. Лендинг и публичные страницы | ENQ-97 — ENQ-101 | ✅ |
| 10. Онбординг | ENQ-102 — ENQ-105 | ✅ |
| 11. Seeder и демо-данные | ENQ-106 — ENQ-109 | ✅ |
| 12. Тестирование и стабилизация | ENQ-110 — ENQ-116 | ⚠️ 12a (ENQ-112, ENQ-113) оформлен в [internal-test-protocol-2026-09-22.pdf](https://github.com/rbaklanov/entropiq/blob/main/docs/internal-test-protocol-2026-09-22.pdf). Альфа ENQ-115 не начата |
| 13. Stage-деплой и CI/CD | ENQ-117 — ENQ-122, ENQ-127 | ✅ Домен, production, CI, Pulse, бэкапы, SMS Aero |
| 14. Подготовка к запуску и защите | ENQ-123 — ENQ-126 | ✅ ENQ-125 и ENQ-126 закрыты 22.09.2026. Презентация: [presentation.pptx](https://github.com/rbaklanov/entropiq/blob/main/docs/defense/presentation.pptx). Репетиция выполнена |

**Документы:**

| Документ | Файл |
|----------|------|
| Презентация к защите | [presentation.pptx](https://github.com/rbaklanov/entropiq/blob/main/docs/defense/presentation.pptx) |

## Недостающие документы

1. Документ о регистрации ИП (Этап 3)
2. Отчёт об альфа-тестировании (Этап 5)
3. Маркетинговые материалы для запуска (Этап 6)

## Вывод

С апреля 2026 закрыты оставшиеся продуктовые фазы плана: профиль и настройки, лендинг, онбординг, демо-данные, домен, production-деплой, SMS Aero и живые AI-советы через GigaChat. По состоянию на 22 сентября реализованы фазы 0–11, 13 и 14 из 14. Фаза 12: внутренний протокол ENQ-112/113 есть, внешняя альфа ENQ-115 нет.

ENQ-125 закрыта: прод, DemoSeeder, тестовый пользователь залогинен. ENQ-126 закрыта: [presentation.pptx](https://github.com/rbaklanov/entropiq/blob/main/docs/defense/presentation.pptx), скрипт демо, репетиция 22.09.2026. Докладчик: Бакланова Елизавета Романовна, 11Э7, Лицей ВШЭ.

Продукт можно показывать на `entropiq.ru`. Коммерческий запуск упирается не в код приложения, а во внешние условия: регистрация ИП, ЮKassa, одобрение имени отправителя SMS Aero, набор первых пользователей.
