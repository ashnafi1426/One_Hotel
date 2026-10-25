# Hotel Settings Tab Implementation

Added a fifth tab to the Admin Profile page allowing administrators to configure hotel information (name, contact details, address, hours, currency, timezone). The settings are loaded on mount and saved through a dedicated service endpoint.

**Watch for:** Language store translation keys won't exist yet (confirmed), hotelSettings reactive defaults to empty strings which may cause API errors if backend expects null (likely), no error handling for API failures in loadHotelSettings (confirmed).

**Verdict**: NEEDS_CHANGES

## High-level view

The new tab follows the existing four-tab pattern: rounded-3xl card wrapper, matching Tailwind input classes, separate loading state. The hotelSettings reactive object holds 12 fields initialized with defaults (empty strings for most fields, 'ETB' for currency, 'Africa/Addis_Ababa' for timezone). The form submit handler calls saveHotelSettings which invokes the service's updateHotelSettings method. The service module defines a HotelSettings interface and exposes getHotelSettings/updateHotelSettings via axios. Building2 icon imported from lucide-vue-next and added to the localizedTabs computed.

The loadHotelSettings function doesn't surface errors to the user—it logs and silences them, so if the backend endpoint doesn't exist yet or returns 404, the form renders with empty fields and no indication something failed. The saveHotelSettings function does show an alert on failure, but loadHotelSettings failing silently means the admin won't know whether the feature is broken or the hotel simply has no settings saved yet.

<details>
<summary>Issues (3)</summary>

1. **Silent load failure** — loadHotelSettings catches errors but doesn't inform the user. If the backend endpoint doesn't exist or returns 404/500, the form shows empty fields with no warning. Add an alert or toast notification when the load fails so admins know the feature is unavailable.

2. **Missing translation keys** — The form references ~15 new translation keys (hotel_name, hotel_email, front_desk_hours, etc.) that don't exist in the language store. The languageStore.t() calls fall back to English defaults, but this breaks localization for Amharic users. Add these keys to the translation files.

3. **Empty strings vs null** — hotelSettings fields default to empty strings, but some backends expect null for unset values. If the backend validates or normalizes these fields, sending `phone: ""` instead of `phone: null` could cause validation errors. Verify backend expectations or adjust defaults.

</details>

<details>
<summary>Details</summary>

## Service layer and type alignment

The hotelSettingsService module exports a HotelSettings interface with 12 string fields and one optional numeric id. The interface matches the form's v-model bindings: name, email, reservation_email, phone, reservation_phone, address, city, country, front_desk_hours, dining_hours, currency, timezone. The service wraps axios.get and axios.put, extracting data from response.data.data with a fallback to response.data (handles both Laravel resource and plain JSON responses).

The getHotelSettings and updateHotelSettings methods have correct TypeScript signatures. updateHotelSettings accepts Partial<HotelSettings> which allows saving a subset of fields, though the current implementation sends all 12 fields on every save.

## Form structure and field coverage

The Hotel Settings tab renders 12 form fields in the same layout pattern as the Personal Info tab: full-width name field at the top, 2-column grids for related pairs (email/reservation_email, phone/reservation_phone, city/country, front_desk_hours/dining_hours, currency/timezone), and a full-width address field. All inputs use the established Tailwind classes: `w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition`.

Only the name field is marked required in the template. The currency and timezone fields use select dropdowns with reasonable defaults (ETB, USD, EUR for currency; Africa/Addis_Ababa and several other zones for timezone). The form submit handler prevents default and calls saveHotelSettings directly—no intermediate validation beyond the browser's required-field check.

## Reactive state and loading isolation

The hotelSettings ref is initialized with defaults: empty strings for text fields, 'ETB' for currency, 'Africa/Addis_Ababa' for timezone. The hotelSettingsLoading ref is separate from the shared loading ref used by other tabs, so a hotel settings save operation doesn't disable other tab actions (the profile photo upload button, the personal info save button, etc.). This is correct—each tab's async operation is independent.

The loadHotelSettings function overwrites the entire hotelSettings object rather than patching individual fields, which prevents stale data if the backend returns fewer fields than expected. The fallback values (empty string, 'ETB', 'Africa/Addis_Ababa') match the initial ref defaults, so a fresh installation or missing backend endpoint leaves the form in the same state as before the API call.

