#!/bin/zsh

set -u
unsetopt BG_NICE

PROJECT_DIR="${0:A:h}"
SITE_URL="http://127.0.0.1:8080"
PHP_PID_FILE="$PROJECT_DIR/storage/php-server.pid"
PHP_LOG_FILE="$PROJECT_DIR/storage/logs/php-server.log"
MYSQL_DATA_DIR="$PROJECT_DIR/storage/mysql-data"
MYSQL_PID_FILE="$PROJECT_DIR/storage/local-mysql.pid"
MYSQL_LOG_FILE="$PROJECT_DIR/storage/logs/local-mysql.log"
MYSQL_PORT="3307"
MYSQL_LAUNCH_LABEL="com.almawaheb.local-mysql"
PHP_LAUNCH_LABEL="com.almawaheb.local-php"

cd "$PROJECT_DIR" || exit 1

pause_on_error() {
    printf "\nحدث خطأ. اضغط Enter لإغلاق النافذة..."
    IFS= read -r _
    exit 1
}

need_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        printf "الأداة المطلوبة غير مثبتة: %s\n" "$1"
        pause_on_error
    fi
}

open_url() {
    if [[ "${MAWAHEB_NO_OPEN:-0}" != "1" ]]; then
        open "$1"
    fi
}

need_command php
need_command mysqld
need_command mysql
need_command mysqladmin
need_command curl
need_command lsof

mkdir -p "$PROJECT_DIR/storage/logs"

printf "\nشركة المواهب - تشغيل الموقع المحلي\n"
printf "===================================\n\n"

# التشغيل المحلي يستخدم MySQL معزولاً داخل المشروع حتى لا يتعارض مع XAMPP
# أو إصدارات MySQL الأخرى الموجودة على الجهاز.
if [[ ! -f "$PROJECT_DIR/.env" ]]; then
    APP_KEY_INPUT="$(php -r 'echo bin2hex(random_bytes(32));')"
    umask 077
    {
        printf "APP_ENV=local\n"
        printf "APP_URL=%s\n" "$SITE_URL"
        printf "APP_TIMEZONE=Asia/Baghdad\n"
        printf "SESSION_COOKIE_SECURE=false\n"
        printf "APP_KEY=%s\n\n" "$APP_KEY_INPUT"
        printf "DB_HOST=127.0.0.1\n"
        printf "DB_PORT=%s\n" "$MYSQL_PORT"
        printf "DB_NAME=almawaheb\n"
        printf "DB_USER=root\n"
        printf "DB_PASS=\n\n"
        printf "SMTP_HOST=\nSMTP_PORT=587\nSMTP_ENCRYPTION=tls\nSMTP_USER=\nSMTP_PASS=\n"
        printf "SMTP_FROM=info@almawaheb-co.com\nSMTP_FROM_NAME='شركة المواهب'\nSMTP_TO=info@almawaheb-co.com\n"
    } > "$PROJECT_DIR/.env"
    printf "تم إنشاء إعداد التشغيل المحلي تلقائياً.\n"
fi

set -a
source "$PROJECT_DIR/.env"
set +a

if [[ ! "${DB_PORT:-}" =~ ^[0-9]+$ ]] || [[ ! "${DB_NAME:-}" =~ ^[A-Za-z0-9_]+$ ]]; then
    printf "قيم DB_PORT أو DB_NAME في ملف .env غير صالحة.\n"
    pause_on_error
fi

mysql_ping() {
    # mysqladmin ping reports success even when authentication is rejected.
    mysql_exec --batch --skip-column-names -e "SELECT 1" >/dev/null 2>&1
}

mysql_exec() {
    MYSQL_PWD="${DB_PASS:-}" mysql --no-defaults --protocol=TCP --connect-timeout=3 --host="${DB_HOST:-127.0.0.1}" --port="${DB_PORT:-3307}" --user="${DB_USER:-root}" "$@"
}

