#!/bin/bash

set -e

# Получаем путь к корню проекта
PROJECT_ROOT=$(git rev-parse --show-toplevel)
cd "$PROJECT_ROOT"

# Проверяем, что PHPStan установлен
if [ ! -f "./vendor/bin/phpstan" ]; then
    echo "⚠️  PHPStan not found. Run 'composer install' first."
    exit 0
fi

# Получаем список измененных PHP файлов (staged + unstaged)
STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)
UNSTAGED_FILES=$(git diff --name-only --diff-filter=ACM | grep '\.php$' || true)
CHANGED_FILES=$(echo -e "$STAGED_FILES\n$UNSTAGED_FILES" | sort -u | grep -v '^$' || true)

# Если нет измененных PHP файлов, выходим
if [ -z "$CHANGED_FILES" ]; then
    echo "✓ No PHP files changed, skipping PHPStan"
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
    echo "✓ No relevant PHP files changed, skipping PHPStan"
    exit 0
fi

echo "🔍 Running PHPStan on changed files..."
echo ""

# Запускаем PHPStan только для измененных файлов
./vendor/bin/phpstan analyse \
    --error-format=table \
    --memory-limit=1G \
    --no-progress \
    $FILTERED_FILES

# Сохраняем код возврата
EXIT_CODE=$?

# Если PHPStan нашел ошибки, прерываем коммит
if [ $EXIT_CODE -ne 0 ]; then
    echo ""
    echo "❌ PHPStan found errors. Please fix them before committing."
    echo "To skip this check, use: git commit --no-verify"
    exit 1
fi

echo "✓ PHPStan check passed"
exit 0
