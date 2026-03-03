#!/bin/bash

set -e

# Получаем путь к корню проекта
PROJECT_ROOT=$(git rev-parse --show-toplevel)
cd "$PROJECT_ROOT"

# Проверяем, что PHP CS Fixer установлен
if [ ! -f "./vendor/bin/php-cs-fixer" ]; then
    echo "⚠️  PHP CS Fixer not found. Run 'composer install' first."
    exit 0
fi

# Получаем список измененных PHP файлов (staged + unstaged)
STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)
UNSTAGED_FILES=$(git diff --name-only --diff-filter=ACM | grep '\.php$' || true)
CHANGED_FILES=$(echo -e "$STAGED_FILES\n$UNSTAGED_FILES" | sort -u | grep -v '^$' || true)

# Если нет измененных PHP файлов, выходим
if [ -z "$CHANGED_FILES" ]; then
    echo "✓ No PHP files changed, skipping PHP CS Fixer"
    exit 0
fi

# Фильтруем файлы, исключая vendor и другие ненужные директории
FILTERED_FILES=""
while IFS= read -r file; do
    if [[ "$file" != vendor/* && "$file" != storage/* && "$file" != bootstrap/cache/* ]]; then
        if [ -f "$file" ]; then
            FILTERED_FILES="$FILTERED_FILES $file"
        fi
    fi
done <<< "$CHANGED_FILES"

FILTERED_FILES=$(echo "$FILTERED_FILES" | xargs)

if [ -z "$FILTERED_FILES" ]; then
    echo "✓ No relevant PHP files changed, skipping PHP CS Fixer"
    exit 0
fi

echo "🔧 Running PHP CS Fixer on changed files..."
echo ""

# Устанавливаем переменную окружения для игнорирования проверки версии PHP
# и запускаем PHP CS Fixer только для измененных файлов
PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix \
    --config=config/analizator/cs-fixer/.php_cs.php \
    --allow-risky=yes \
    $FILTERED_FILES

echo ""
echo "✓ PHP CS Fixer completed"
exit 0
