#!/bin/zsh

set -u

PROJECT_DIR="${0:A:h}"
PHP_PID_FILE="$PROJECT_DIR/storage/php-server.pid"
MYSQL_PID_FILE="$PROJECT_DIR/storage/local-mysql.pid"
MYSQL_LAUNCH_LABEL="com.almawaheb.local-mysql"
PHP_LAUNCH_LABEL="com.almawaheb.local-php"

cd "$PROJECT_DIR" || exit 1

PHP_STOPPED=0
if [[ -f "$PHP_PID_FILE" ]]; then
    SERVER_PID="$(tr -cd '0-9' < "$PHP_PID_FILE")"
    SERVER_COMMAND="$(ps -p "$SERVER_PID" -o command= 2>/dev/null || true)"
    if [[ "$SERVER_COMMAND" == *"php -S 127.0.0.1:8080"* && "$SERVER_COMMAND" == *"router.php"* ]]; then
        kill "$SERVER_PID"
        PHP_STOPPED=1
    fi
    rm -f "$PHP_PID_FILE"
fi
launchctl remove "$PHP_LAUNCH_LABEL" >/dev/null 2>&1 || true

MYSQL_STOPPED=0
if [[ -f "$MYSQL_PID_FILE" ]]; then
    MYSQL_PID="$(tr -cd '0-9' < "$MYSQL_PID_FILE")"
    MYSQL_COMMAND="$(ps -p "$MYSQL_PID" -o command= 2>/dev/null || true)"
    if [[ "$MYSQL_COMMAND" == *"mysqld"* && "$MYSQL_COMMAND" == *"--datadir=$PROJECT_DIR/storage/mysql-data"* ]]; then
        if [[ -f "$PROJECT_DIR/.env" ]]; then
            set -a
            source "$PROJECT_DIR/.env"
            set +a
            # Application accounts intentionally have no global SHUTDOWN grant.
            # SIGTERM also requests a graceful stop of this verified local instance.
            MYSQL_PWD="${DB_PASS:-}" mysqladmin --no-defaults --protocol=TCP --connect-timeout=3 --host="${DB_HOST:-127.0.0.1}" --port="${DB_PORT:-3307}" --user="${DB_USER:-root}" shutdown >/dev/null 2>&1 || kill "$MYSQL_PID"
        else
            kill "$MYSQL_PID"
        fi
        MYSQL_STOPPED=1
    fi
    rm -f "$MYSQL_PID_FILE"
fi
launchctl remove "$MYSQL_LAUNCH_LABEL" >/dev/null 2>&1 || true

if (( MYSQL_STOPPED )); then
    for _ in {1..50}; do
        kill -0 "$MYSQL_PID" 2>/dev/null || break
        sleep 0.2
    done
    if kill -0 "$MYSQL_PID" 2>/dev/null; then
        printf "أُرسل طلب إيقاف MySQL؛ ما زال يحفظ بياناته. انتظر قبل إعادة التشغيل.\n"
        exit 1
    fi
fi

if (( PHP_STOPPED || MYSQL_STOPPED )); then
    printf "تم إيقاف الموقع المحلي بأمان.\n"
else
    printf "الموقع غير مشغّل من زر التشغيل.\n"
fi
