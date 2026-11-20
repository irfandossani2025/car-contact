# Deploying Contact Scan on Plesk (no SSH)

The server has **no SSH** and **Node 17**, so:
- CSS/JS is built on your computer (`npm run build`) and the `public/build` folder is **committed to git**.
- Everything else is done in **Plesk → your subdomain → Laravel Toolkit**.

---

## 0. Before every deploy (on your computer)

```bash
npm run build
```

```bash
git add -A && git commit -m "Build assets" && git push
```

## 1. Create the subdomain and database (one time)

1. **Plesk → Websites & Domains → Add Subdomain**, e.g. `park.yourdomain.com`.
2. **SSL/TLS Certificates → Let's Encrypt**: secure the subdomain. HTTPS is **required** for phone notifications and Telegram.
3. **Databases → Add Database**: MySQL. Note the database name, user and password.
4. **PHP Settings** for the subdomain: PHP **8.3 or newer**. Make sure these extensions are on: `gd`, `mbstring`, `openssl`, `curl`, `pdo_mysql`, `intl`, and `gmp` (or `bcmath`) if available.
5. **Mail → Create Email Address**, e.g. `alerts@yourdomain.com`. Alert emails are sent from this mailbox.

## 2. Install the app with Laravel Toolkit (one time)

1. **Laravel Toolkit → Install Application → From Git repository**, and paste your GitHub repo URL.
   For a private repo, add the deploy key that Plesk shows to GitHub → repo → Settings → Deploy keys.
2. Set **document root** to `/public` (the Toolkit usually does this for you).
3. Under **Deployment** settings:
   - ✅ Composer install (`--no-dev`)
   - ✅ Run migrations (`php artisan migrate --force`)
   - ❌ **npm / Node build: turn it off.** The built files are already in git.
   - ✅ Clear and rebuild cache (`optimize`)

## 3. Fill in `.env`

> ⚠️ On this server the Laravel Toolkit's **Edit .env** button did not save. Edit the file with
> **Plesk → Files → `carcontact.techittechnologies.com/.env` → Code Editor → Save** instead.
> After any `.env` change, run `optimize` in the Artisan tab, otherwise the old cached settings stay in use.
> Check the result with `contactscan:status` (prints yes/no only, never secrets).

```dotenv
APP_NAME="Contact Scan"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://park.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=localhost          # or your Plesk mail server hostname
MAIL_PORT=587
MAIL_SCHEME=null             # use "smtps" with port 465 instead if 587 fails
MAIL_USERNAME=alerts@yourdomain.com
MAIL_PASSWORD=mailbox_password
MAIL_FROM_ADDRESS=alerts@yourdomain.com
MAIL_FROM_NAME="Contact Scan"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

# Web push keys: generate on your computer with: php artisan contactscan:vapid
VAPID_SUBJECT="mailto:alerts@yourdomain.com"
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...

# Telegram (see step 5)
TELEGRAM_BOT_TOKEN=...
TELEGRAM_BOT_USERNAME=YourBotName_bot
TELEGRAM_WEBHOOK_SECRET=any-long-random-string-you-make-up

# Cloudflare Turnstile (optional, see step 6)
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

After each deploy that adds database changes, and the first time, run in **Laravel Toolkit → Artisan**:
- `key:generate` (first install only)
- `migrate --force`
- `optimize`

> ⚠️ Generate the VAPID keys **once** and never change them. Changing them disconnects every phone that turned on notifications.

## 4. Scheduler (one time)

Laravel Toolkit → **Scheduled Tasks / Scheduler** → turn it **on**. It runs `php artisan schedule:run` every minute.
This deletes alerts older than 90 days. Alerts themselves are sent instantly and don't depend on it.

If the Toolkit has no scheduler switch, go to **Plesk → Scheduled Tasks → Add Task → Run a PHP script**:
script `httpdocs/<app folder>/artisan`, arguments `schedule:run`, run **every minute**.

## 5. Telegram bot (free, about 3 minutes)

1. In Telegram, open **@BotFather** → `/newbot` → choose a name and a username ending in `bot`.
2. Copy the **token** into `TELEGRAM_BOT_TOKEN` and the username (without @) into `TELEGRAM_BOT_USERNAME`.
3. Laravel Toolkit → Artisan → run `optimize`, then `contactscan:telegram-webhook`.
   It should say *Telegram webhook set to https://park.yourdomain.com/telegram/webhook*.
4. Optional: in BotFather, use `/setuserpic` and `/setdescription` to brand the bot.

## 5b. SMS via BulkSMS (paid per message)

1. In BulkSMS → Settings → **API Tokens**, copy the **Token ID** and **Token Secret**.
2. In the Laravel Toolkit `.env` editor set:
   ```dotenv
   BULKSMS_TOKEN_ID=your-token-id
   BULKSMS_TOKEN_SECRET=your-token-secret
   # or, instead of the two lines above:
   # BULKSMS_BASIC_AUTH=the-basic-auth-string
   BULKSMS_FROM=            # optional approved sender ID
   CS_SMS_DEFAULT_COUNTRY_CODE=971   # optional: lets owners type 050... instead of +97150...
   CS_SMS_DAILY_LIMIT=10    # max SMS per owner per day (cost guard)
   ```
3. Artisan → `optimize`.
4. Owners turn SMS on from the dashboard. They confirm their number with a 6-digit SMS code first.
   Alert texts are kept to one SMS (under 160 characters) with a short reply link.

## 5c. WhatsApp via Meta Cloud API

- Webhook URL: `https://carcontact.techittechnologies.com/whatsapp/webhook`, verify token = `WHATSAPP_VERIFY_TOKEN`, subscribe to the **messages** field.
- Message template `car_alert` (Utility, English) with body variables {{1}} car, {{2}} reason, {{3}} note,
  and three quick-reply buttons in this order: *On my way (5 min)*, *Car moved*, *Can't come now*.
