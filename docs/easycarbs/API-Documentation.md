# EasyCarb Backend API Documentation

> Imported reference data only. No AMS functional changes.

## Global behaviour

### Base path

- All mobile API routes are under `/api`.

### Authentication and middleware

- Public guest routes: `guest`
- Authenticated mobile routes: `auth:sanctum` + `ensure.single.device`
- Subscription-gated routes: additional subscription middleware

### Important caveats

- API throttling is disabled in `app/Providers/RouteServiceProvider.php` via `Limit::none()`.
- `GET /api/test` is a public diagnostic route that runs `storage:link`; this is not a normal product endpoint and should not be exposed in production.

---

## Auth and registration

### POST /api/login

- **Auth:** guest
- **Controller:** `AuthenticationController@store`
- **Request:**
  - `email` required email
  - `password` required string
- **Response:**
  - `token`
  - `phone_verified_at`
  - `message`
- **Notes:**
  - Creates a new Sanctum token and rotates out the prior `current_access_token_id`.
  - Login attempts are rate-limited inside `LoginRequest`.

### POST /api/register

- **Auth:** guest
- **Controller:** `RegisteredUserController@store`
- **Request:**
  - Standard signup path:
    - `first_name`
    - `last_name` nullable
    - `email`
    - `country_code`
    - `phone`
    - `password`
    - `password_confirmation`
    - `referral_code` nullable
  - OAuth path:
    - `oauth_id`
    - `driver`
    - `email`
    - `first_name`
    - `last_name` nullable
    - `userIdentifier` for Apple re-login support
- **Response:**
  - `token`
  - `message`
  - `is_phone_screen_shown`
- **Notes:**
  - Creates or updates a user record, issues a Sanctum token, and may send an OTP.
- **Example request (Postman):**

```json
{
  "name": "Sujit Sarkar",
  "email": "test@example.com",
  "country_code": "BD",
  "phone": "01749699156",
  "password": "12345678",
  "password_confirmation": "12345678"
}
```

- **Example OAuth request (Postman):**

```json
{
  "name": "Sujit Sarkar",
  "email": "test@example.com",
  "oauth_id": "1441664444",
  "driver": "google"
}
```

- **Sample response note:** the local Postman collection does not include captured response bodies for this request; response shape above is derived from code rather than a saved Postman example.

### POST /api/register/phone

- **Auth:** Sanctum + single-device
- **Controller:** `RegisteredUserController@storePhonenumber`
- **Request:** `country_code`, `phone`
- **Response:** `message`

### POST /api/resend

- **Auth:** Sanctum + single-device
- **Controller:** `RegisteredUserController@resend`
- **Request:** `type` optional: `phone` or `email`
- **Response:** `message`

### POST /api/verify

- **Auth:** Sanctum + single-device
- **Controller:** `RegisteredUserController@verify`
- **Request:** `type` optional: `phone` or `email`, `otp_code`
- **Response:** `message`
- **Notes:** Verifies OTP and updates verification timestamps.

### POST /api/set/token

- **Auth:** Sanctum + single-device
- **Controller:** `AuthenticationController@setToken`
- **Request:** `fcm_id`
- **Response:** `message`

### POST /api/logout

- **Auth:** Sanctum + single-device
- **Controller:** `AuthenticationController@logout`
- **Response:** `message`
- **Notes:** Deletes current token and clears `fcm_id` and `current_access_token_id`.

### GET /api/user

- **Auth:** Sanctum + single-device
- **Controller:** `AuthenticationController@user`
- **Response from UserResource:**
  - Identity/profile: `id`, `full_name`, `first_name`, `last_name`, `username`, `email`, `country_code`, `phone`
  - Verification: `email_verified_at`, `phone_verified_at`
  - Demographic/health profile: `gender`, `date_of_birth`, `diabetes`, `height`, `height_measurement`, `weight`, `weight_measurement`, `ethnicity`, `qualification`, `dietary`, `city`, `occupation`, `avatar`, `avatar_id`
  - App/subscription/settings: `total_point`, `subscription.name`, `subscription.started_at`, `subscription.expire_at`, `enable_notification`, `enable_vibration`, `enable_sound`, `stripe`, `app_name`

---

## Password reset

### POST /api/forgot-password

- **Auth:** guest
- **Controller:** `PasswordResetController@store`
- **Request:** `country_code`, `phone`
- **Response:** `message`

