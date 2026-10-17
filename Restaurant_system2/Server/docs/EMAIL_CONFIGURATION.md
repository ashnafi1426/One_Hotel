# Email Configuration Guide

## Overview

This guide explains how to configure email sending for the One Hotel system, particularly for the **user creation flow** where administrators create new users and passwords are emailed to them.

---

## Critical Issue: Email Not Sending in Production

### Common Cause
The most common reason emails don't send in production is the mail configuration being set to `log` mode instead of a real SMTP provider.

**❌ Development (Do NOT use in production):**
```env
MAIL_MAILER=log
```
This writes emails to `storage/logs/laravel.log` instead of sending them.

**✅ Production (Use one of these):**
```env
MAIL_MAILER=smtp
# or
MAIL_MAILER=resend
# or  
MAIL_MAILER=postmark
```

---

## Supported Email Providers

### 1. Gmail (Recommended for small deployments)

**Pros:**
- Free
- Reliable
- Easy to set up

**Cons:**
- Requires App Password (more complex than simple password)
- Subject to rate limits

**Setup:**

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable 2-Step Verification if not already enabled
3. Go to [App Passwords](https://myaccount.google.com/apppasswords)
4. Select "Mail" and "Windows Computer" (or your OS)
5. Google will generate a 16-character password
6. Use this password in your `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Hotel Management System"
```

**Testing:**
```bash
php artisan tinker
Mail::raw('Test email', fn($m) => $m->to('test@example.com')->subject('Test'))->send()
```

### 2. Resend (Recommended for production)

**Pros:**
- Easy API-based setup
- Good deliverability
- No SMTP port issues
- Developer-friendly

**Cons:**
- Requires account creation
- Limited free tier

**Setup:**

1. Create account at [resend.com](https://resend.com)
2. Get your API key from the dashboard
3. Add to `.env`:

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Hotel Management System"
```

### 3. Postmark (Best for reliability)

**Pros:**
- Excellent deliverability
- DKIM, SPF, DMARC support
- Transactional email service

**Cons:**
- Paid service (but free tier available)

**Setup:**

1. Create account at [postmark.com](https://postmark.com)
2. Get Server API token
3. Add to `.env`:

```env
MAIL_MAILER=postmark
POSTMARK_TOKEN=xxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Hotel Management System"
```

### 4. SMTP (Generic - any provider)

For any SMTP-compatible provider (SendGrid, Mailgun, etc.):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.provider.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Hotel Management System"
```

---

## Render.com Deployment

### Port Restrictions

- ✅ **Port 587** (TLS) — OPEN and recommended
- ✅ **Port 25** (SMTP) — Usually open but not recommended
- ❌ **Port 465** (SSL) — Often blocked on Render

### Render Configuration

If using SMTP on Render:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

**Do NOT use:**
```env
# ❌ WRONG - Port 465 often blocked on Render
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

### Testing on Render

1. SSH into your Render service:
   ```bash
   # SSH command from Render dashboard
   ```

2. Test mail configuration:
   ```bash
   php artisan tinker
   Mail::raw('Test', fn($m) => $m->to('your-email@gmail.com')->subject('Test'))->send()
   ```

3. Check logs for success/errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Local Development Testing

### Using Log Mode (Development Only)

For development, you can see emails without actually sending them:

```env
MAIL_MAILER=log
```

Emails are written to `storage/logs/laravel.log`:

```bash
tail -f storage/logs/laravel.log | grep "Welcome email"
```

### Using Laravel Tinker

Test email sending interactively:

```bash
php artisan tinker

# Send a test email
Mail::raw('Hello World', function($message) {
    $message->to('test@example.com')->subject('Test Email');
})->send();

# Check response
```

### Using Mailpit (Mock SMTP Server)

For local development with a realistic SMTP experience:

1. Install Mailpit: https://github.com/axllent/mailpit
2. Run: `mailpit` (starts on port 1025 by default)
3. Configure `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=127.0.0.1
   MAIL_PORT=1025
   MAIL_ENCRYPTION=null
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   ```
4. View emails in web UI: http://localhost:8025

---

## User Creation Email Flow

### How It Works

1. **Admin creates user** via admin dashboard
2. **Random password generated** if not provided
3. **User record saved** to database
4. **Mailable sent** via configured mailer
5. **Status returned** to frontend:
   - `mail_status`: 'sent' | 'failed' | 'warning'
   - `mail_error`: null | error message
   - `mail_warning`: null | config warning

### Email Template

The welcome email is sent using the `NewUserCreated` mailable:
- **Template**: `resources/views/emails/new-user-created.blade.php`
- **Contains**: User name, email, temporary password, login URL
- **Recipient**: User's registered email address

### What to Check If Email Doesn't Arrive

1. **Check frontend notification**
   - Success ✅ = Email sent successfully
   - Failed ❌ = Error message shown
   - Warning ⚠️ = Configuration issue

2. **Check logs** on server:
   ```bash
   tail -100 storage/logs/laravel.log | grep -i mail
   # Look for:
   # - "Welcome email sent successfully"
   # - "Welcome email failed"
   # - "Mail configuration issue"
   ```

3. **Verify `.env` configuration**:
   ```bash
   cat .env | grep MAIL_
   # Verify MAIL_MAILER is NOT "log" in production
   ```

4. **Test SMTP connection**:
   ```bash
   php artisan mail:test your-email@example.com
   ```

5. **Check spam folder**
   - Gmail: Check "Promotions", "Social", "Spam" tabs
   - Outlook: Check junk/spam folder
   - Add `noreply@your-domain.com` to contacts to improve deliverability

---

## Troubleshooting

### "Email Failed to Send" in Frontend

**Possible causes:**

1. **SMTP credentials wrong**
   - Verify `MAIL_USERNAME` and `MAIL_PASSWORD` are correct
   - For Gmail, ensure you're using App Password, not your account password

2. **Network connectivity**
   - Check firewall allows outbound on port 587
   - On Render, ensure port 587 is available

3. **MAIL_MAILER is 'log'**
   - Check `MAIL_MAILER=log` in `.env`
   - Change to `MAIL_MAILER=smtp` for production

4. **Mail server down**
   - Test SMTP connection: `php artisan mail:test test@example.com`
   - Check mail provider status page

### Emails Go to Spam

**Solutions:**

1. **Add SPF record** for your domain
2. **Add DKIM record** for your domain
3. **Use a dedicated email service** (Resend, Postmark, SendGrid)
4. **Send from domain-based email** (@your-domain.com, not @gmail.com)

### "Connection refused" Error

**Likely causes:**

1. Port is blocked (use 587, not 465 on Render)
2. MAIL_HOST is incorrect
3. Firewall issue

**Solution:**
```bash
# Test if port is open (on Render)
telnet smtp.gmail.com 587
```

### Authentication Failed

**Check:**
1. `MAIL_USERNAME` matches Gmail email exactly
2. `MAIL_PASSWORD` is the 16-character App Password (not your Gmail password)
3. Remove spaces from password if present

---

## Monitoring & Alerts

### Email Sending Statistics

Check logs to count successful vs failed emails:

```bash
# Count successful sends
grep -c "Welcome email sent successfully" storage/logs/laravel.log

# Count failures
grep -c "Welcome email failed" storage/logs/laravel.log

# List all mail-related entries
grep -i "UserController.*mail" storage/logs/laravel.log
```

### Render Log Monitoring

From Render dashboard:
1. Go to your service
2. Click "Logs" tab
3. Filter by: `mail` or `Mail`
4. Look for patterns

---

## Production Deployment Checklist

Before deploying to production:

- [ ] `MAIL_MAILER` is set to `smtp`, `resend`, or `postmark` (NOT `log`)
- [ ] `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` are configured
- [ ] Test email sending: `php artisan mail:test your-email@example.com`
- [ ] Check that emails arrive within 1 minute
- [ ] Verify `.env` values match your email provider
- [ ] Check logs for "Welcome email sent successfully"
- [ ] Notify your team about the email configuration

### Render Production Setup

After deploying to Render:

1. Set environment variables in Render dashboard:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_FROM_ADDRESS=your-email@gmail.com
   MAIL_FROM_NAME=Hotel Management System
   ```

2. Deploy your app

3. Test by creating a user from frontend

4. Check Render logs for confirmation

---

## Support & References

- **Gmail App Password**: https://support.google.com/accounts/answer/185833
- **Resend Docs**: https://resend.com/docs
- **Postmark Docs**: https://postmarkapp.com/developer
- **Laravel Mail Docs**: https://laravel.com/docs/mail
- **Render Docs**: https://render.com/docs

---

## Quick Reference

| Config | Local Dev | Production (Gmail) | Production (Resend) |
|--------|-----------|------------------|------------------|
| MAIL_MAILER | log | smtp | resend |
| MAIL_HOST | - | smtp.gmail.com | - |
| MAIL_PORT | - | 587 | - |
| MAIL_ENCRYPTION | - | tls | - |
| Requires App Password | - | Yes | No |
| Max emails/day | ∞ | ~500 | ✓ (plan dependent) |
| Deliverability | N/A | Good | Excellent |
| Cost | Free | Free | Paid |

---

## Version History

- **2024-10-20**: Initial guide created
- **2024-10-20**: Added Render port restrictions and troubleshooting