start_isolated_mysql() {
    if [[ "${APP_ENV:-}" != "local" || "${DB_HOST:-}" != "127.0.0.1" || "${DB_PORT:-}" != "$MYSQL_PORT" ]]; then
        return 1
    fi

    if lsof -nP -iTCP:"$MYSQL_PORT" -sTCP:LISTEN >/dev/null 2>&1; then
        printf "المنفذ %s مشغول، لكن الاتصال بقاعدة الموقع لم ينجح. قد يكون الخادم خاصاً بمشروع آخر؛ راجع المنفذ والخادم وبيانات DB_* في .env. لن تُعاد تهيئة بياناتك.\n" "$MYSQL_PORT"
        return 1
    fi

    if [[ ! -d "$MYSQL_DATA_DIR/mysql" ]]; then
        # A configured application account belongs to an existing database.
        # Never create a fresh empty instance in its place.
        if [[ "${DB_USER:-}" != "root" || -n "${DB_PASS:-}" ]]; then
            printf "مجلد بيانات MySQL المحلي غير موجود. استعد النسخة الاحتياطية؛ لن تُنشأ قاعدة فارغة بدلاً منه.\n"
            return 1
        fi
        printf "تهيئة MySQL المحلي للمرة الأولى...\n"
        mkdir -p "$MYSQL_DATA_DIR"
        chmod 700 "$MYSQL_DATA_DIR"
        if ! mysqld --no-defaults --initialize-insecure --datadir="$MYSQL_DATA_DIR" >> "$MYSQL_LOG_FILE" 2>&1; then
            printf "تعذرت تهيئة MySQL المحلي. راجع السجل: %s\n" "$MYSQL_LOG_FILE"
            return 1
        fi
    fi

    printf "تشغيل MySQL المحلي المعزول...\n"
    launchctl remove "$MYSQL_LAUNCH_LABEL" >/dev/null 2>&1 || true
    launchctl submit -l "$MYSQL_LAUNCH_LABEL" -o /dev/null -e "$MYSQL_LOG_FILE" -- "$(command -v mysqld)" --no-defaults \
        --datadir="$MYSQL_DATA_DIR" \
        --bind-address=127.0.0.1 \
        --port="$MYSQL_PORT" \
        --socket="$PROJECT_DIR/storage/local-mysql.sock" \
        --pid-file="$MYSQL_PID_FILE" \
        --log-error="$MYSQL_LOG_FILE" \
        --skip-log-bin \
        --mysqlx=OFF

    for _ in {1..30}; do
        mysql_ping && return 0
        sleep 0.5
    done

    printf "تعذر تشغيل MySQL المحلي. راجع السجل: %s\n" "$MYSQL_LOG_FILE"
    return 1
}

if ! mysql_ping; then
    start_isolated_mysql || {
        printf "تعذر الاتصال بـ MySQL. راجع بيانات DB_* في ملف .env.\n"
        pause_on_error
    }
fi

if ! mysql_exec --database="$DB_NAME" -e "SELECT 1" >/dev/null 2>&1; then
    if ! mysql_exec -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" >/dev/null 2>&1; then
        printf "تعذر فتح أو إنشاء قاعدة البيانات %s.\n" "$DB_NAME"
        pause_on_error
    fi
fi

TABLE_COUNT="$(mysql_exec --database="$DB_NAME" --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name='pages'" 2>/dev/null || printf '0')"
if [[ "$TABLE_COUNT" != "1" ]]; then
    printf "تهيئة محتوى الموقع للمرة الأولى...\n"
    php "$PROJECT_DIR/scripts/install.php" --without-admin || pause_on_error
fi

if [[ -f "$PHP_PID_FILE" ]]; then
    SERVER_PID="$(tr -cd '0-9' < "$PHP_PID_FILE")"
    if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" 2>/dev/null && curl -fsS "$SITE_URL/ar/" >/dev/null 2>&1; then
        printf "الموقع يعمل بالفعل. سيتم فتحه الآن.\n"
        open_url "$SITE_URL/ar/"
        exit 0
    fi
    launchctl remove "$PHP_LAUNCH_LABEL" >/dev/null 2>&1 || true
    rm -f "$PHP_PID_FILE"
fi

if lsof -nP -iTCP:8080 -sTCP:LISTEN >/dev/null 2>&1; then
    printf "المنفذ 8080 مستخدم من برنامج آخر. أغلقه ثم حاول مجدداً.\n"
    pause_on_error
fi

launchctl remove "$PHP_LAUNCH_LABEL" >/dev/null 2>&1 || true
launchctl submit -l "$PHP_LAUNCH_LABEL" -o "$PHP_LOG_FILE" -e "$PHP_LOG_FILE" -- \
    /usr/bin/env APP_ENV=local APP_URL="$SITE_URL" SESSION_COOKIE_SECURE=false \
    "$(command -v php)" -S 127.0.0.1:8080 -t "$PROJECT_DIR/public" "$PROJECT_DIR/router.php"

for _ in {1..30}; do
    if curl -fsS "$SITE_URL/ar/" >/dev/null 2>&1; then
        SERVER_PID="$(lsof -nP -t -iTCP:8080 -sTCP:LISTEN 2>/dev/null | head -n 1)"
        [[ -n "$SERVER_PID" ]] && printf "%s" "$SERVER_PID" > "$PHP_PID_FILE"
        printf "\nتم تشغيل الموقع بنجاح: %s/ar/\n" "$SITE_URL"
        printf "لوحة الإدارة: %s/admin/login\n" "$SITE_URL"
        printf "لإيقافه استخدم ملف: إيقاف الموقع.command\n"
        open_url "$SITE_URL/ar/"
        exit 0
    fi
    sleep 0.25
done

printf "تعذر تشغيل خادم PHP. راجع السجل: %s\n" "$PHP_LOG_FILE"
pause_on_error
