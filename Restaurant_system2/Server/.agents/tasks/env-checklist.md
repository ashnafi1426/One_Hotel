# Environment Variables Checklist for Email Delivery

This checklist ensures that the user creation email flow works correctly when deployed to Render.

## Critical Email Variables (MUST be set on Render)

### MAIL_MAILER
- **Required**: Yes
- **Value**: `smtp` (for production)
- **Do NOT use**: `log` (development only - emails won't be sent)
- **Example**: `MAIL_MAILER=smtp`

### MAIL_HOST
- **Required**: Yes (when using smtp)
- **Value**: Your SMTP server hostname
- **Examples**: 
  - `smtp.gmail.com` (Gmail)
  - `smtp.sendgrid.net` (SendGrid)
  - `smtp.mailgun.org` (Mailgun)
  - `smtp.resend.com` (Resend)
- **Example**: `MAIL_HOST=smtp.gmail.com`

### MAIL_PORT
- **Required**: Yes (when using smtp)
- **Common values**: `587` (TLS), `465` (SSL), `25` (SMTP)
- **Render note**: Port 465 may be blocked; use 587 (TLS) instead
- **Example**: `MAIL_PORT=587`

### MAIL_ENCRYPTION
- **Required**: Yes (when using smtp)
- **Values**: `tls` or `ssl` (match your MAIL_PORT)
- **For port 587**: Use `tls`
- **For port 465**: Use `ssl`
- **Example**: `MAIL_ENCRYPTION=tls`

### MAIL_USERNAME
- **Required**: Yes (when using smtp with authentication)
- **Value**: Your SMTP username or email
- **Example**: `MAIL_USERNAME=your-email@gmail.com`

### MAIL_PASSWORD
- **Required**: Yes (when using smtp with authentication)
- **Value**: Your SMTP password or API key
- **⚠️ WARNING**: Never commit actual passwords to version control
- **Example**: `MAIL_PASSWORD=your-app-specific-password` (use 16-char Gmail App Password)

### MAIL_FROM_ADDRESS
- **Required**: Yes
- **Value**: The email address that appears as the sender
- **Example**: `MAIL_FROM_ADDRESS=noreply@one-hotel.com`

### MAIL_FROM_NAME
- **Required**: No, defaults to APP_NAME
- **Value**: Display name for sender
- **Example**: `MAIL_FROM_NAME=Hotel Management System`

---

## Supporting Variables

### APP_FRONTEND_URL
- **Required**: Yes (so links in emails point to correct login page)
- **Value**: Your frontend application URL
- **Example (Production)**: `APP_FRONTEND_URL=https://your-frontend.vercel.app`
- **Example (Local)**: `APP_FRONTEND_URL=http://localhost:5173`

### APP_ENV
- **Required**: Yes
- **Value**: `production` (on Render) or `local` (development)
- **Used for**: Mail config validation — warns if MAIL_MAILER=log but APP_ENV != local

### APP_URL
- **Required**: Yes
- **Value**: Backend API URL
- **Example (Production)**: `APP_URL=https://your-backend.onrender.com`
- **Example (Local)**: `APP_URL=http://localhost:8000`

---

## Gmail Setup (Recommended for Testing)

1. **Enable 2-Factor Authentication**
   - Go to https://myaccount.google.com/security
   - Enable "2-Step Verification"

2. **Create an App Password**
   - After 2FA is enabled, visit https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows Computer" (or your device)
   - Google will generate a 16-character password
   - Copy this password (remove any spaces)

3. **Set Environment Variables**
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-16-character-app-password
   MAIL_FROM_ADDRESS=your-email@gmail.com
   ```

---

## Debugging Email Issues

### Check Logs on Render
1. In Render dashboard, go to your service
2. Navigate to "Logs" tab
3. Search for `[UserController.store]` entries
4. Look for messages like:
   - `"Welcome email sent successfully"` ✅
   - `"Welcome email failed to send"` ❌
   - `"Mail configuration issue"` ⚠️

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Email not sent, no error | `MAIL_MAILER=log` in production | Change to `MAIL_MAILER=smtp` |
| "Authentication failed" | Wrong MAIL_PASSWORD | Generate new app password for Gmail |
| "Connection timeout" | Wrong MAIL_HOST or blocked port | Use `smtp.gmail.com:587` |
| "Invalid sender address" | MAIL_FROM_ADDRESS format wrong | Use valid email format |
| "User not found" | Invalid MAIL_USERNAME | Verify against provider documentation |

### Test Email Sending (Local Development)

```bash
# SSH into Render or run locally
php artisan tinker

# Test sending a mail
Mail::raw('This is a test', function($m) {
    $m->to('your-test-email@example.com')->subject('Test Email');
})->send();

# Check if email appears in logs
tail -f storage/logs/laravel.log | grep -i "mail"
```

---

## Deployment Checklist

Before deploying to Render, verify:

- [ ] `MAIL_MAILER` is set to `smtp` (not `log`)
- [ ] `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` are configured
- [ ] `MAIL_FROM_ADDRESS` is set to a valid email
- [ ] `APP_FRONTEND_URL` points to your Vercel frontend domain
- [ ] `APP_ENV` is set to `production`
- [ ] Test email by creating a user through the admin interface
- [ ] Verify email arrives in the test user's inbox
- [ ] Check Render logs for `"Welcome email sent successfully"`

---

## References

- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [Gmail App Passwords Setup](https://support.google.com/accounts/answer/185833)
- [Render Networking Guide](https://render.com/docs/networking)
- [SendGrid SMTP Setup](https://docs.sendgrid.com/for-developers/sending-email/smtp-service)

