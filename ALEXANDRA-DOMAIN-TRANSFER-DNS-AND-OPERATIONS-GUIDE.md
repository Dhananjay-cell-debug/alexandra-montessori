# Alexandra Montessori
## Domain Transfer, DNS, Website Launch and Ongoing Operations Guide

**Domain:** `alexandramontessori.co.uk`  
**Prepared:** 18 July 2026  
**Purpose:** One step-by-step guide for taking control of the domain, connecting the new Hostinger website without breaking email, completing launch checks, and running the website afterwards.

---

## 1. Read this first

The domain, DNS, website and email are four separate things:

| Item | What it controls | Current position |
|---|---|---|
| Domain registration | Legal/administrative control and renewal of `alexandramontessori.co.uk` | Registered through Namesco, not Hostinger |
| IPS tag | Which registrar controls a `.co.uk` domain | Currently Namesco; destination tag will be `AXIDOMAINS` |
| DNS/nameservers | Directs visitors and email to the correct providers | Controlled through `sustainable-hosting.co.uk` nameservers |
| Website hosting | Stores and runs the new WordPress website | New website is running at Hostinger |
| Email hosting | Receives email for `@alexandramontessori.co.uk` addresses | Current DNS contains Google and Namesco/Stackmail-related records |

Moving the registration to Hostinger does **not** automatically move the website or DNS. Changing the website does **not** require changing the email provider.

### The most important safety rules

1. **Start the Hostinger transfer order before Nigel changes the IPS tag.**
2. During the transfer, select **Keep current nameservers**. Do not change nameservers yet.
3. Obtain a complete DNS-zone export from Nigel before moving DNS control.
4. Never delete or replace MX, TXT, SPF, DKIM, DMARC or verification records just to connect the website.
5. Do not use passwords sent by email or WhatsApp. Use account invitations/access management where possible.
6. Take a backup before changing the Hostinger website domain, WordPress URL, files or database.
7. Do not cancel the former DNS service until the new website and email have worked correctly for at least seven days.
8. Do not repeatedly change DNS while it is propagating. A correct change may take up to 24 hours to appear everywhere.

---

## 2. Current verified position

The following was checked on 18 July 2026.

### Registration