### POST /api/forgot-password/resend

- **Auth:** guest
- **Controller:** `PasswordResetController@resend`
- **Request:** `country_code`, `phone`
- **Response:** `message`

### POST /api/forgot-password/verify

- **Auth:** guest
- **Controller:** `PasswordResetController@verify`
- **Request:** `country_code`, `phone`, `otp_code`
- **Response:** `message`

### POST /api/forgot-password/reset

- **Auth:** guest
- **Controller:** `PasswordResetController@reset`
- **Request:** `country_code`, `phone`, `otp_code`, `password`, `password_confirmation`
- **Response:** `message`

---

## Profile, partials, settings

### GET /api/profile/partials

- **Auth:** public
- **Controller:** `ProfileController@index`
- **Response from ProfilePartialResource:** `gender`, `diabetes`, `height_measurement`, `weight_measurement`, `competition_types`, `ethnicities`, `qualifications`, `dietaries`, `cities`, `occupations`, `avatars`

### GET /api/app-name

- **Auth:** public
- **Controller:** `ProfileController@getAppName`
- **Response:** `data.app_name`

### GET /api/profile

- **Auth:** Sanctum + single-device
- **Controller:** `ProfileController@show`
- **Response:** Same core shape as `GET /api/user`

### PUT /api/profile

- **Auth:** Sanctum + single-device
- **Controller:** `ProfileController@update`
- **Request:**
  - Identity: `first_name`, `last_name`, `email`, `country_code`, `phone`
  - Demographics/profile: `date_of_birth`, `gender`, `diabetes`, `height`, `height_measurement`, `weight`, `weight_measurement`, `ethnicity`, `qualification`, `occupation`, `city`, `city_name`, `dietary`, `avatar_id`, `avatar` (optional file), `is_profile_completed`
- **Response:** `message`
- **Notes:**
  - If `city=0` and `city_name` is supplied, a new city row may be created.
  - Phone/email changes can clear existing verification state.

### PUT /api/profile/change-password

- **Auth:** Sanctum + single-device
- **Controller:** `ProfileController@changePassword`
- **Request:** `old_password`, `password`, `password_confirmation`
- **Response:** `message`

### GET /api/setting

- **Auth:** Sanctum + single-device
- **Controller:** `SettingController@index`
- **Response from SettingResource:** `facebook_login`, `google_login`, `apple_login`, `stripe`

### PUT /api/setting

- **Auth:** Sanctum + single-device
- **Controller:** `SettingController@update`
- **Request:** `enable_notification`, `enable_vibration`, `enable_sound`
- **Response:** `message`

### POST /api/account/delete

- **Auth:** Sanctum + single-device
- **Controller:** `SettingController@deleteAccount`
- **Response:** `message`

---

## Payments and subscriptions

### GET /api/payment

- **Auth:** Sanctum + single-device
- **Controller:** `PaymentController@index`
- **Response from PaymentCollection:** Paginated `data[]` including `id`, `package`, `amount`, `purchase_at`, `expire_at`, `stripe_subscription_id`, `auto_renew_status`, `payment_method`

### POST /api/payment

- **Auth:** Sanctum + single-device
- **Controller:** `PaymentController@store`
- **Request from PaymentRequest:**
  - `subscription_package` required package slug
  - `payment_method` nullable enum
  - App Store path: `receipt_data`
  - Stripe path: `stripe_subscription_id`, `invoice_id`, `current_period_end`
- **Response:** success/failure message; App Store validation failures may also include a reason
- **Notes:** Stripe subscriptions are created from client-supplied subscription identifiers. Apple receipts are validated server-side via `AppleReceiptService`.

### POST /api/payment/license-key

- **Auth:** Sanctum + single-device
- **Controller:** `PaymentController@licenseKey`
- **Request:** `license_key`
- **Response:** `message`

### GET /api/payment/partial

- **Auth:** Sanctum + single-device
- **Controller:** `PaymentController@partial`
- **Response:** `packages[]` each including `id`, `name`, `slug`, `title`, `price`, `package_type`, `description`, `app_store_purchase_id`, `stripe_purchase_id`, `google_play_purchase_id`, `show_license_key`

### POST /api/payment/stripe-webhook

