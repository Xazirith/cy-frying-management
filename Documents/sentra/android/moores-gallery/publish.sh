#!/bin/bash
# Build, sign, and publish Moore's Sales to Sentra Repo

set -e

cd "$(dirname "$0")"

APP_ID="${APP_ID:-moorescustomz}"
CHANNEL="${CHANNEL:-stable}"
REPO_BASE="${REPO_BASE:-https://sentrasys.dev}"
CORE_AUTH_TOKEN="${CORE_AUTH_TOKEN:-${SENTRA_API_KEY:-}}"
TOKEN_FILE="${TOKEN_FILE:-$HOME/.sentra/repo.key}"

if [ -z "$CORE_AUTH_TOKEN" ] && [ -f "$TOKEN_FILE" ]; then
  CORE_AUTH_TOKEN="$(cat "$TOKEN_FILE" 2>/dev/null | tr -d '\n')"
fi

if [ -z "$CORE_AUTH_TOKEN" ]; then
  echo "❌ Missing auth token. Set CORE_AUTH_TOKEN/SENTRA_API_KEY or $TOKEN_FILE"
  exit 1
fi

echo "🔨 Building Android APK..."
./gradlew assembleRelease -q

# Resolve version from build.gradle if not provided
VERSION="${VERSION:-}"
if [ -z "$VERSION" ]; then
  VERSION=$(grep -m1 "versionName" app/build.gradle | sed -E 's/.*"([^"]+)".*/\1/')
fi
if [ -z "$VERSION" ]; then
  echo "❌ Could not detect VERSION"
  exit 1
fi

APK_FILE="app/build/outputs/apk/release/moores-sales-${VERSION}.apk"
if [ ! -f "$APK_FILE" ]; then
  APK_FILE_FALLBACK="app/build/outputs/apk/release/app-release.apk"
  if [ -f "$APK_FILE_FALLBACK" ]; then
    APK_FILE="$APK_FILE_FALLBACK"
  else
    echo "❌ APK not found at $APK_FILE or $APK_FILE_FALLBACK"
    exit 1
  fi
fi

echo "✅ APK built: $APK_FILE"
SIZE=$(stat -f%z "$APK_FILE" 2>/dev/null || stat -c%s "$APK_FILE")
echo "   Size: $((SIZE / 1024 / 1024))MB"

# Encode APK into JSON payload file to avoid arg limits
echo "📦 Preparing upload payload..."
UPLOAD_JSON="/tmp/sentra_repo_upload.json"
python3 - <<PY
import base64, json, pathlib, os, re
apk_path = pathlib.Path("${APK_FILE}")
data = base64.b64encode(apk_path.read_bytes()).decode()
version = "${VERSION}"
changelog_file = pathlib.Path(os.getenv("CHANGELOG_FILE", "CHANGELOG.md"))
mode = (os.getenv("CHANGELOG_MODE", "full") or "full").lower()
def extract_section(text: str, version: str):
    header_re = re.compile(r"^#{1,3}\\s+v?%s\\b" % re.escape(version), re.IGNORECASE)
    lines = text.splitlines()
    start = None
    for i, line in enumerate(lines):
        if header_re.match(line.strip()):
            start = i
            break
    if start is None:
        return None
    end = len(lines)
    for j in range(start + 1, len(lines)):
        if re.match(r"^#{1,3}\\s+v?\\d", lines[j].strip()):
            end = j
            break
    section = "\n".join(lines[start:end]).strip()
    return section if section else None

changelog = f"Moore's Sales v{version} - Sales media dashboard app"
if changelog_file.exists():
    text = changelog_file.read_text(encoding="utf-8", errors="ignore").strip()
    if text:
        if mode == "section":
            section = extract_section(text, version)
            changelog = section or text
        else:
            changelog = text
payload = {
    "version": "${VERSION}",
    "channel": "${CHANNEL}",
    "source_base64": data,
    "changelog": changelog,
}
path = pathlib.Path("${UPLOAD_JSON}")
path.write_text(json.dumps(payload))
print(str(path))
PY