- Domain status: active
- Current registrar: Team Blue Internet Services UK Limited / Namesco
- Registrar identifier: `NAMESCO`
- Registered: 15 May 2019
- Registry expiry: 15 May 2027
- DNSSEC delegation: not signed
- Official lookup: [Nominet RDAP record](https://rdap.nominet.uk/uk/domain/alexandramontessori.co.uk)

### Current nameservers

```text
ns1.sustainable-hosting.co.uk
ns2.sustainable-hosting.co.uk
ns3.sustainable-hosting.co.uk
ns4.sustainable-hosting.co.uk
```

These nameservers mean Childcare Marketing/their hosting supplier currently controls the active DNS zone even though Namesco is the registrar.

### Current public website records

```text
@     A       185.151.30.176
@     AAAA    2a07:7800::176
www   A       185.151.30.176
```

The old public domain returned HTTP `403` during the check.

### New Hostinger website

- Temporary/staging website: `https://alexandra.krildigital.com`
- WordPress admin: `https://alexandra.krildigital.com/wp-admin`
- Current server IPv4 observed: `31.170.167.196`
- Website response: HTTP `200`
- WordPress REST API response: HTTP `200`

**Important:** When setting the final DNS record, always copy the current IP displayed in Hostinger hPanel. Do not rely only on the IP written in this document because hosting IPs can change.

### Known public email-related records

The public records below are a useful snapshot, but they are **not a substitute for a complete DNS export** because private/unknown subdomains and DKIM selectors cannot be discovered reliably.

```text
MX  1    ASPMX.L.GOOGLE.COM
MX  5    ALT1.ASPMX.L.GOOGLE.COM
MX  5    ALT2.ASPMX.L.GOOGLE.COM
MX  10   ALT3.ASPMX.L.GOOGLE.COM
MX  10   ALT4.ASPMX.L.GOOGLE.COM
MX  15   athena.hosts.co.uk
MX  15   hermes.hosts.co.uk

TXT google-site-verification=mUHnKxKIxqPAjWt4UKcyWfzYTKIfy1J68XSX2i7QPis
TXT google-site-verification=aKK0gzkleeqeoi_1wu7OlWtGqgkI7S2ngrdBe4y97uI
TXT v=spf1 include:_spf.google.com include:spf.stackmail.com -all
TXT v=spf1 include:spf.hosts.co.uk ~all
```

There are mixed Google and Namesco/Stackmail mail records and two SPF policies. Do not try to “clean this up” during the domain transfer. Preserve the working records first, confirm the real email provider with Alexandra Montessori, and schedule a separate email-DNS audit after launch.

---

## 3. Who should own the Hostinger account?

Before paying for the transfer, confirm the Hostinger account that will receive the domain.

The best long-term arrangement is:

- The domain is registered in an account controlled by Alexandra Montessori/the business owner.
- The account uses a business-controlled email address.
- The legal registrant/contact information belongs to the client, not a developer or former marketing company.
- The client controls billing, recovery email and two-factor authentication.
- Developers receive delegated access rather than ownership of the domain.

If the intended Hostinger account is an agency/developer account, obtain written client approval and make sure the client can recover and transfer the domain without depending on one individual.

### Account-security checklist

- [ ] Confirm the correct long-term Hostinger account.
- [ ] Confirm the owner email can receive messages.
- [ ] Enable two-factor authentication.
- [ ] Save recovery codes in the client’s secure password manager.
- [ ] Add a current payment method.
- [ ] Do not share the owner password.
- [ ] Record who is responsible for domain and hosting renewal.

---

## 4. Immediate action: protect the IPS-tag sequence

The email asking Nigel to change the tag has already been sent. Hostinger’s official order is:

1. Start the transfer in Hostinger.
2. Pay and set up the transfer order.
3. Confirm Hostinger’s transfer email.
4. Then ask the current registrar/provider to change the tag to `AXIDOMAINS`.

### Send this short follow-up to Nigel now

> Hi Nigel,  
>   
> Please hold the IPS-tag change until we confirm that the incoming transfer order has been initiated and confirmed in Hostinger. We will notify you as soon as Hostinger shows the transfer ready for the `AXIDOMAINS` tag.  
>   
> Please also provide a complete export of the existing DNS zone and keep the current DNS/nameservers active during the transfer and for seven days after the agreed DNS cutover.  
>   
> Kind regards,

### If Nigel has already changed the tag

Do not panic and do not change nameservers.

1. Log in to Hostinger immediately.
2. Open **Domains → Transfers**.
3. Start or finish the incoming transfer order for `alexandramontessori.co.uk`.
4. Complete payment/order setup if requested.
5. Confirm any transfer email.
6. If Hostinger does not recognise the transfer, open Hostinger support chat and say:

> The IPS tag for `alexandramontessori.co.uk` may already have been changed to `AXIDOMAINS`. Please attach it to the incoming `.co.uk` transfer order in this account without changing the existing nameservers.

Do not ask Nigel to change the tag back unless Hostinger support specifically instructs it.

---

## 5. Stage A — Obtain the DNS handover

Ask Nigel/Childcare Marketing for one final DNS-only handover.

### What must be provided

Request:

- A complete DNS-zone export in BIND, CSV or equivalent format.
- Every record name/host.
- Record type.
- Record value/content.
- TTL.
- MX priority.
- SRV priority, weight and port, if any.
- All subdomains.
- All email authentication records, including every DKIM selector.
- SPF, DMARC and verification records.
- CAA records, if any.
- Confirmation of whether any email, calendar, booking, telephone or third-party service relies on their DNS.
- Confirmation that any registrar/transfer lock will be disabled when the IPS tag is changed.
- Confirmation that the old zone will remain active for seven days after cutover.

If they cannot export the zone, request screenshots of **every page** of the DNS manager. A partial screenshot of only A and MX records is not enough.

### What is not required from them

We do not require:

- Their website files.
- Their proprietary website platform.
- Their hosting account.
- Their old website IP to be transferred.
- An EPP/auth code; `.co.uk` transfers use an IPS tag.
- Access to their staff email.
- An email migration.

### Save the handover safely

- [ ] Save the original export without editing it.
- [ ] Save the email in which it was supplied.
- [ ] Record the date and person who supplied it.
- [ ] Create a second working copy for Hostinger.
- [ ] Do not post the zone publicly.

---

## 6. Stage B — Start the `.co.uk` transfer in Hostinger

Official Hostinger guidance: [Transfer a .uk domain to Hostinger](https://www.hostinger.com/support/1771588-how-to-transfer-a-uk-domain-to-hostinger/)

### Click-by-click

1. Sign in to the correct Hostinger account.
2. Open **Domains** in the left menu.
3. Choose **Transfer domain** or open the **Transfers** page.
4. Enter:

```text
alexandramontessori.co.uk
```

5. Click **Transfer**.
6. Complete the payment if Hostinger asks for it.
7. Complete the order setup.
8. If asked about nameservers, select **Keep current nameservers**.
9. Do **not** select Hostinger nameservers yet.
10. Check the owner email inbox and spam folder.
11. Open Hostinger’s transfer-confirmation email and approve it.
12. Return to **Domains → Transfers** and confirm that the transfer is waiting for the IPS tag or is in progress.
13. Take a screenshot of the status.

If Hostinger reports that the domain is locked, ask Nigel/Namesco to disable the transfer lock. Unlocking the registration is separate from changing nameservers; the four existing nameservers must remain in place.

### Now authorise Nigel

Only after the Hostinger order is ready, send:

> Hi Nigel,  
>   
> The incoming transfer order is now active and confirmed at Hostinger. Please proceed with changing the Nominet IPS tag for `alexandramontessori.co.uk` to:  
>   
> `AXIDOMAINS`  
>   
> Please confirm when completed. The existing nameservers and DNS zone must remain unchanged and active.  
>   
> Kind regards,

### Possible Namesco fee

Namesco’s published transfer-away instructions currently state that changing the tag through its panel may cost £10 plus VAT. Nigel may include this or an administration charge in his quotation.

Official Namesco guidance: [Transfer a domain away from Namesco](https://www.names.co.uk/support/articles/how-to-transfer-a-domain-name-away-from-us/)

### Expected transfer behaviour

- Hostinger may show several intermediate statuses.
- Namesco states that a `.co.uk` tag transfer may take up to 48 hours.
- Registration transfer should not interrupt the website or email when current nameservers are retained.
- Do not make repeated transfer orders.
- Do not start a second domain transfer in another Hostinger account.

---

## 7. Stage C — Verify the registration transfer

Wait until Hostinger says the transfer is complete.

### Check in Hostinger

1. Open **Domains → Domain portfolio**.
2. Find `alexandramontessori.co.uk`.
3. Click **Manage**.
4. Confirm:

- [ ] Status is active.
- [ ] The domain is in the correct Hostinger account.
- [ ] Registrant/contact details are correct.
- [ ] Owner email is accessible.
- [ ] Expiry date is sensible.
- [ ] Auto-renewal is enabled.
- [ ] The payment method is current.
- [ ] Domain lock is enabled after transfer completion.
- [ ] The nameservers are still the four `sustainable-hosting.co.uk` values.

### Check at Nominet

Open:

`https://rdap.nominet.uk/uk/domain/alexandramontessori.co.uk`

Confirm that:

- [ ] Status is active.
- [ ] Registrar is no longer `NAMESCO`.
- [ ] Nameservers are still the old values until the planned DNS cutover.

If Hostinger shows the domain but Nominet still shows Namesco, wait and check again. Do not change the tag repeatedly.

---

## 8. Stage D — Prepare the Hostinger DNS zone

Do this before changing nameservers.

Official guidance: [Manage DNS records at Hostinger](https://www.hostinger.com/support/1583249-how-to-manage-dns-records-at-hostinger/)

### Open the DNS manager

1. In Hostinger, open **Domains → Domain portfolio**.
2. Click **Manage** beside `alexandramontessori.co.uk`.
3. Open **DNS / Nameservers**.
4. Open the **DNS records** tab.

The exact labels can change. Do not worry if Hostinger calls it **DNS Zone Editor**.

### Build the new zone

1. Keep the original export open beside Hostinger.
2. Recreate every non-website record exactly.
3. Preserve:

- MX records and priorities.
- TXT records.
- SPF.
- DKIM selectors.
- DMARC.
- Google verification.
- Any Microsoft, booking, calendar, telephone or other service records.
- Subdomains.

4. For the website records, use the Hostinger website’s current target:

```text
@     A       [IPv4 shown in Hostinger Plan Details]
www   A       [same IPv4 shown in Hostinger Plan Details]
```

5. Do not copy the old website IP `185.151.30.176` into the new active website records.
6. Do not create an AAAA record unless Hostinger provides a specific IPv6 address.
7. If Hostinger created default/conflicting records automatically, compare them carefully before deleting anything.
8. Take screenshots of the complete Hostinger zone.

### Find the correct Hostinger IP

1. Open **Websites**.
2. Find the Alexandra website.
3. Click **Dashboard**.
4. Open **Plan details** or **Website details**.
5. Copy the IP shown there.

The staging server currently resolves to `31.170.167.196`, but the hPanel value at the time of cutover is authoritative.

### Email warning

Changing nameservers without recreating the mail records can stop incoming email. Hostinger’s own guidance warns that switching nameservers can configure Hostinger Email defaults and requires third-party MX/SPF/DKIM/DMARC records to be preserved.

Do not switch nameservers until:

- [ ] The complete export has been received.
- [ ] Every record has been copied.
- [ ] The business has confirmed which email provider is actually used.
- [ ] Someone can test an `@alexandramontessori.co.uk` mailbox during cutover.

---

## 9. Stage E — Prepare the final WordPress website

The working WordPress site is currently at `alexandra.krildigital.com`. It must be connected to `alexandramontessori.co.uk` before public DNS is switched.

### Backup first

Create and download:

- [ ] Full website-files backup.
- [ ] Full database backup.
- [ ] Hostinger-generated backup if available.
- [ ] Screenshot of the current website settings.
- [ ] Screenshot of the current WordPress Site URL and Home URL.

The project also has a verified production backup/release from 17 July 2026. Do not overwrite or delete it.

### Do not blindly click “Change domain”

Hostinger’s **Change domain** flow can warn that email accounts, subdomains or Hostinger backups associated with the old domain will be removed. If you see that warning, stop and read it carefully.

The preferred safe approach is:

1. Keep `alexandra.krildigital.com` available as the rollback/staging copy.
2. Add `alexandramontessori.co.uk` to the hosting plan as the destination website.
3. Copy/clone the verified WordPress website to the final domain or perform a controlled server-side migration.
4. Update WordPress URLs and serialized database references safely.
5. Test the final domain against the Hostinger server before the public DNS switch.

### Hostinger screens you may use

1. Open **Websites**.
2. Locate the hosting plan.
3. Choose **Add website**.
4. Select the transferred domain `alexandramontessori.co.uk`.
5. If Hostinger offers **Copy Website** for the WordPress installation, use the staging website as the source and the final-domain website as the destination.

**Important:** Copy Website can overwrite the destination. Confirm that the destination is empty and take a backup first.

### Recommended stop point

When Hostinger asks you to choose between **Change domain**, **Add website**, **Copy website**, or a temporary domain:

1. Take a screenshot.
2. Do not confirm a destructive warning.
3. Return to this Codex project and ask for the exact next click.

The workspace already contains the required server, WordPress and database access for a controlled technical migration. You should retain control of the Hostinger account while the server-side work is performed.

### Pre-cutover website checks

Before DNS changes, verify:

- [ ] Homepage works.
- [ ] Nursery directory and all three nursery pages work.
- [ ] Blog archive and articles work.
- [ ] Events work.
- [ ] Careers/vacancies work.
- [ ] Contact page works.
- [ ] `/wp-admin` login works.
- [ ] Media loads over HTTPS.
- [ ] No page redirects back to the staging domain unexpectedly.
- [ ] No mixed-content warning.
- [ ] WordPress Site URL and Home URL will use `https://alexandramontessori.co.uk`.
- [ ] A full backup exists.

---

## 10. Stage F — Schedule the DNS cutover

Choose a quiet time when:

- The business owner is available.
- Someone can test the business email inbox.
- You have at least two uninterrupted hours.
- Hostinger support is reachable.
- The new site has passed preview testing.
- Nigel has confirmed the old DNS will remain available for seven days.

Avoid changing DNS immediately before a weekend, public holiday, important admissions campaign or event.

### Final go/no-go checklist

- [ ] Hostinger transfer complete.
- [ ] Correct owner/contact details.
- [ ] Auto-renewal enabled.
- [ ] Complete DNS export saved.
- [ ] Full DNS zone recreated in Hostinger.
- [ ] New website tested.
- [ ] Files and database backed up.
- [ ] Correct Hostinger IP confirmed.
- [ ] Email provider confirmed.
- [ ] Mailbox tester available.
- [ ] Previous nameservers recorded.
- [ ] Rollback plan understood.

If any box is unchecked, postpone the nameserver change.

---

## 11. Stage G — Change to Hostinger nameservers

Official guidance: [Point a domain to Hostinger](https://www.hostinger.com/support/1863967-how-to-point-a-domain-to-hostinger/)

### Click-by-click

1. Sign in to Hostinger.
2. Open **Domains → Domain portfolio**.
3. Click **Manage** next to `alexandramontessori.co.uk`.
4. Open **DNS / Nameservers**.
5. Select **Change nameservers** or **Use Hostinger nameservers**.
6. Use the exact nameservers shown for this domain in hPanel.
7. Do not copy example nameservers from a blog or this guide.
8. Confirm once.
9. Take a screenshot showing the new values and time.

### What happens next

- Different internet providers update at different times.
- Some visitors may still reach the old host while others reach Hostinger.
- This mixed period is normal.
- Hostinger says propagation can take up to 24 hours.
- SSL may take time to issue after the new DNS is detected.
- Do not repeatedly change records because one phone still shows the old result.

### During propagation

Check using:

- A phone on Wi-Fi.
- The same phone on mobile data.
- A desktop browser.
- A private/incognito window.
- An external DNS propagation checker.

Record:

- Time of nameserver change.
- Time Hostinger detected the domain.
- Time HTTPS began working.
- Time website and email tests passed.

---

## 12. Stage H — Launch validation

Complete every section below.

### DNS and SSL

- [ ] `alexandramontessori.co.uk` reaches Hostinger.
- [ ] `www.alexandramontessori.co.uk` reaches Hostinger.
- [ ] One version redirects consistently to the preferred version.
- [ ] HTTPS certificate is valid.
- [ ] Browser shows no certificate warning.
- [ ] Nameservers are the exact Hostinger values.
- [ ] MX and TXT records match the approved zone.

### Public website

- [ ] Homepage.
- [ ] About.
- [ ] Curriculum.
- [ ] Funded childcare.
- [ ] Nurseries directory.
- [ ] Hammersmith page.
- [ ] Heston page.
- [ ] Hounslow page.
- [ ] Blog archive and an article.
- [ ] Events archive and an event.
- [ ] Careers and a vacancy.
- [ ] Testimonials.
- [ ] Contact page.
- [ ] Privacy/cookie/legal pages.
- [ ] Mobile menu.
- [ ] Images and downloadable documents.
- [ ] No link unexpectedly opens the old Childcare Marketing platform.

### WordPress

- [ ] `https://alexandramontessori.co.uk/wp-admin` works.
- [ ] Alexandra Content Manager account works.
- [ ] Dashboard shows no fatal error.
- [ ] Website Content can be saved.
- [ ] Media Library works.
- [ ] Submissions inbox works.
- [ ] No public page redirects to `alexandra.krildigital.com`.

### Forms

Use client-approved test details, not real applicant data.

- [ ] Contact form submits.
- [ ] Book-a-visit/contact routing reaches the correct branch.
- [ ] Availability form submits.
- [ ] Careers form submits with a harmless test PDF/DOC/DOCX.
- [ ] Submission appears in WordPress.
- [ ] New status/first-open behaviour works.
- [ ] Email arrives in inbox or spam.
- [ ] Branch routing is correct.
- [ ] Thirty-minute digest is observed if applicable.
- [ ] Reply action addresses only the submitted customer email.
- [ ] Private CV download works only while logged in.
- [ ] Test records are documented before any approved cleanup.

### Business email

Test from an unrelated external mailbox:

- [ ] Send to the main `@alexandramontessori.co.uk` inbox.
- [ ] Send to each branch inbox used by the website.
- [ ] Confirm delivery in inbox or spam.
- [ ] Reply from the business mailbox to the external sender.
- [ ] Confirm the reply arrives.
- [ ] Confirm no SPF/DKIM/DMARC rejection or warning.
- [ ] Confirm website form notifications arrive.

Do not assume email works merely because the website loads.

### SEO and external services

- [ ] `https://alexandramontessori.co.uk/robots.txt`
- [ ] `https://alexandramontessori.co.uk/sitemap.xml`
- [ ] Canonical URLs use the final domain.
- [ ] Google Search Console verification remains valid.
- [ ] Analytics/tag manager still receives data, if used.
- [ ] Social-sharing image URLs use the final domain.
- [ ] Old important URLs redirect to the most relevant new pages.

---

## 13. Emergency and rollback guide

### If the website fails but email works

Do not modify MX/TXT records.

Check:

1. Hostinger website is assigned to the final domain.
2. Root `@` A record matches Hostinger’s current IP.
3. `www` points to the same website.
4. No old AAAA record is sending IPv6 visitors to the old host.
5. SSL is active.
6. WordPress Site URL/Home URL use the correct domain.
7. Hostinger error logs.

Keep the staging site available while this is fixed.

### If email fails but the website works

Do not modify the website A record.

1. Compare every MX and TXT record with the original export.
2. Check missing DKIM/DMARC/SPF records.
3. Check MX priorities.
4. Confirm which provider owns the mailbox.
5. Use Hostinger DNS History to restore the last known-good zone if appropriate.
6. If necessary and the old DNS is still active, restore the previous nameservers while the zone is corrected.

### If both website and email fail

1. Check Nominet/Hostinger nameserver values.
2. Check whether the Hostinger DNS zone exists.
3. Confirm nameserver spelling.
4. Restore the previous nameservers if the former provider has confirmed the old zone is still active.
5. Contact Hostinger support with the exact change time and screenshots.

Previous nameservers:

```text
ns1.sustainable-hosting.co.uk
ns2.sustainable-hosting.co.uk
ns3.sustainable-hosting.co.uk
ns4.sustainable-hosting.co.uk
```

### If the IPS transfer stalls

1. Check **Domains → Transfers** in Hostinger.
2. Check owner email/spam for confirmation.
3. Ask Nigel for written confirmation that the tag is exactly `AXIDOMAINS`.
4. Check Nominet RDAP for the current registrar.
5. Contact Hostinger support.
6. Do not request multiple tag changes at the same time.

---

## 14. Seven-day post-launch plan

### Day 0: cutover

- Complete all launch checks.
- Record issues and exact times.
- Keep staging and old DNS available.
- Do not delete anything.

### Day 1

- Recheck website from multiple networks.
- Recheck SSL.
- Test incoming and outgoing email again.
- Review WordPress submissions and notification jobs.
- Check Hostinger error logs.

### Days 2–3

- Confirm DNS has propagated widely.
- Check Search Console and analytics.
- Test forms again.
- Check mobile navigation and key pages.
- Confirm staff can use WordPress.

### Day 7

- Confirm there have been no email complaints.
- Confirm new enquiries appear in WordPress and the correct inboxes.
- Download a fresh post-launch backup.
- Save a current DNS-zone export.
- Tell Nigel the migration is complete and no further DNS operation is required.
- Keep staging for at least another seven days unless storage makes that impossible.

### After 14 days

- Decide whether to retain staging as a protected maintenance copy.
- Remove obsolete access held by the former provider.
- Do not delete evidence, backups or real submissions.
- Review whether old DNS/hosting services can safely be cancelled.

---

## 15. Using the website after launch

### WordPress login

Final login address:

`https://alexandramontessori.co.uk/wp-admin`

The normal client menu contains:

1. Dashboard
2. Website Content
3. Nurseries
4. Events
5. Blog
6. Testimonials
7. Jobs
8. Media Library
9. Site Settings
10. Submissions
11. Profile

Technical menus such as Plugins, Users, Themes and system pages are intentionally hidden from the client role.

### Publishing content

- **Blog:** A published article with a title and body appears in the blog system. Images have a controlled fallback.
- **Events:** A complete published event appears automatically as upcoming or past.
- **Jobs:** A job must be published, open and complete before it becomes public.
- **Nurseries:** A complete published nursery creates its directory card, page and form-routing choice.
- **Testimonials:** Complete published testimonials appear in the testimonials archive; the homepage shows a controlled subset.
- **Media Library:** Store website images and documents here. Do not upload applicant CVs to Media.
- **Site Settings:** Controls shared contact/brand/footer information. Confirm facts with the client before changing them.

### Handling submissions

Open **Submissions** in WordPress.

Daily actions:

1. Review records marked **New**.
2. Confirm the nursery/type.
3. Assign an owner if required.
4. Set priority/status.
5. Add internal notes where useful.
6. Set a follow-up date.
7. Use the locked reply action so replies go only to the submitted customer email.
8. Treat CVs and personal data as confidential.

Do not export or delete applicant/customer data unless the client’s retention policy permits it.

---

## 16. Routine maintenance schedule

### Every working day

- [ ] Check the Submissions inbox.
- [ ] Check business email and spam.
- [ ] Review urgent/follow-up items.
- [ ] Report website or email problems with screenshots and exact times.

### Every week

- [ ] Open the homepage and each nursery page.
- [ ] Submit one harmless contact-form test if the client approves.
- [ ] Confirm notification email arrives.
- [ ] Check Hostinger backup status.
- [ ] Check available storage.
- [ ] Review WordPress for failed jobs or visible errors.
- [ ] Confirm HTTPS is valid.

### Every month

- [ ] Download a fresh files-and-database backup.
- [ ] Export the current DNS zone.
- [ ] Confirm Hostinger billing and auto-renewal status.
- [ ] Review WordPress admin accounts and remove unnecessary access.
- [ ] Review broken links and outdated content.
- [ ] Check Search Console for indexing/security issues.
- [ ] Check forms from mobile and desktop.
- [ ] Review storage of private applications/CVs.

Do not install or update themes/plugins blindly. Back up first and test custom website behaviour after every technical update.

### Every three months

- [ ] Test restoring a backup in a safe environment.
- [ ] Review contact details, nursery details and opening information.
- [ ] Review open jobs and past events.
- [ ] Confirm email SPF/DKIM/DMARC health.
- [ ] Test keyboard/mobile accessibility.
- [ ] Review privacy and data-retention compliance.
- [ ] Confirm former staff/contractors no longer have access.

### Every year

- [ ] Confirm domain auto-renewal well before 15 May.
- [ ] Confirm hosting-plan renewal date and payment method.
- [ ] Confirm owner/contact/recovery email details.
- [ ] Review two-factor authentication and recovery codes.
- [ ] Save an annual full backup and DNS export.
- [ ] Review SSL, privacy policy, cookies and legal pages.
- [ ] Confirm the business still controls the registrar and hosting accounts.

---

## 17. Work still required before formal client handover

The website is technically deployed, but these items should be completed or decided:

- [ ] Controlled real-delivery UAT for Contact, Availability and Careers.
- [ ] Confirm final global telephone number and address.
- [ ] Physical/narrow-mobile and independent-browser check.
- [ ] Decide what to do with two legacy resume PDFs; do not delete without approval.
- [ ] Confirm whether four existing Contact records are disposable test data.
- [ ] Complete or keep hidden the Support Assistant job description.
- [ ] Complete or unpublish the fourth incomplete testimonial.
- [ ] Obtain a written data-retention period for enquiries, applications, messages and exports.
- [ ] Confirm final social links and remaining official copy.
- [ ] Optionally configure the one-minute Hostinger hPanel cron after host-level access is available.

---

## 18. Information to keep in the permanent handover pack

Keep these together in a client-controlled secure location:

- Domain registrar and renewal date.
- Hostinger owner email and account reference.
- Nameservers.
- DNS-zone export.
- Website IP.
- Hosting renewal date.
- WordPress admin URL.
- List of authorised administrators.
- Backup dates and locations.
- Email-provider support details.
- Google Search Console/analytics ownership.
- Supplier and developer contact information.
- Cutover record and screenshots.
- Written data-retention policy.

Do not place passwords, private keys, database credentials, SMTP passwords or recovery codes inside this guide.

---

## 19. Quick “what do I do next?” checklist

Complete these in order:

1. [ ] Confirm the Hostinger account is the correct long-term owner account.
2. [ ] Send Nigel the “hold the IPS-tag change” follow-up.
3. [ ] Ask Nigel for the complete DNS-zone export and seven-day DNS continuity.
4. [ ] Start and pay for the Hostinger `.co.uk` transfer.
5. [ ] Select **Keep current nameservers**.
6. [ ] Confirm Hostinger’s transfer email.
7. [ ] Check **Domains → Transfers** shows ready/in progress.
8. [ ] Tell Nigel to change the tag to `AXIDOMAINS`.
9. [ ] Wait for the domain to appear as active in Hostinger.
10. [ ] Enable auto-renewal, correct contacts and domain lock.
11. [ ] Recreate the complete DNS zone in Hostinger without changing nameservers.
12. [ ] Back up and prepare the WordPress site for the final domain.
13. [ ] Preview and test the final site.
14. [ ] Schedule a quiet cutover window with an email tester available.
15. [ ] Change to the exact Hostinger nameservers shown in hPanel.
16. [ ] Test website, WordPress, forms, business email, SSL and SEO.
17. [ ] Monitor for seven days.
18. [ ] Save final backups and DNS export.
19. [ ] Remove former-provider access only after everything is stable.
20. [ ] Follow the ongoing maintenance schedule.

---

## 20. Official references

- [Nominet RDAP record for the domain](https://rdap.nominet.uk/uk/domain/alexandramontessori.co.uk)
- [Hostinger: Transfer a .uk domain](https://www.hostinger.com/support/1771588-how-to-transfer-a-uk-domain-to-hostinger/)
- [Hostinger: Point a domain to Hostinger](https://www.hostinger.com/support/1863967-how-to-point-a-domain-to-hostinger/)
- [Hostinger: Manage DNS records](https://www.hostinger.com/support/1583249-how-to-manage-dns-records-at-hostinger/)
- [Hostinger: Add a website](https://www.hostinger.com/support/1583214-how-to-add-a-website-in-hostinger/)
- [Hostinger: Copy a WordPress website to another domain](https://www.hostinger.com/support/6601521-how-to-copy-a-wordpress-website-to-another-domain-name/)
- [Namesco: Transfer a domain away](https://www.names.co.uk/support/articles/how-to-transfer-a-domain-name-away-from-us/)
- [Namesco: Change a Nominet tag](https://www.names.co.uk/support/articles/how-to-change-a-nominet-tag/)

---

**Final rule:** Registration first, DNS copied second, website prepared third, nameservers changed last. This order is what protects the business email and keeps a rollback route available.
