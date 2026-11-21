#!/usr/bin/env bash
# ロリポップへサイト一式をアップロードする
#   使い方:  bash scripts/deploy.sh            … アップロード
#            bash scripts/deploy.sh --dry-run  … 送るファイルの一覧だけ表示
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo ".env がありません。.env.example をコピーして記入してください。"; exit 1; }
set -a; . ./.env; set +a

DEPLOY_REMOTE_DIR="${DEPLOY_REMOTE_DIR%/}"; DEPLOY_REMOTE_DIR="${DEPLOY_REMOTE_DIR#/}"

for v in DEPLOY_PROTOCOL DEPLOY_HOST DEPLOY_PORT DEPLOY_USER DEPLOY_PASS DEPLOY_REMOTE_DIR; do
  [ -n "${!v:-}" ] || { echo ".env の $v が未記入です。"; exit 1; }
done

# 公開するファイル（.git / .env / scripts / README 等は送らない）
FILES=$(find index.html css html image js php -type f ! -name 'asami-01.png' | sort)

if [ "${1:-}" = "--dry-run" ]; then
  echo "送信先: ${DEPLOY_PROTOCOL}://${DEPLOY_HOST}:${DEPLOY_PORT}/${DEPLOY_REMOTE_DIR}/"
  echo "$FILES"
  exit 0
fi

case "$DEPLOY_PROTOCOL" in
  ftps) BASE="ftp://${DEPLOY_HOST}:${DEPLOY_PORT}/${DEPLOY_REMOTE_DIR}"; EXTRA="--ssl-reqd" ;;
  sftp) BASE="sftp://${DEPLOY_HOST}:${DEPLOY_PORT}/~/${DEPLOY_REMOTE_DIR}"; EXTRA="" ;;
  *) echo "DEPLOY_PROTOCOL は ftps か sftp を指定してください。"; exit 1 ;;
esac

count=0
for f in $FILES; do
  # パスワードがコマンドラインに出ないよう、認証情報は標準入力から渡す
  printf 'user = "%s:%s"\n' "$DEPLOY_USER" "$DEPLOY_PASS" | \
    curl -sS --fail --ftp-create-dirs $EXTRA -K - -T "$f" "${BASE}/${f}"
  echo "  ✓ $f"
  count=$((count+1))
done
echo "完了: ${count} ファイルをアップロードしました。"