- `.env`: `WHATSAPP_ACCESS_TOKEN` (permanent System User token), `WHATSAPP_PHONE_NUMBER_ID`,
  `WHATSAPP_BUSINESS_NUMBER` (+international), `WHATSAPP_APP_SECRET` (App settings → Basic). Then `optimize`.
- Owners tap **Connect WhatsApp** → WhatsApp opens with `CONNECT <code>` → Send. Replies come back as button taps.

## 6. Cloudflare Turnstile (free bot protection, optional but recommended)

1. dash.cloudflare.com → **Turnstile → Add site** → add your subdomain → Widget mode: *Managed*.
2. Put the Site key and Secret key into `.env`, then run `optimize`.
   Your domain does **not** need to use Cloudflare DNS for this.

## 7. Test it

1. Open `https://park.yourdomain.com`, sign up, and download the sticker PDF.
2. On your phone, open the dashboard → **Turn on for this device** (iPhone: Add to Home Screen first), then tap **Send test**.
3. Connect Telegram.
4. Scan the QR code with a different phone (or the same one) and send an alert. You should get push + Telegram + email.
5. Tap **On my way** in the notification. The scanner's page shows your reply within a few seconds.

## Updating later

1. On your computer: make changes, `npm run build`, commit, `git push`.
2. Plesk → Laravel Toolkit → **Deploy** (or Pull + run deployment).

## Troubleshooting

| Problem | Fix |
|---|---|
| Settings change has no effect | Run `optimize` in Artisan (settings are cached). Check with `contactscan:status`. |
| `Specified key was too long` on migrate | Server runs MariaDB 10.1. Already handled in code (191-char strings); pull the latest and deploy. |
| Page is unstyled | `public/build` wasn't committed. Run `npm run build` locally, then commit and push. |
| 500 error | Toolkit → Logs, or open `storage/logs/laravel.log` in File Manager. Run `optimize:clear`. |
| No emails | Check the MAIL_* settings. Try port 465 + `MAIL_SCHEME=smtps`. Check the mailbox password. |
| Phone notifications don't arrive | The site must be HTTPS. VAPID keys must be set. iPhone needs Add to Home Screen and iOS 16.4+. |
| Telegram "Connect" does nothing | Run `contactscan:telegram-webhook` again and check that the bot token is right. |
| Links in emails point to http:// | Set `APP_URL=https://...` and `APP_ENV=production`, then run `optimize`. |
