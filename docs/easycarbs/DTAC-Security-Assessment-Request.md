# EasyCarbs DTAC / Security Assessment Request

> Imported reference data only. No AMS functional changes.
>
> **Security note:** Live passwords from the source pack are redacted and must be supplied out-of-band.

---

## 1. Current Live App Details

| Field | Value |
|-------|-------|
| Google Play Store link | https://play.google.com/store/apps/details?id=com.easy.carbs |
| Apple App Store link | https://apps.apple.com/us/app/easycarbs/id6747334755 |
| Android package name | `com.easy.carbs` |
| iOS bundle ID | `com.easy.carbs` |
| Current live Android version/build | 1.0.0 (build 6) |
| Current live iOS version/build | 1.1.0 (build 1) |
| Last release date (Android & iOS) | July 15, 2026 |
| Latest Android release notes | Improved application performance, stability, and user experience. |
| Latest iOS release notes | Improved application performance, stability, and user experience. |
| Same feature set on Android and iOS | Yes |

---

## 2. App Build Files for Assessment

| Field | Value |
|-------|-------|
| Android APK/AAB (v1.0.0, build 6) | Available |
| iOS IPA/TestFlight (v1.1.0, build 1) | Available |
| Production/release builds | Yes |
| Special installation notes | iOS build accessible via TestFlight invite process |

---

## 3. Store Privacy and Release Evidence

### Google Play Console

| Field | Value |
|-------|-------|
| Data Safety section | “Data Not Collected” — developer does not collect any data from this app |
| App permissions section | 22 permissions declared (location, camera-adjacent, contacts, biometric, notifications, storage, billing, etc.) |
| Privacy policy URL | https://easycarbs.com/privacy-policy/ |
| Current production release | 6 (1.0.0) — Available on Google Play, released July 15, 2026, 11:51 AM |
| Crash and ANR summary | No affected users / no crashes reported |

### Apple App Store Connect

| Field | Value |
|-------|-------|
| App Privacy answers | “No, we do not collect data from this app” |
| App permissions/capabilities summary | Background Modes, Camera, Contacts, Location (When In Use), Location Push Service Extension, Motion, Photo Library (Add Only), Push Notifications, Sign In with Apple |
| Privacy policy URL | https://easycarbs.com/privacy-policy/ |
| Current live version | 1.1.0 — Ready for Distribution |
| Crash summary | No crash feedback |

---

## 4. API and Backend Information

| Field | Value |
|-------|-------|
| API base URL | https://panel.easycarbs.com/ |
| Staging API URL | http://13.41.111.205:8000/ |
| API documentation | No public Swagger/OpenAPI. API uses Scribe PHPDoc annotations in controller source. Postman collection available on request. See also [API-Documentation.md](./API-Documentation.md). |
| API authentication method | Laravel Sanctum — Bearer Token. Tokens issued on login/registration, sent via `Authorization: Bearer <token>`. Tokens stored hashed in `personal_access_tokens`. No session cookies for API clients. |
| Admin portal URL | https://panel.easycarbs.com/admin |
| User roles and permissions | (1) Admin — full access (content, users, packages, settings, roles, payments, license keys, subscriptions, notifications). (2) Doctor/Reseller — scoped to own license keys, users, payments/subscriptions. App end-users: single authenticated role, no admin portal access. |
| API rate limits/security controls | HTTPS enforced in production via `URL::forceScheme('https')`. Sanctum required except registration, login, social login, password-reset. Rate limiting currently `Limit::none()` (unlimited). No additional WAF or IP throttling identified at application level. |

---

## 5. Test Account for Testing

### Android/iOS App

| Field | Value |
|-------|-------|
| Email | `mrdavid@gmail.com` |
| Password | `[REDACTED — supply out-of-band]` |

### Admin Panel

| Field | Value |
|-------|-------|
| Email | `admin@example.com` |
| Password | `[REDACTED — supply out-of-band]` |

---

## 6. Firebase, Analytics and Crash Reporting Summary

