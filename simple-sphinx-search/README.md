# Simple Sphinx Search для WordPress

Плагин для интеграции поисковой системы Sphinx с WordPress. Разработан специально для использования с хостингом Beget, где Sphinx предустановлен и настроен.

## Возможности

- Быстрый полнотекстовый поиск по материалам сайта
- Поиск в заголовках, отрывках и содержимом публикаций
- Поддержка морфологии русского языка
- Удобный интерфейс поиска в админ-панели
- Настраиваемая позиция кнопки поиска в верхнем меню
- Возможность встраивания формы поиска через шорткод

## Требования

- WordPress 5.0 или выше
- PHP 7.4 или выше
- MySQL 5.7 или выше
- Настроенный сервер Sphinx (предустановлен на Beget)

## Установка

1. Загрузите папку `simple-sphinx-search` в директорию `/wp-content/plugins/`
2. Активируйте плагин через меню 'Плагины' в WordPress
3. Перейдите в раздел настроек плагина для конфигурации подключения к Sphinx

## Настройка

### Основные параметры

- **Sphinx Host**: Адрес сервера Sphinx (обычно localhost)
- **Sphinx Port**: Порт Sphinx (по умолчанию 9306)
- **Sphinx Index**: Имя индекса Sphinx
- **WordPress DB**: Параметры подключения к базе данных WordPress

### Настройки отображения

- Отображение в меню админ-панели
- Позиция в меню админ-панели (1-999)
- Отображение в пункте Настройки админ-панели
- Отображение кнопки в верхнем меню (админ-бар)
- Позиция кнопки в верхнем меню (1-999)
- Включение/отключение отображения в разных местах админки

## Использование

### В админ-панели

1. Нажмите на кнопку "Поиск Sphinx" в верхнем меню или в боковом меню
2. Введите поисковый запрос
3. Результаты поиска отобразятся на той же странице

### На фронтенде

Используйте шорткод для вставки формы поиска:
```php
[sphinx_search]
```

### Особенности поиска

- Поиск работает в заголовках (title), отрывках (excerpt) и тексте (content), только в опубликованных материалах. Подробнее, о настройке серверной конфигурации смотрите в [документации Sphinx](http://sphinxsearch.com/docs/);
- Обновление поиска (индексация) происходит по расписанию, как вы настроите в серверной части, в управлении индексами;
- Использование слов через пробел «большой город», эквивалентно поиску двух слов «большой+город», то есть будут найдены результаты содержащие, и «большой», и «город»;
- По-умолчанию, включена полная лемматизация русских слов. Поиск выделит лемму из введённого вами слова и найдёт все совпадения, но бывают тяжёлые случаи, такие как «абырвалг» или «главтрансдепстрой», которых нет в словаре;
- Если нужна вариативность (в тяжёлых случаях), используйте символ «или»: «|», например: «главрыба | главрыбу | главрыбы» соберёт все три комбинации слова в результате поиска;
- Для точного поиска используйте английские кавычки;
- Можно комбинировать точный поиск (в кавычках), дополнительные слова и символ «|»;
- Вывод результатов ограничен 500 ссылками.

## Поддержка

При возникновении проблем или вопросов:
1. Проверьте настройки подключения к Sphinx
2. Убедитесь, что сервер Sphinx работает
3. Проверьте права доступа к базе данных

## История версий

### 1.0.1 (12.02.2025)

- Исправлен баг с отображением сообщений о подключении к сервису Sphinx
- Добавлен шорткод [sphinx_search] для вставки формы поиска на любую страницу

### 1.0.0 (10.02.2025)

- Первый релиз
- Базовый функционал поиска
- Интеграция с админ-панелью WordPress
- Настраиваемые позиции в меню

## Лицензия

GNU General Public License v2 или более поздняя версия