## Tab registration and icon import

The Building2 icon is imported from lucide-vue-next alongside the other icons. The localizedTabs computed adds a fifth entry: `{ id: 'hotel-settings', label: languageStore.t('hotel_settings', 'Hotel Settings'), icon: Building2 }`. The tab id matches the v-if condition on the form section (`v-if="activeTab === 'hotel-settings'"`), so clicking the tab button correctly shows the form.

The tab button loop in the sidebar already handles arbitrary tab counts, so adding a fifth tab doesn't break the layout or navigation. The sidebar uses `v-for="tab in localizedTabs"` without hardcoded tab ids, which is the right pattern for extensibility.

## Lifecycle and data fetching

The onMounted hook calls loadProfile, loadStats, and now loadHotelSettings. All three run in parallel without awaiting each other, so a slow or failing hotel settings fetch doesn't block the profile and stats data. The loadHotelSettings function sets hotelSettingsLoading to true before the axios call and false in the finally block, which is correct—though the UI doesn't currently show a loading spinner for the hotel settings form, so the user won't see the loading state.

If the backend endpoint doesn't exist yet (likely, given the user's complaint about a 404 on /api/admin/profile/hotel-settings), the loadHotelSettings function logs the error and returns silently. The form renders with empty fields. An admin opening the tab for the first time has no way to distinguish "no settings saved yet" from "backend endpoint broken." The saveHotelSettings function does show an error alert, so attempting to save reveals the problem—but that's a poor discovery path.

## Translation keys and localization

The form uses ~15 new translation keys: hotel_settings, hotel_settings_desc, hotel_name, hotel_email, reservation_email, phone_number, reservation_phone, address, city, country, front_desk_hours, dining_hours, currency, timezone, hotel_settings_saved, and several placeholder keys. The languageStore.t() calls provide English fallbacks, so the form renders in English even if the keys are missing from the translation files. This breaks the experience for Amharic users—the rest of the Admin Profile page localizes correctly, but this tab reverts to English.

The translation keys follow the existing naming convention (lowercase with underscores) and the fallback strings are clear and professional. Adding these keys to the language store is straightforward, but the implementation assumes they'll exist.

## Save handler and error reporting

The saveHotelSettings function calls hotelSettingsService.updateHotelSettings with the full hotelSettings object. On success, it shows an alert with the translated success message. On failure, it shows an alert with the error response message or a generic fallback. The loading state is managed correctly in the finally block.

The updateHotelSettings method in the service sends a PUT request to /admin/profile/hotel-settings with the hotelSettings object as the request body. The backend needs to accept this endpoint and validate the fields. If the endpoint returns 404 (as the user's error log suggests), the alert will display "Error: Request failed with status code 404" or similar.

## Currency and timezone options

The currency dropdown offers three options: ETB (Ethiopian Birr), USD (US Dollar), EUR (Euro). The timezone dropdown offers six options, with Africa/Addis_Ababa at the top. Both defaults match the Ethiopian hotel context the project is built for. The dropdowns use the same Tailwind classes as the text inputs, so they match the visual style. The option values are standard ISO codes (currency) and IANA timezone identifiers (timezone), which are portable and widely supported.

The form doesn't validate timezone or currency values beyond the dropdown selection. A user can't enter a freeform string, which prevents typos but limits flexibility. If the hotel operates in a timezone not in the dropdown (e.g., Asia/Kolkata), an admin can't select it without modifying the component.

</details>

<details>
<summary>File map</summary>

**Created:**
- `src/services/hotelSettingsService.ts` — Service module exporting HotelSettings interface and getHotelSettings/updateHotelSettings methods

**Modified:**
- `src/views/Admin/AdminProfile.vue` — Added 5th tab (Hotel Settings) with 12-field form, imported Building2 icon, added hotelSettings reactive ref, added loadHotelSettings/saveHotelSettings methods, called loadHotelSettings in onMounted

[Full diff: commit 59ef4749ada51f4081d18682f0b2b5d7382d806b]

</details>