- **Auth:** public
- **Controller:** `PaymentController@handleWebhook`
- **Request:** raw webhook payload, `Stripe-Signature` header
- **Response:** `message`
- **Notes:** Signature verification against configured webhook secret. Handles successful invoice payment and subscription update/delete lifecycle events.

---

## Pages, help, tutorials, ads, content

### GET /api/page

- **Auth:** Sanctum + single-device
- **Controller:** `PageController@index`
- **Response:** `data[]` with `id`, `name`, `slug`

### GET /api/page/{slug}

- **Auth:** Sanctum + single-device
- **Controller:** `PageController@show`
- **Response:** `id`, `name`, `slug`, `title`, `description`, `meta_title`, `meta_description`

### GET /api/tutorial

- **Auth:** Sanctum + single-device
- **Controller:** `TutorialController`
- **Response:** array of tutorial items such as `page_name` and `video`

### GET /api/ads

- **Auth:** Sanctum + single-device
- **Controller:** `AdController`
- **Response:** `data[]` with `id`, `image`

### GET /api/topics

- **Auth:** Sanctum + single-device
- **Controller:** `TopicController`
- **Response:** `data[]` with `id`, `name`

### GET /api/gethelp

- **Auth:** Sanctum + single-device
- **Controller:** `GetHelpController@index`
- **Response:** FAQ/help data from settings

### POST /api/gethelp

- **Auth:** Sanctum + single-device
- **Controller:** `GetHelpController@store`
- **Request:** `topic`, `body`
- **Response:** `message`
- **Example request from Postman:**

```json
{
  "subject": "Lorem ipsum dolor sit amet",
  "body": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam et fermentum dui. Ut orci quam, ornare sed lorem sed, hendrerit."
}
```

- **Drift note:** Postman example uses `subject`, while documentation inferred `topic` from code review. Validate before external publication.

### GET /api/recipe-category

- **Auth:** Sanctum + single-device
- **Controller:** `RecipeCategoryController@index`
- **Response:** `data[]` with `id`, `name`

### GET /api/food

- **Auth:** Sanctum + single-device
- **Controller:** `FoodController@index`
- **Request:** `search` optional
- **Response:** `data[]` with key summary nutrition and display fields

### GET /api/food/{id}

- **Auth:** Sanctum + single-device
- **Controller:** `FoodController@show`
- **Response:** food detail including `name`, `image`, `size`, `is_drink`, `carb`, `net_carb`, `energy`, `protein`, `fat`, `fiber`, `sugar`, nutrient level classifications

### GET /api/carb-hub

- **Auth:** Sanctum + single-device
- **Controller:** `CarbHubController@index`
- **Request:** `type`, `favorite`, `search`, `recipe_category_id`
- **Response:** `data[]` with `id`, `title`, `type`, `media`, `thumbnail`, `favorite`, `is_new`, `mime_type`

### GET /api/carb-hub/{id}

- **Auth:** Sanctum + single-device
- **Controller:** `CarbHubController@show`
- **Response:** `id`, `title`, `sub_title`, `description`, `type`, `media`, `mime_type`, `thumbnail`, `time`, `point`, `is_rewarded`

### POST /api/carb-hub/point

- **Auth:** Sanctum + single-device
- **Controller:** `CarbHubController@storePoint`
- **Request:** `carb_hub_id`
- **Response:** `message`

### POST /api/carb-hub/favorite

- **Auth:** Sanctum + single-device
- **Controller:** `CarbHubController@favorite`
- **Request:** `carb_hub_id`, `is_favorite`
- **Response:** `message`

---

## Habit tracker

### GET /api/topic-pdfs

- **Auth:** Sanctum + single-device
- **Controller:** `HabitTopicPdfController@index`
- **Response:** `topics[]` with `id`, `title`, `url`

### GET /api/habit-topics

- **Auth:** Sanctum + single-device
- **Controller:** `HabitTopicController@index`
- **Response:** habit topics with nested habits

### GET /api/habit-topics/{id}

- **Auth:** Sanctum + single-device
- **Controller:** `HabitTopicController@show`
- **Response:** single habit topic with nested habits

### GET /api/habits

- **Auth:** Sanctum + single-device
- **Controller:** `HabitController@index`
- **Response:** habits with fields such as `title`, `description`, `type`, `frequency`, `is_required`, `config`

### GET /api/habits/{id}

