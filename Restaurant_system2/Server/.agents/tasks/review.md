# User Creation Email Delivery Flow — Fixed

The user creation flow now reliably emails generated passwords to newly created users. The fix addresses a critical gap: the backend was generating passwords but not surfacing email delivery status to the frontend, leaving admins unable to see if emails failed. The implementation captures password in plaintext before hashing, sends it via the Mailable, logs all outcomes, and exposes delivery status through the API response, allowing the frontend to inform the admin whether the email succeeded, failed, or faced a configuration warning.

**Watch for:** Email sending is now synchronous (not queued), which is safe for small hotel deployments but may cause slow API responses if mail service is slow or unreachable. If email provider latency becomes an issue, queuing should be re-introduced. The Resend migration (commits 79943ea and 1861306) bypasses port issues but adds a dependency on a third-party HTTP API — if Resend is down, emails will fail. Consider a fallback to SMTP for production resilience.

**Verdict**: APPROVED

---

## High-level view

The plaintext password is now generated before hashing and passed through the Mailable's `with()` data, reaching the Blade template correctly. The mail configuration is checked at send time to detect 'log' mode in production and warn the admin. All email outcomes — success, transient SMTP failures, and configuration warnings — are logged with structured detail and included in the API response (`mail_status`, `mail_error`, `mail_warning`). The frontend now reads these fields and shows the admin appropriate notifications: green success, red failure with error details, or yellow warning about 'log' mode misconfiguration. If email fails, the generated password is displayed to the admin as a fallback for manual notification. The `.env.example` now defaults to SMTP (not log mode) and includes detailed comments on mail provider setup, preventing new deployments from defaulting to development-only configuration.

---

<details>
<summary>Issues (4)</summary>

1. **Synchronous email sending latency risk** — Email delivery is synchronous and blocks the API response. If the mail provider is slow or unreachable, the user creation endpoint will time out. Low likelihood in practice for healthy mail services, but a known trade-off worth monitoring.

2. **Resend HTTP API dependency** — Current production configuration uses Resend instead of SMTP to bypass Render port restrictions. Resend is a single point of failure; if Resend API is down, all password emails fail. No fallback to SMTP is in place.

3. **Email not sent if password property is not exposed** — A subtle risk during future refactoring: if the Mailable's `temporaryPassword` property is renamed or the `with()` call is removed, the template will render empty password fields silently. No compile-time check exists to catch this.

4. **Frontend doesn't show the generated password if email succeeds** — When email is sent successfully, the admin sees only a confirmation message. If the admin needs to manually notify the user (e.g., user lost the email), the password is not available in the UI. A copyable password field would improve fallback notification.

</details>

---

<details>
<summary>Details</summary>

### Password Generated and Exposed to Mailable Before Hashing

The UserController.store() method now generates the plaintext password before hashing it. Lines 99-100:

```php
$plainPassword = $request->password ?: Str::random(12);
```

This plaintext value is passed to the NewUserCreated Mailable at line 125, allowing the Blade template to render it in the email body. The hashed password is stored in the database column at line 104:

```php
'password_hash' => Hash::make($plainPassword),
```

This two-step approach is correct: the plaintext is never persisted, only forwarded to the mail service, and the database stores the hash. No plaintext password ever reaches storage.

### Mail Configuration Checked at Runtime

Before sending, lines 110-122 detect if the mailer is set to 'log' mode in a non-local environment:

```php
$currentMailer = config('mail.default');
$appEnv = config('app.env', 'local');
$mailWarning = null;

if ($currentMailer === 'log' && $appEnv !== 'local') {
    $mailWarning = "Mail mailer is set to LOG mode...";
    Log::warning('[UserController.store] Mail configuration issue', [...]);
}
```

This catches a common misconfiguration where new deployments default to 'log' mode (development only). The warning is included in the API response and displayed to the frontend, immediately alerting the admin to the problem.

### All Outcomes Logged and Returned

Lines 135-155 handle the mail sending attempt:

```php
try {
    Mail::to($user->email)->send(new NewUserCreated($user, $plainPassword));
    Log::info('[UserController.store] Welcome email sent successfully', [...]);
} catch (Throwable $mailException) {
    $mailStatus = 'failed';
    $mailError  = $mailException->getMessage();
    Log::error('[UserController.store] Welcome email failed to send', [...]);
}
```

Success and failure are both logged with structured context (user ID, email, mailer type, error class on failure). The response at lines 157-167 includes all status fields:

```json
{
  "mail_status": "sent" | "failed",
  "mail_error": null | "error message",
  "mail_warning": null | "config warning",
  "password_generated": true | false
}
```

This ensures the frontend can act on precise delivery status.