| Field | Value |
|-------|-------|
| Firebase project name / ID | `easycarbs` |
| Firebase services used | Cloud Messaging, Google Analytics |
| Data sent to Firebase Analytics | Backend does not send events to Firebase Analytics. Mobile SDK may log standard app usage events client-side only. No server-side analytics calls. |
| Health/carb/medical/PII data sent to analytics | No. Zero health, carb, meal, glucose, weight, or medical data sent to Firebase from the backend. FCM push payloads contain only: notification title, body text, and a notification-type identifier (e.g. `"payment_success"`, `"inactivity_reminder"`). No PII or health metrics in push payload. |
| Crashlytics or crash report summary | Crashlytics not used on backend. Server errors written to Laravel log (`storage/logs/laravel.log`). No automated crash reporting service connected to backend. |
| Push notification service summary | Firebase FCM HTTP v1 API. Backend authenticates via Google Service Account credentials file. Notifications for: payment success, new subscription, subscription expiry, admin broadcast, weekly/monthly health report reminders, inactivity reminders. Device FCM token stored in `users.fcm_id`. |

---

## 7. Third-Party SDKs and Services

| Service | Purpose | Data Sent | Environment |
|---------|---------|-----------|-------------|
| Firebase | Cloud Messaging, Analytics | FCM payloads only — title, body, type key. No PII or health data in payloads. Analytics events by mobile SDK client-side only. | Production |
| Stripe | Subscription payment processing; webhook renewal handling | User email (Stripe customer creation), subscription amount, billing period, `stripe_subscription_id`, `invoice_id`, `current_period_end`. No health data. | Production |
| Twilio | SMS OTP for phone verification and password reset | User phone number and 6-digit OTP only. No health or personal profile data. | Production |
| Apple IAP (App Store) | iOS subscription receipt validation | Encrypted `receipt_data` from app. Validated against Apple servers. No health data. | Production |
| AWS S3 | File/image storage | Food images, habit topic PDFs, user profile photos. No health metrics stored in S3. | Production |
| Google Sign-In / Facebook / Apple Sign In | Social authentication | OAuth token/code from provider. Backend verifies token, extracts email and name only. | Production |

---

## 8. App Permissions List

| Permission | Platform | Reason |
|------------|----------|--------|
| Camera | iOS | Meal photo upload |
| Photos / Storage (Add Only) | iOS / Android | Select/save food or meal image |
| Location (Fine/Coarse, When In Use) | Android / iOS | Nearby food/restaurant suggestions or location-based features |
| Contacts (Read/Write) | Android / iOS | Referral or friend/contact sharing feature |
| Notifications (Push) | Android / iOS | Reminders and app alerts |
| Internet / Network State | Android | API communication |
| Activity Recognition / Motion | Android / iOS | Step/activity tracking related to health features |
| Biometric / Fingerprint | Android | Secure app login (Face ID/fingerprint) |
| Foreground Service / Foreground Service Health | Android | Background health/activity tracking while app is in use |
| Vibrate | Android | Alert/notification feedback |
| Wake Lock | Android | Keep device awake during sync/tracking |
| Boot Completed | Android | Restart scheduled reminders after device reboot |
| Billing / Check License | Android | In-app purchase/subscription verification |
| Sign In with Apple | iOS | User authentication |
| Location Push Service Extension | iOS | Location-based push notifications |

---

## 9. Data Collection and Storage Summary

### Personal data collected

Full name (first name, last name), email address, phone number (with country code), date of birth, gender, country, profile image URL, account creation date.

### Health/carb/food/meal/medical data collected

Weight (kg/lbs, timestamped per entry), blood glucose (mmol/L, type: fasting/postprandial), blood pressure (systolic/diastolic mmHg), food diary entries (food name, carb content, meal type), step count, activity/motion data. All stored with `user_id` foreign key in MySQL.

### Storage location per data type

| Data type | Storage |
|-----------|---------|
| Personal profile data | MySQL database (AWS hosted) |
| Health metrics (weight, blood glucose, blood pressure, food logs, steps) | MySQL database |
| Profile images and food images | AWS S3 bucket (`easycarbs-prod-images`, region `eu-west-2`) |
| Authentication tokens | MySQL (`personal_access_tokens` table) |