- **Auth:** Sanctum + single-device
- **Controller:** `HabitController@show`
- **Response:** single habit detail

### GET /api/weeks

- **Auth:** Sanctum + single-device
- **Controller:** `UserWeekController@index`
- **Response:** week records with `year`, `week_number`, `start_date`, `end_date`, `status`, `is_locked`, `completion_percentage`

### GET /api/weeks/current

- **Auth:** Sanctum + single-device
- **Controller:** `UserWeekController@current`
- **Response:** `success`, `data.week`, `data.topics[]`, nested habits with either `daily_entries[]` for daily habits or `weekly_value` and `entry_id` for weekly habits

### GET /api/weeks/{year}/{week}

- **Auth:** Sanctum + single-device
- **Controller:** `UserWeekController@show`
- **Response:** same overall shape as current week

### GET /api/weeks/{year}/{week}/summary

- **Auth:** Sanctum + single-device
- **Controller:** `UserWeekController@summary`
- **Response:** week summary with aggregated daily/weekly completion state

### GET /api/weeks/history

- **Auth:** Sanctum + single-device
- **Controller:** `UserWeekController@history`
- **Response:** historical week collection

### POST /api/habit-entries

- **Auth:** Sanctum + single-device
- **Controller:** `HabitEntryController@store`
- **Request:** `habit_id`, `year`, `week`, `date` for daily habits, and one of `value_boolean`, `value_text`, `value_number`, `value_scale`
- **Response:** `success`, `message`, `data`

### PUT /api/habit-entries/{habitEntry}

- **Auth:** Sanctum + single-device
- **Controller:** `HabitEntryController@update`
- **Request:** `date`, value field(s) as appropriate
- **Response:** `success`, `message`, `data`

### DELETE /api/habit-entries/{habitEntry}

- **Auth:** Sanctum + single-device
- **Controller:** `HabitEntryController@destroy`
- **Response:** `success`, `message`

### POST /api/habit-entries/batch

- **Auth:** Sanctum + single-device
- **Controller:** `HabitEntryController@batchStore`
- **Request:** `entries[]`
- **Response:** `success`, `message`, `data[]`

---

## Levels, quiz, steak message

### GET /api/level

- **Auth:** Sanctum + single-device
- **Controller:** `LevelController@index`
- **Response:** `point`, `consecutive_correct_threshold`, `data[]` with level summaries, `positions[]`

### GET /api/level/current

- **Auth:** Sanctum + single-device
- **Controller:** `LevelController@currentLevel`
- **Response:** current/next level metadata

### GET /api/level/{id}

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `LevelController@show`
- **Response:** level detail with rounds/steps/questions
- **Note:** exact nested `rounds[]` shape is partially inferred from model/service logic.

### POST /api/quiz

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `QuizController@store`
- **Request:** `level`, `step`, `earn_point`, `rounds[]`
- **Response:** `message`, `steak_message`
- **Example request (basic round):**

```json
{
  "level": 1,
  "step": 0,
  "rounds": [
    {
      "given_answer": 10,
      "options": [
        { "id": 1, "correct": true },
        { "id": 10, "correct": false }
      ]
    }
  ]
}
```

- **Example request (level 6 advanced pattern):**

```json
{
  "level": 6,
  "step": 1,
  "rounds": [
    {
      "options": [
        { "id": 1, "checked": true },
        { "id": 10, "checked": false },
        { "id": 11, "checked": true },
        { "id": 15, "checked": true }
      ]
    }
  ]
}
```

### GET /api/steak-message

- **Auth:** Sanctum + single-device
- **Controller:** `SteakMessageController@index`
- **Note:** response shape was not fully traced statically and should be validated if shared externally.

---

## Notifications

### GET /api/notification

- **Auth:** Sanctum + single-device
- **Controller:** `NotificationController`
- **Response:** `data[]` with `id`, `title`, `type`, `body`, `is_viewed`, `time_display`; plus `unseen_notifications`

### PUT /api/notification

- **Auth:** Sanctum + single-device
- **Controller:** `NotificationController@seen`
- **Response:** `message`

---

## Groups, invites, leaderboard, referral

### GET /api/leaderboard

- **Auth:** Sanctum + single-device
- **Controller:** `LeaderBoardController`
- **Request:** `group_id` optional, `type` optional: `today`, `week`, `month`, `year`
- **Response:** leaderboard entries with user display fields and points

