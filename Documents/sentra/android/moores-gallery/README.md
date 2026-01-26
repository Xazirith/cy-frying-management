# Moore's Sales (Android)

Simple Compose app for browsing the Sentra media service and performing remote updates via `/api/app/version`.

## Configure
- Set `BASE_URL` and `TENANT_ID` in `app/src/main/java/com/sentra/mooresgallery/MainActivity.kt`.
- Ensure the backend serves `/api/app/version` and `download_url` points to your hosted APK (e.g., via Sentra proxy/static).
- Optional: bump `CURRENT_VERSION` and Gradle `versionName` when releasing.

## Build (local)
```
cd android/moores-gallery
./gradlew assembleDebug
```

## Release signing
- Create/upload a keystore; add to `app/signingConfigs` (not committed). Example snippet for `build.gradle`:
```
android {
  signingConfigs {
    release {
      storeFile file("../keystore.jks")
      storePassword System.getenv("KEYSTORE_PWD")
      keyAlias System.getenv("KEY_ALIAS")
      keyPassword System.getenv("KEY_PWD")
    }
  }
  buildTypes { release { signingConfig signingConfigs.release } }
}
```

## Publishing to Sentra Repo

The `publish.sh` script builds, signs, and publishes the app to the Sentra repository.

### Basic Usage
```bash
./publish.sh
```

### Options
- `VERSION=1.2.3`: Publish a specific version (otherwise auto-detected from build.gradle)
- `CHANNEL=beta`: Publish to a specific channel (default: stable)

### Examples
```bash
# Normal publish (creates new version)
./publish.sh

# Publish specific version to beta channel
VERSION=1.3.7 CHANNEL=beta ./publish.sh
```

### Version Overwriting
If a version already exists, the script will prompt you to confirm whether you want to overwrite it. This is useful for small fixes like logs or adjustments without creating a new version number.

### Auto-Detection
- Automatically detects if app exists in repo
- Only creates app if it doesn't exist (no tags required)
- Prompts for confirmation before overwriting existing versions

### Authentication
Set `CORE_AUTH_TOKEN` or `SENTRA_API_KEY` environment variable, or create `$HOME/.sentra/repo.key` with the token.

### What it does
1. Builds release APK
2. Creates/updates app in Sentra repo
3. Uploads APK as new version (or overwrites existing after confirmation)
4. Approves, signs, and publishes the version
5. Sets channel pointer to latest version