### Data sent to backend

All health tracking data (weight, blood glucose, blood pressure, food entries, steps) is sent to and stored on the backend API. User profile data (name, email, phone, DOB, gender) sent on registration and profile updates. FCM device token sent for push notification registration. Payment/subscription data sent on purchase.

### Data sent to Firebase/analytics/third parties

No health or PII data is sent to Firebase or any third-party analytics service from the backend. Firebase receives only FCM push notification payloads (title, body, type key). Stripe receives user email and payment amount. Twilio receives phone number and OTP code only.

### User data export capability

No user data export feature currently implemented in the API. Users can view their own data through the app interface.

### User data deletion/anonymisation capability

Yes. Users can permanently delete their account via `DELETE /api/settings/delete-account` endpoint (authenticated). This calls `forceDelete()` which permanently and irreversibly removes the user record and all associated data from the database (hard delete, not soft delete). No anonymisation — full deletion.

> **Note vs API docs:** [API-Documentation.md](./API-Documentation.md) lists account deletion as `POST /api/account/delete`. Source packs disagree; retain both until runtime-validated.

### Uploaded images/files storage location

AWS S3 bucket: `easycarbs-prod-images` (`eu-west-2`). Organised under prefixes: `food/` (food images), `users/` (profile photos), `habit_topics/` (habit topic PDFs). Files are public-read via pre-configured S3 bucket policy. Uploaded via server-side using AWS SDK.

---

## 10. Security and Deployment Summary

| Field | Value |
|-------|-------|
| Android release build & upload process | Built using Flutter (`flutter build appbundle`), signed with release keystore, uploaded via Google Play Console (Production track) |
| iOS release build & upload process | Built using Xcode/Flutter, archived and signed with release provisioning profile and distribution certificate, uploaded to App Store Connect via Xcode Organiser / Transporter |
| Release builds obfuscated/minified/protected | Yes |
| Debug logs disabled in release builds | Yes |
| HTTPS/TLS used for all API communication | Yes (production) |
| App/backend secrets management | `.env` stored in AWS S3 (`s3://easycarbs-prod-images/secret-variables/.env`), not committed to Git. At deploy time, GitHub Actions downloads it from S3 using IAM credentials stored as GitHub Secrets. Firebase Service Account JSON stored on server filesystem and referenced via config. |
| Backup process summary | App deployed as Docker container on AWS EC2 (private subnet, Bastion access). Docker image on Docker Hub (`cloudpeerbits/easycarbs-prod`). Deployments via GitHub Actions `workflow_dispatch`. Database backup process not specified in codebase — assumed to rely on AWS EC2/RDS-level snapshots if configured separately. |
| Known security issues | API rate limiting disabled (`Limit::none()`). No WAF, DDoS protection, or IP-level throttling identified at application level. No known application-level security vulnerabilities identified in code review. |
| Previous penetration test / VA reports | None on file. This DTAC assessment is the first formal security review. |

---

## Appendix: Supporting Screenshots

Console evidence referenced in the sections above (filenames/descriptions as supplied; binary screenshots not imported).

### A. Evidence for Section 1 — Current Live App Details

- Google Play Console — EasyCarbs app listing, package `com.easy.carbs`, Production status, last updated Jul 14, 2026.
- Google Play Console — Releases tab, version 6 (1.0.0), released Jul 15.

### B. Evidence for Section 2 — App Build Files for Assessment

- Google Play Console — `6.aab` (1.0.0) build detail showing 22 declared permissions, features, and target SDK 35.

### C. Evidence for Section 3 — Store Privacy and Release Evidence

- Google Play Console — App Privacy: “Data Not Collected,” privacy policy URL configured.
- Google Play Console — Crashes and ANRs, no affected users in the last 28 days.
- App Store Connect — App Privacy data collection response: “No, we do not collect data from this app.”
- Xcode — Signing & Capabilities for Runner target, showing declared iOS capabilities (Camera, Contacts, Location, Motion, Photo Library, Push Notifications, Sign In with Apple).