### NewUserCreated Mailable Correctly Passes Password

The Mailable in app/Mail/NewUserCreated.php (lines 20-21) receives both the User model and temporaryPassword string:

```php
public function __construct(
    public User $user,
    public string $temporaryPassword,
) { ... }
```

The `content()` method (lines 37-48) makes it available to the template:

```php
return new Content(
    view: 'emails.new-user-created',
    with: [
        'temporaryPassword' => $this->temporaryPassword,
        // ... other fields ...
    ],
);
```

The Blade template at line 90 renders the password:

```blade
<div class="credential-value">{{ $temporaryPassword }}</div>
```

This is all correct: the password is generated, passed as a public Mailable property, made available to the template via `with()`, and rendered in the HTML body.

### Frontend Notifications Based on Email Status

CreateUser.vue (lines 48-60) captures mail status from the API response and builds appropriate notifications:

```javascript
mailStatus.value = response.mail_status || 'sent'
mailError.value = response.mail_error || null
mailWarning.value = response.mail_warning || null

if (mailStatus.value === 'failed') {
  successMessage.value = `⚠️ User created, but email FAILED to send to ${data.email}`
} else if (mailStatus.value === 'warning') {
  successMessage.value = `⚠️ User created with email delivery warning`
} else {
  successMessage.value = `✓ User created successfully!`
}
```

Three separate UI blocks follow for each status (lines 75-180). The 'failed' block shows the error message and advises the admin to manually notify the user. The 'warning' block explains 'log' mode. The 'sent' block auto-redirects after 3 seconds. This is well-implemented.

### .env.example Updated with Production Defaults

The .env.example now defaults to SMTP (not log mode) at line 61:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
```

Lines 66-79 include a comment block with detailed setup instructions for Gmail App Passwords and a note about Render port restrictions (port 587 TLS is open, port 465 is blocked). This prevents new deployments from blindly using 'log' mode.

### Synchronous Email Sending Trade-off

Email is sent synchronously (not queued) via `Mail::to()->send()`. This is a deliberate choice to avoid the overhead of a queue worker for a small hotel system. The trade-off: if Gmail SMTP is slow (typical: ~1-2 seconds), the user creation API blocks for that duration. For production, if API latency becomes an issue, the code can be wrapped in `Mail::to()->queue()` and a background queue worker (supervisord, etc.) can be added to Render.

### Documentation and Render-Specific Guidance

The new docs/EMAIL_CONFIGURATION.md file (274 lines) covers all major email providers (Gmail, Resend, Postmark), local testing with Mailpit, and explicit Render deployment guidance (port 587 is open, port 465 is blocked). This is thorough and prevents deployment surprises.

---

### Current Production Configuration Note

Recent commits (79943ea, 1861306) switched from SMTP to Resend (HTTP API over port 443). This bypasses Render's port restrictions but trades SMTP reliability for HTTP API availability. The `.env.example` still shows SMTP as the default for new deployments; the Render production environment variable is configured separately to use Resend. This is a pragmatic choice given Render's network constraints, but it's a transient solution. If Render ever opens port 587 or 465, SMTP should be restored.

</details>

---

<details>
<summary>File map</summary>

- **app/Http/Controllers/Api/UserController.php** — store() method: generate password in plaintext before hashing (line 99), check mail config for 'log' mode warning (lines 110-122), log user creation with timestamp (lines 102-107), capture mail sending outcome (lines 128-155), include mail_status/mail_error/mail_warning/password_generated in response (lines 157-167).

- **app/Mail/NewUserCreated.php** — constructor receives temporaryPassword string (line 16-17), content() method passes password to template via with() data (lines 37-48).

- **resources/views/emails/new-user-created.blade.php** — renders temporaryPassword at line 90 in a styled credential box; full HTML email template with security notice, login steps, and styling.

- **src/views/Admin/users/CreateUser.vue** — createUser() function captures mail_status, mail_error, mail_warning from response (lines 48-60), shows green/yellow/red notification cards based on status (lines 75-180), displays error details if mail fails (line 144).

- **.env.example** — MAIL_MAILER set to 'smtp' (line 61, not 'log'), MAIL_HOST, MAIL_PORT, credentials included with placeholder values, comment block (lines 66-79) explains Gmail App Password setup and Render port constraints.

- **docs/EMAIL_CONFIGURATION.md** — comprehensive setup guide covering Gmail, Resend, Postmark, Mailpit for local testing, Render port restrictions, troubleshooting, and production deployment checklist.

[Full diff available via: `git diff main~15..main -- app/Http/Controllers/Api/UserController.php app/Mail/NewUserCreated.php src/views/Admin/users/CreateUser.vue .env.example`]

</details>
