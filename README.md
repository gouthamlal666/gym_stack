# GymStack — Multi-tenant Gym SaaS (TALL stack)

**T**ailwind CSS 4 · **A**lpine.js (bundled with Livewire) · **L**aravel 13 · **L**ivewire 4 · Chart.js

A multi-tenant gym management platform with a premium **Personal Training (PT) suite**: PT packages and sessions,
body and progress tracking, private progress photos, fitness assessments, PT workouts, nutrition and goals.

## Quick start

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm run build        # or: npm run dev
php artisan serve
```

Open http://localhost:8000. Emails (OTP codes, password resets, reminders) go to `storage/logs/laravel.log` (`MAIL_MAILER=log`).

### Demo accounts (password: `password`)

| Role | Email | Try |
|---|---|---|
| Super admin (SaaS) | admin@gymstack.test | Tenants, subscriptions, suspend/activate |
| Gym admin | owner@irontemple.test | Everything in the gym, including progress photos |
| Manager | manager@irontemple.test | Members, plans, trainers, PT (no photos) |
| Receptionist | reception@irontemple.test | Check-in, memberships, payments, PT booking |
| Accountant | accounts@irontemple.test | Billing, refunds, expenses, reports |
| Trainer | john@irontemple.test | Only *his* PT clients: sessions, body, photos, workouts… |
| Trainer | priya@irontemple.test | Can't see Alex (not her client) |
| **PT customer** | **alex@member.test** | Premium PT dashboard: 20-session package, 12 done / 8 left, 82 → 76 kg, goal "Lose 8 KG" at 75 % |
| Regular member | rahul@member.test | Portal without PT modules |
| Other tenant | owner@pulse.test | Proves tenant isolation |

## Access structure

```
SUPER ADMIN ── platform panel (/admin)
  └ GYM ADMIN ── everything in their gym
      ├ Manager · Receptionist · Accountant   (permission map in config/gym.php)
      └ TRAINER ── only PT clients they coach
          ├ Regular Member ── portal: membership, bills, attendance
          └ PT Customer    ── + PT sessions, body tracking, progress photos,
                               fitness assessment, PT workout, nutrition, goals