# Create app in repo (if not exists)
echo "📝 Creating/checking app in repo..."
APP_EXISTS=$(curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
  "$REPO_BASE/api/sentra-repo/apps/$APP_ID")
if [ "$APP_EXISTS" != "200" ]; then
  curl -s -X POST "$REPO_BASE/api/sentra-repo/apps" \
    -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"app_id\": \"$APP_ID\",
      \"name\": \"Moore's Sales\",
      \"description\": \"Moore's Sales dashboard app for Android\",
      \"package_type\": \"firmware\",
      \"author\": \"sentra\",
      \"homepage\": \"https://sentrasys.dev\",
      \"tags\": [\"android\", \"media\", \"sales\"]
    }" > /dev/null 2>&1 || true
fi

# Upload source (APK as source)
echo "⬆️  Uploading APK to repo..."
UPLOAD_RESP=""
for attempt in 1 2 3; do
  UPLOAD_RESP=$(curl -s -X POST "$REPO_BASE/api/sentra-repo/apps/$APP_ID/source" \
    -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
    -H "Content-Type: application/json" \
    --max-time 300 \
    --data-binary @"$UPLOAD_JSON" 2>/dev/null)
  
  if echo "$UPLOAD_RESP" | grep -q "\"version_id\""; then
    break
  fi
  echo "⚠️  Upload attempt $attempt failed, retrying..."
  sleep 5
done
UPLOAD_RESP_FILE="/tmp/sentra_repo_upload_resp.json"
printf "%s" "$UPLOAD_RESP" > "$UPLOAD_RESP_FILE"

VERSION_ID=$(python3 - <<PY
import json
try:
    with open("${UPLOAD_RESP_FILE}", "r") as f:
        data = json.load(f)
    print(data.get("version_id", ""))
except Exception:
    print("")
PY
)
if [ -z "$VERSION_ID" ]; then
  VERSION_ID=$(sed -n 's/.*"version_id"[[:space:]]*:[[:space:]]*"\\([^"]*\\)".*/\\1/p' "$UPLOAD_RESP_FILE")
fi
if [ -z "$VERSION_ID" ]; then
  echo "❌ Failed to upload: $UPLOAD_RESP"
  exit 1
fi
echo "✅ Uploaded as version: $VERSION_ID"

# Approve
echo "🔏 Approving version..."
curl -s -X POST "$REPO_BASE/api/sentra-repo/versions/$VERSION_ID/approve" \
  -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"approved_by":"auto"}' > /dev/null

# Sign and publish
echo "✍️  Signing and publishing..."
curl -s -X POST "$REPO_BASE/api/sentra-repo/versions/$VERSION_ID/sign-publish" \
  -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"signed_by":"auto"}' > /dev/null

# Set channel pointer
echo "🎯 Setting stable channel..."
curl -s -X POST "$REPO_BASE/api/sentra-repo/apps/$APP_ID/channels/$CHANNEL" \
  -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"version_id":"'$VERSION_ID'","set_by":"auto"}' > /dev/null

if [ -n "$DOWNLOAD_MODE" ] || [ -n "$DOWNLOAD_TOKEN" ]; then
  echo "🔐 Updating download access..."
  curl -s -X POST "$REPO_BASE/api/sentra-repo/apps/$APP_ID/access" \
    -H "Authorization: Bearer $CORE_AUTH_TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"download_mode\": \"${DOWNLOAD_MODE:-token}\",
      \"download_token\": \"${DOWNLOAD_TOKEN:-}\"
    }" > /dev/null
fi

echo ""
echo "✅ Done! Moore's Sales v$VERSION published to stable channel"
echo "   Download: $REPO_BASE/api/sentra-repo/download/$VERSION_ID"
echo "   Latest: $REPO_BASE/api/sentra-repo/public/latest/$APP_ID/$CHANNEL"