### GET /api/groups

- **Auth:** Sanctum + single-device
- **Controller:** `GroupController@index`
- **Response:** paginated groups

### POST /api/groups

- **Auth:** Sanctum + single-device
- **Controller:** `GroupController@store`
- **Request:** `competition_type`, `name`
- **Response:** group summary + message

### DELETE /api/groups/{id}

- **Auth:** Sanctum + single-device
- **Controller:** `GroupController@destroy`
- **Response:** `message`

### GET /api/groups/invitation-requests

- **Auth:** Sanctum + single-device
- **Controller:** `GroupController@invitationRequests`
- **Response:** pending invite request data

### POST /api/group-users

- **Auth:** Sanctum + single-device
- **Controller:** `GroupUserController@store`
- **Request:** `group_id`, `source`, `username` or `contacts[]` depending on source
- **Response:** `message`

### PUT /api/group-users/change-status

- **Auth:** Sanctum + single-device
- **Controller:** `GroupUserController@changeStatus`
- **Request:** `group_id`, `status`
- **Response:** `message`

### GET /api/group-users

- **Auth:** Sanctum + single-device
- **Controller:** `GroupUserController@index`
- **Request:** `group_id`, `status[]` optional, `is_admin` optional
- **Response:** paginated group membership rows

### PUT /api/group-users/remove

- **Auth:** Sanctum + single-device
- **Controller:** `GroupUserController@remove`
- **Request:** `group_id`, `user_id`
- **Response:** `message`

### PUT /api/group-users/change-admin

- **Auth:** Sanctum + single-device
- **Controller:** `GroupUserController@changeAdmin`
- **Request:** `group_id`, `user_id`
- **Response:** `message`

### GET /api/referral/generate-link

- **Auth:** Sanctum + single-device
- **Controller:** `ReferralController@generateReferralLink`
- **Response:** `data.url`

### GET /api/share-app

- **Auth:** Sanctum + single-device
- **Controller:** `AppLinkController@getShareAppLink`
- **Response:** `message`, `share_url`

---

## My Health

### GET /api/steps

- **Auth:** Sanctum + single-device
- **Controller:** `StepController@index`
- **Request:** `type`: `today`, `week`, `month`, `year`
- **Response:** today and aggregate step data from `StepServices`
- **Note:** exact week/month/year chart payload is partially inferred from service logic.

### POST /api/steps

- **Auth:** Sanctum + single-device
- **Controller:** `StepController@store`
- **Request:** `steps`
- **Response:** `message`

### PUT /api/steps/target

- **Auth:** Sanctum + single-device
- **Controller:** `StepController@updateTarget`
- **Request:** `target_steps`
- **Response:** `message`

### GET /api/weight

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `WeightController@index`
- **Request:** `type`: `today`, `week`, `month`, `year`
- **Response:** current/target/history weight data via `WeightServices`

### POST /api/weight

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `WeightController@store`
- **Request:** `weight`
- **Response:** `message`

### PUT /api/weight/target

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `WeightController@updateTarget`
- **Request:** `target_weight`
- **Response:** `message`

### GET /api/blood-glucose

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `BloodGlucoseController@index`
- **Request:** `type`: `today`, `week`, `month`, `year`, `latest`; `glucose_type` unless `type=latest`
- **Response:** blood glucose current/history/chart data via `BloodGlucoseServices`

### POST /api/blood-glucose

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `BloodGlucoseController@store`
- **Request:** `glucose_type`, `blood_glucose`
- **Response:** `message`

### PUT /api/blood-glucose/target

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `BloodGlucoseController@updateTarget`
- **Request:** `glucose_type`, `target_blood_glucose`
- **Response:** `message`

### GET /api/blood-pressure

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `BloodPressureController@index`
- **Request:** `type`: `today`, `week`, `month`, `year`, `chart`
- **Response:** blood pressure current/history/chart data via `BloodPressureServices`

### POST /api/blood-pressure

- **Auth:** Sanctum + single-device + subscription
- **Controller:** `BloodPressureController@store`
- **Request:** `systolic`, `diastolic`
- **Response:** `message`

---

## Public reference

### GET /api/country

- **Auth:** public
- **Controller:** `CountryController`
- **Response:** `data[]` with country display fields such as `name`, `dial_code`, `country_code`, `flag`