```

* **Permissions** are defined in `config/gym.php` → registered as Gates in `AppServiceProvider`. You can see the matrix in *Staff & roles*.
* **Multi-tenancy** means one database with rows scoped by `gym_id`, handled by `App\Models\Concerns\BelongsToGym`. It adds a global scope and stamps `gym_id` on create, so route-model binding returns 404 across tenants.
* **PT gating** follows `members.training_type = regular | pt`. The `pt.customer` middleware covers the portal. The `view-pt-member` and `coach-pt-member` gates cover staff.
* **Photo privacy**: files sit on the private `local` disk and are streamed by `FileController` only when `view-progress-photos` passes. Only the member, their assigned trainer and the gym admin can see them.

## Modules built

| Area | What's included |
|---|---|
| Auth & users | Login, gym self-registration, forgot/reset password, passwordless **email OTP login**, **email 2FA**, profile/avatar, roles & permissions, **activity/audit log** |
| Multi-tenant | Gyms, **multiple branches**, branch staff, **branding** (logo + brand colour applied app-wide), tax/currency/prefix settings, super-admin panel |
| Members | Registration, profile, member ID, emergency contact, medical notes, **documents** (private), membership & payment history, attendance, trainer assignment, portal login |
| Memberships | Plans (monthly/quarterly/half-yearly/yearly/custom/**trial**), admission fee, discounts, **renewal, upgrade/downgrade with pro-rata credit, freeze/unfreeze, extension, cancellation, expiry tracking** |
| Billing | Invoices (auto from memberships/PT), ad-hoc invoices, **partial payments**, receipts, **refunds**, void, tax, discounts, printable invoice/receipt (Save as PDF), payment reminders |
| Finance | Expenses by category, revenue/expense/profit, outstanding dues, daily/monthly reports, **branch-wise** report, revenue by source, CSV export |
| Trainers | Profiles, specializations, certifications, weekly availability, assigned clients, performance trend, **commission** on PT revenue |
| **PT management ⭐** | PT packages (sessions, price, validity), enrolment (converts to PT customer), session tracking (completed/remaining/expiry) |
| **PT sessions & schedule** | Week calendar, **slot booking from trainer availability** with clash detection, reschedule, cancel, **no-show tracking**, live session timer, exercises (sets/reps/weight/duration), calories, notes, feedback, reminders |
| **Body tracking** | Initial + periodic assessments (13 metrics, auto BMI), compare any two dates, weight/body-fat/measurement charts |
| **Progress photos** | Front/back/left/right, before/after comparison with weight & BF %, strict privacy |
| **Fitness assessment** | 8 ratings (radar chart) + 7 standard tests with history and change |
| **PT workouts** | Weekly program builder: goal, level, weeks; per exercise sets, reps, weight, rest, tempo, RPE, instructions, video, notes; duplicate/versioning |
| **PT nutrition** | Calories/macros/water targets, macro suggestion from body weight, meal-by-meal plan |
| **PT goals** | Goal type, tracked metric, start/target; **current value and % progress update automatically from assessments** |
| **PT customer dashboard** | Program progress, today's session, body progress, package ring, next assessment, weight chart |
| Attendance | Front-desk quick check-in/out, hourly heat bar, member streak & 12-week heatmap |
| Exercise library | Global library + gym-specific exercises (used in plans and session logs) |

### Membership reminders (WhatsApp + email)

* **Memberships → Expiring ≤ 7 days / Expired (not renewed):** select members (or *Select all*), click **Send reminder**, choose WhatsApp and/or Email, preview, then send. Each row also has a *Send* button, a **WhatsApp click-to-chat** icon (opens `wa.me` with the message pre-filled, so staff can send from their own phone) and a *Last reminded* column.
* **Automatic:** `gym:membership-reminders` runs daily at 09:30. It sends reminders 7, 3 and 1 days before expiry and 1 and 7 days after expiry, only to members who have not renewed, and at most once a day per membership. You can configure this per gym in **Gym settings → Membership reminders**.
* **WhatsApp delivery:** set it up in **Gym settings → WhatsApp Business**. *Test mode* writes messages to `storage/logs/laravel.log`. *Live* uses the Meta WhatsApp Cloud API with the gym's own phone number ID and access token (the token is stored encrypted). Business-started messages need **approved templates** with the body variables `{{1}}` name, `{{2}}` plan, `{{3}}` end date and `{{4}}` gym. There is a *Send test* button.
* Every attempt is logged in `notification_logs` (sent, failed with the error, or skipped for missing email/phone). It also shows under *Renewal reminders* on the member's profile.
* Permission `reminders.send` covers Gym admin, Manager and Receptionist.

### Scheduled jobs (`routes/console.php`)

* `gym:refresh-statuses` runs daily. It activates upcoming memberships, ends freezes, expires memberships and PT packages.
* `gym:send-reminders` runs hourly. It sends PT session reminders (24 h ahead) and overdue-payment reminders.
* `gym:membership-reminders` runs daily at 09:30. It sends WhatsApp and email renewal reminders (see above).

Add `* * * * * php artisan schedule:run` to cron in production.

## Tests

```bash
php artisan test
```

The suite covers every page for all 5 staff roles, the PT portal, regular-member gating, photo privacy (7 actors), trainer client scoping, tenant isolation, 2FA login, registration, partial payment → paid → refund, freeze and upgrade, PT booking clash detection, session completion consuming a session, and goal progress updating from assessments.

## Roadmap (next modules)

The architecture has room for: Inventory & POS, CRM & leads, WhatsApp automation (add a notification channel next to `mail`), in-app chat, classes, general workout/diet plans, gamification and challenges, website builder, reviews, support tickets, HR, staff attendance and payroll, an automation engine, AI fitness features, and payment-gateway integrations (Razorpay/Stripe fit the existing `BillingService::recordPayment`).

## Code map

```
app/Models/Concerns/BelongsToGym.php   tenancy scope
app/Services/                          BillingService, MembershipService, PtService, OtpService
app/Support/PtProgress.php             PT analytics (summary, comparisons, charts)
app/Livewire/                          full-page components (Members, Billing, Pt, Portal, Admin, …)
app/Livewire/Pt/Client/*               PT client tabs (body, photos, fitness, workout, nutrition, goals, sessions)
resources/views/pt/partials/           PT views shared by trainer screens and the customer portal
config/gym.php                         roles, permissions, metrics, tests, meal types…
```
