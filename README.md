# Latpost Android

Native Android client for the Latpost API.

- Application ID: `com.latpost`
- API base URL: `https://v1-a1.latpost.com/`
- Features: registration, OTP activation, login, feed, single post view, profile, account deletion
- Tokens are stored in Android app-private SharedPreferences and sent in the Authorization header.
- No Resend or database secret is included in the Android app.
- Launcher icon: `mobile.png`
- Legal documents: `PRIVACY_POLICY.md`, `TERMS_OF_SERVICE.md`, `DATA_DELETION.md`

Publish the privacy policy and data deletion document at public HTTPS URLs before submitting to Google Play, and enter those URLs in Play Console. Replace `support@latpost.com` with a monitored support address if needed.

## Build

Install Android SDK Platform 35 and an Android SDK build-tools version supported by the installed Android Gradle Plugin, then run:

```bash
./gradlew assembleRelease
```

The API server must have its database, Resend API key, verified `latpost.com` sender, and PHP `pdo_mysql`/`curl` extensions configured separately.
