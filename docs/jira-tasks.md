# Entropiq — Перечень задач (Jira)

**Проект:** ENQ | **Jira:** https://rbaklanov.atlassian.net/jira/software/projects/ENQ

**Всего задач:** 119 | **Оценка:** 348 часов

---

## [ENQ-8](https://rbaklanov.atlassian.net/browse/ENQ-8) — Фаза 0: Фундамент и дизайн-система

### [ENQ-9](https://rbaklanov.atlassian.net/browse/ENQ-9) Проектирование БД (миграции, модели, фабрики, enums) (19ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-10](https://rbaklanov.atlassian.net/browse/ENQ-10) | Создать миграции и модели для users, verification_codes | 3ч | ⬜ |
| | [ENQ-11](https://rbaklanov.atlassian.net/browse/ENQ-11) | Создать миграции и модели для currencies, categories, transactions, recurring_rules | 4ч | ⬜ |
| | [ENQ-12](https://rbaklanov.atlassian.net/browse/ENQ-12) | Создать миграции и модели для goals, goal_contributions | 2ч | ⬜ |
| | [ENQ-13](https://rbaklanov.atlassian.net/browse/ENQ-13) | Создать миграции и модели для cpi_values, cpi_categories, ai_advices | 3ч | ⬜ |
| | [ENQ-14](https://rbaklanov.atlassian.net/browse/ENQ-14) | Создать миграции и модели для subscriptions, payments, notification_settings | 2ч | ⬜ |
| | [ENQ-15](https://rbaklanov.atlassian.net/browse/ENQ-15) | Создать Enum-классы | 2ч | ⬜ |
| | [ENQ-16](https://rbaklanov.atlassian.net/browse/ENQ-16) | Создать фабрики для всех моделей | 3ч | ⬜ |

### [ENQ-17](https://rbaklanov.atlassian.net/browse/ENQ-17) Layout и навигация (10ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-18](https://rbaklanov.atlassian.net/browse/ENQ-18) | Установка Flux, настройка Tailwind (цвета, типографика) | 3ч | ⬜ |
| | [ENQ-19](https://rbaklanov.atlassian.net/browse/ENQ-19) | Guest layout (публичные страницы) | 2ч | ⬜ |
| | [ENQ-20](https://rbaklanov.atlassian.net/browse/ENQ-20) | App layout (mobile + desktop + responsive) | 5ч | ⬜ |

### [ENQ-21](https://rbaklanov.atlassian.net/browse/ENQ-21) Базовые UI-компоненты (дизайн-система) (7ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-22](https://rbaklanov.atlassian.net/browse/ENQ-22) | Компоненты EmptyState, PremiumLock, BottomNav, MoneyDisplay | 4ч | ⬜ |
| | [ENQ-23](https://rbaklanov.atlassian.net/browse/ENQ-23) | Компоненты PhoneInput, OtpInput, CategoryIcon | 3ч | ⬜ |

### [ENQ-24](https://rbaklanov.atlassian.net/browse/ENQ-24) Роутинг, middleware, i18n, каркас сервисов (8ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-25](https://rbaklanov.atlassian.net/browse/ENQ-25) | Структура роутов и кастомные middleware | 3ч | ⬜ |
| | [ENQ-26](https://rbaklanov.atlassian.net/browse/ENQ-26) | Языковые файлы (ru/en) | 3ч | ⬜ |
| | [ENQ-27](https://rbaklanov.atlassian.net/browse/ENQ-27) | Интерфейсы и каркасы сервисов | 2ч | ⬜ |

## [ENQ-28](https://rbaklanov.atlassian.net/browse/ENQ-28) — Фаза 1: Аутентификация

### [ENQ-29](https://rbaklanov.atlassian.net/browse/ENQ-29) Backend аутентификации (14ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-30](https://rbaklanov.atlassian.net/browse/ENQ-30) | Реализовать SmsService (заглушка) | 2ч | ⬜ |
| | [ENQ-31](https://rbaklanov.atlassian.net/browse/ENQ-31) | Реализовать Actions: SendVerificationCode, VerifyCode | 4ч | ⬜ |
| | [ENQ-32](https://rbaklanov.atlassian.net/browse/ENQ-32) | Реализовать AuthController (web + API) и Form Requests | 4ч | ⬜ |
| | [ENQ-33](https://rbaklanov.atlassian.net/browse/ENQ-33) | Тесты аутентификации (Pest) | 4ч | ⬜ |

### [ENQ-34](https://rbaklanov.atlassian.net/browse/ENQ-34) Frontend аутентификации (7ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-35](https://rbaklanov.atlassian.net/browse/ENQ-35) | Экран ввода телефона (Livewire) | 3ч | ⬜ |
| | [ENQ-36](https://rbaklanov.atlassian.net/browse/ENQ-36) | Экран ввода SMS-кода и обработка ошибок | 4ч | ⬜ |

## [ENQ-37](https://rbaklanov.atlassian.net/browse/ENQ-37) — Фаза 2: Операции (доходы/расходы)

### [ENQ-38](https://rbaklanov.atlassian.net/browse/ENQ-38) Backend операций (20ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-39](https://rbaklanov.atlassian.net/browse/ENQ-39) | CategorySeeder: 20 системных категорий | 2ч | ⬜ |
| | [ENQ-40](https://rbaklanov.atlassian.net/browse/ENQ-40) | TransactionService и TransactionsController | 6ч | ⬜ |
| | [ENQ-41](https://rbaklanov.atlassian.net/browse/ENQ-41) | API v1: TransactionsController + TransactionResource | 3ч | ⬜ |
| | [ENQ-42](https://rbaklanov.atlassian.net/browse/ENQ-42) | Повторяющиеся операции | 4ч | ⬜ |
| | [ENQ-43](https://rbaklanov.atlassian.net/browse/ENQ-43) | Тесты операций (Pest) | 5ч | ⬜ |

### [ENQ-44](https://rbaklanov.atlassian.net/browse/ENQ-44) Frontend операций (24ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-45](https://rbaklanov.atlassian.net/browse/ENQ-45) | Список операций (Livewire) | 8ч | ⬜ |
| | [ENQ-46](https://rbaklanov.atlassian.net/browse/ENQ-46) | Форма добавления/редактирования операции | 6ч | ⬜ |
| | [ENQ-47](https://rbaklanov.atlassian.net/browse/ENQ-47) | Компоненты TransactionRow, CategoryGrid | 4ч | ⬜ |
| | [ENQ-48](https://rbaklanov.atlassian.net/browse/ENQ-48) | Дашборд v1 (баланс, метрики, последние операции) | 6ч | ⬜ |

## [ENQ-49](https://rbaklanov.atlassian.net/browse/ENQ-49) — Фаза 3: Финансовые цели

### [ENQ-50](https://rbaklanov.atlassian.net/browse/ENQ-50) Backend финансовых целей (16ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-51](https://rbaklanov.atlassian.net/browse/ENQ-51) | GoalCalculationService | 6ч | ⬜ |
| | [ENQ-52](https://rbaklanov.atlassian.net/browse/ENQ-52) | GoalsController + API v1 + Form Requests | 5ч | ⬜ |
| | [ENQ-53](https://rbaklanov.atlassian.net/browse/ENQ-53) | Тесты целей и расчётов (Pest) | 5ч | ⬜ |

### [ENQ-54](https://rbaklanov.atlassian.net/browse/ENQ-54) Frontend финансовых целей (22ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-55](https://rbaklanov.atlassian.net/browse/ENQ-55) | Список целей + компоненты GoalCard, DualProgressBar | 5ч | ⬜ |
| | [ENQ-56](https://rbaklanov.atlassian.net/browse/ENQ-56) | Пошаговая форма создания цели | 6ч | ⬜ |
| | [ENQ-57](https://rbaklanov.atlassian.net/browse/ENQ-57) | Детали цели (метрики, сценарии, слайдер, график) | 8ч | ⬜ |
| | [ENQ-58](https://rbaklanov.atlassian.net/browse/ENQ-58) | Дашборд v2: лента целей | 3ч | ⬜ |

## [ENQ-59](https://rbaklanov.atlassian.net/browse/ENQ-59) — Фаза 4: Инфляция и покупательная способность

### [ENQ-60](https://rbaklanov.atlassian.net/browse/ENQ-60) Backend инфляции (20ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-61](https://rbaklanov.atlassian.net/browse/ENQ-61) | Artisan-команда cpi:import + CpiSeeder | 5ч | ⬜ |
| | [ENQ-62](https://rbaklanov.atlassian.net/browse/ENQ-62) | InflationService | 8ч | ⬜ |
| | [ENQ-63](https://rbaklanov.atlassian.net/browse/ENQ-63) | Обновление GoalCalculationService: реальные данные ИПЦ | 3ч | ⬜ |
| | [ENQ-64](https://rbaklanov.atlassian.net/browse/ENQ-64) | Тесты инфляции (Pest) | 4ч | ⬜ |

### [ENQ-65](https://rbaklanov.atlassian.net/browse/ENQ-65) Frontend инфляции (8ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-66](https://rbaklanov.atlassian.net/browse/ENQ-66) | Компоненты BalanceCard, InflationWidget | 4ч | ⬜ |
| | [ENQ-67](https://rbaklanov.atlassian.net/browse/ENQ-67) | Дашборд v3 + обновление целей | 4ч | ⬜ |

## [ENQ-68](https://rbaklanov.atlassian.net/browse/ENQ-68) — Фаза 5: Аналитика и графики

### [ENQ-69](https://rbaklanov.atlassian.net/browse/ENQ-69) Backend аналитики (10ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-70](https://rbaklanov.atlassian.net/browse/ENQ-70) | AnalyticsService + API v1 | 6ч | ⬜ |
| | [ENQ-71](https://rbaklanov.atlassian.net/browse/ENQ-71) | Тесты аналитики (Pest) | 4ч | ⬜ |

### [ENQ-72](https://rbaklanov.atlassian.net/browse/ENQ-72) Frontend аналитики (13ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-73](https://rbaklanov.atlassian.net/browse/ENQ-73) | Компоненты DonutChart, LineChart (обёртки ApexCharts) | 5ч | ⬜ |
| | [ENQ-74](https://rbaklanov.atlassian.net/browse/ENQ-74) | Три вкладки аналитики | 8ч | ⬜ |

## [ENQ-75](https://rbaklanov.atlassian.net/browse/ENQ-75) — Фаза 6: AI-советы

### [ENQ-76](https://rbaklanov.atlassian.net/browse/ENQ-76) Backend AI-советов (22ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-77](https://rbaklanov.atlassian.net/browse/ENQ-77) | Rule Engine: 5 правил анализа | 8ч | ⬜ |
| | [ENQ-78](https://rbaklanov.atlassian.net/browse/ENQ-78) | LlmService (заглушка) + AiAdviceService | 5ч | ⬜ |
| | [ENQ-79](https://rbaklanov.atlassian.net/browse/ENQ-79) | Artisan-команда advice:generate + AiAdviceController | 4ч | ⬜ |
| | [ENQ-80](https://rbaklanov.atlassian.net/browse/ENQ-80) | Тесты AI-советов (Pest) | 5ч | ⬜ |

### [ENQ-81](https://rbaklanov.atlassian.net/browse/ENQ-81) Frontend AI-советов (8ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-82](https://rbaklanov.atlassian.net/browse/ENQ-82) | Список советов + детали + AiAdviceCard | 6ч | ⬜ |
| | [ENQ-83](https://rbaklanov.atlassian.net/browse/ENQ-83) | Дашборд v4: карточка 'Совет дня' | 2ч | ⬜ |

## [ENQ-84](https://rbaklanov.atlassian.net/browse/ENQ-84) — Фаза 7: Freemium и подписки

### [ENQ-85](https://rbaklanov.atlassian.net/browse/ENQ-85) Backend подписок (14ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-86](https://rbaklanov.atlassian.net/browse/ENQ-86) | SubscriptionService + CheckSubscription middleware | 5ч | ⬜ |
| | [ENQ-87](https://rbaklanov.atlassian.net/browse/ENQ-87) | FakePaymentService + внедрение лимитов | 5ч | ⬜ |
| | [ENQ-88](https://rbaklanov.atlassian.net/browse/ENQ-88) | Тесты подписок (Pest) | 4ч | ⬜ |

### [ENQ-89](https://rbaklanov.atlassian.net/browse/ENQ-89) Frontend подписок (8ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-90](https://rbaklanov.atlassian.net/browse/ENQ-90) | Экран подписки + PremiumLock + upsell-баннеры | 8ч | ⬜ |

## [ENQ-91](https://rbaklanov.atlassian.net/browse/ENQ-91) — Фаза 8: Профиль, настройки, уведомления

### [ENQ-92](https://rbaklanov.atlassian.net/browse/ENQ-92) Backend профиля и уведомлений (8ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-93](https://rbaklanov.atlassian.net/browse/ENQ-93) | ProfileController + NotificationSettingsController + тесты | 4ч | ⬜ |
| | [ENQ-94](https://rbaklanov.atlassian.net/browse/ENQ-94) | WeeklyDigestMail + ExportService | 4ч | ⬜ |

### [ENQ-95](https://rbaklanov.atlassian.net/browse/ENQ-95) Frontend профиля и настроек (5ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-96](https://rbaklanov.atlassian.net/browse/ENQ-96) | Экран профиля и настроек | 5ч | ⬜ |

## [ENQ-97](https://rbaklanov.atlassian.net/browse/ENQ-97) — Фаза 9: Лендинг и публичные страницы

### [ENQ-98](https://rbaklanov.atlassian.net/browse/ENQ-98) Лендинг и публичные страницы (14ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-99](https://rbaklanov.atlassian.net/browse/ENQ-99) | Вёрстка лендинга | 8ч | ⬜ |
| | [ENQ-100](https://rbaklanov.atlassian.net/browse/ENQ-100) | Калькулятор инфляции (Livewire) | 4ч | ⬜ |
| | [ENQ-101](https://rbaklanov.atlassian.net/browse/ENQ-101) | Юридические страницы | 2ч | ⬜ |

## [ENQ-102](https://rbaklanov.atlassian.net/browse/ENQ-102) — Фаза 10: Онбординг

### [ENQ-103](https://rbaklanov.atlassian.net/browse/ENQ-103) Онбординг (backend + frontend) (6ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-104](https://rbaklanov.atlassian.net/browse/ENQ-104) | Backend онбординга | 2ч | ⬜ |
| | [ENQ-105](https://rbaklanov.atlassian.net/browse/ENQ-105) | Frontend онбординга (3 экрана + OnboardingSlide) | 4ч | ⬜ |

## [ENQ-106](https://rbaklanov.atlassian.net/browse/ENQ-106) — Фаза 11: Seeder и демо-данные

### [ENQ-107](https://rbaklanov.atlassian.net/browse/ENQ-107) DemoSeeder (9ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-108](https://rbaklanov.atlassian.net/browse/ENQ-108) | Демо-пользователь + операции за 6 месяцев | 5ч | ⬜ |
| | [ENQ-109](https://rbaklanov.atlassian.net/browse/ENQ-109) | Демо-цели + AI-советы + данные ИПЦ | 4ч | ⬜ |

## [ENQ-110](https://rbaklanov.atlassian.net/browse/ENQ-110) — Фаза 12: Тестирование и стабилизация

### [ENQ-111](https://rbaklanov.atlassian.net/browse/ENQ-111) Внутреннее тестирование (12a) (10ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-112](https://rbaklanov.atlassian.net/browse/ENQ-112) | Функциональное тестирование по user flows | 6ч | ⬜ |
| | [ENQ-113](https://rbaklanov.atlassian.net/browse/ENQ-113) | Тестирование на устройствах + прогон CI | 4ч | ⬜ |

### [ENQ-114](https://rbaklanov.atlassian.net/browse/ENQ-114) Исправления по альфа-тесту (12b) (20ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-115](https://rbaklanov.atlassian.net/browse/ENQ-115) | Сбор и приоритизация фидбека | 4ч | ⬜ |
| | [ENQ-116](https://rbaklanov.atlassian.net/browse/ENQ-116) | Исправление багов и улучшения UX | 16ч | ⬜ |

## [ENQ-117](https://rbaklanov.atlassian.net/browse/ENQ-117) — Фаза 13: Stage-деплой и CI/CD

### [ENQ-118](https://rbaklanov.atlassian.net/browse/ENQ-118) Инфраструктура stage (16ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-119](https://rbaklanov.atlassian.net/browse/ENQ-119) | Покупка домена + настройка VPS + SSL | 4ч | ⬜ |
| | [ENQ-120](https://rbaklanov.atlassian.net/browse/ENQ-120) | Деплой на stage (docker-compose.stage.yml) | 4ч | ⬜ |
| | [ENQ-121](https://rbaklanov.atlassian.net/browse/ENQ-121) | GitHub Actions CI/CD + Envoy | 5ч | ⬜ |
| | [ENQ-122](https://rbaklanov.atlassian.net/browse/ENQ-122) | Мониторинг + бэкапы | 3ч | ⬜ |

## [ENQ-123](https://rbaklanov.atlassian.net/browse/ENQ-123) — Фаза 14: Подготовка к запуску и защите

### [ENQ-124](https://rbaklanov.atlassian.net/browse/ENQ-124) Предзапусковая подготовка (10ч)

| # | Ключ | Задача | Часы | Статус |
|---|------|--------|------|--------|
| | [ENQ-125](https://rbaklanov.atlassian.net/browse/ENQ-125) | Финальный деплой + демо-данные | 4ч | ⬜ |
| | [ENQ-126](https://rbaklanov.atlassian.net/browse/ENQ-126) | Презентация + сценарий демонстрации + репетиция | 6ч | ⬜ |
