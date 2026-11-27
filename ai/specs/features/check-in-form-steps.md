# Feature: Check-In Form Steps (step-by-step)

## Purpose

The signature check-in form is a **multi-step wizard** rendered as seven DOM steps in
`resources/views/Process.blade.php` (`#step1` … `#step7`). Where [`check-in.md`](check-in.md)
gives the high-level architecture and cookie-flow, this file drills into **each step** of
the form: the exact fields, the cookie shape each step writes, the JS managers that handle
it, the backend endpoint that persists it, and how each step behaves in edit mode. It also
records known discrepancies between the UI and the backend.

## Step map

| # | UI heading | Blade partial | `FORM_CONFIG.STEPS` | API endpoint |
|---|-----------|---------------|---------------------|--------------|
| 1 | Your Information | `components/forms/owner-info.blade.php` | `OWNER_INFO` (1) | `POST /api/checkin/step1/user-info` |
| 2 | Pet Information | `components/forms/pet-info.blade.php` | `PET_INFO` (2) | `POST /api/checkin/step2/pet-info` |
| 3 | Feeding & medication | `components/forms/food-medication.blade.php` + `components/pop-ups/feeding-medication.blade.php` | `FEEDING_MEDICATION` (3) | (part of step 3 / full submit) |
| 4 | Health ("Did you notice unusual health behavior…") | `components/forms/health-info.blade.php` | `HEALTH_INFO` (4) | `POST /api/checkin/step3/pet-health` |
| 5 | Inventory Items | `components/forms/inventory.blade.php` | `INVENTORY` (5) | `POST /api/checkin/step5/extra-info` |
| 6 | Grooming | `components/steps/grooming.blade.php` | `GROOMING` (6) | (see [services-and-inventory.md](services-and-inventory.md)) |
| 7 | Review / Submit | `components/steps/review-submit.blade.php` | `THANKS` (7) | `POST /api/checkin/submit` |

`ValidationManager.handleFormStep()` uses **0-based** indices (`STEPS.X - 1`), so "step 0"
is Owner Info, "step 1" is Pet Info, and so on.

---

## Step 1 — Owner Info

### Fields

`phone` (tel, `pattern="[0-9]{10}"`, required) · `name` (required) · `email` (email, required) ·
`address` (required) · `city` (required) · `state` (required) · `zip` (number, required) ·
`emergencyContactName` (optional) · `emergencyContactPhone` (tel, `[0-9]{10}`, optional).

### Cookie shape

```js
user: {
  info:            { phone, name, email, address, city, zip },   // NOTE: no `state`
  emergencyContact:{ name, phone }
}
```

### Frontend

- **Entry / phone lookup** — `resources/js/components/CheckInHandler.js` posts
  `/api/check-user`; based on `userExists` / `hasCheckIn` it redirects to
  `/new-form?phone=…` (new), `/new-form-pre-filled?phone=…` (known, no active check-in),
  or the confirmation page (active check-in).
- **Pre-fill** — `CheckInFormController@newForm` / `newFormPreFilled` look up
  `User::where('phone', …)` and pass `$user` into the Blade form, which echoes
  `$user?->phone/name/email/address/city/state/zip` into the input `value`s.
- **Capture** — on "Next", `FormHandler.extractFormInputValues('#ownerInfoForm')` →
  `ValidationManager.updateUserInfo()` → `FormDataManager.updateCheckinData()` → cookie.
- **Reactivity** — `UIManager` (step 0) → `FormUpdater.updateOwnerInfoForm()` fills only
  empty fields (`['phone','name','email','address','city','zip']` + emergency contact).
- **Edit mode** — `FormHandler.populateFormWithCookies()` fills step 0 from
  `checkinData.user?.info` and `checkinData.user?.emergencyContact`.

### Backend

`POST /api/checkin/step1/user-info` → `CheckInApiController@submitUserInfo`:
validates `user_info.phone` (string), `user_info.name` (string), `user_info.email` (email),
then `CheckInUserService::processUserInfo()` upserts the `User` keyed by `phone`. If both
`emergencyContactName` and `emergencyContactPhone` are set,
`CheckInUserService::processEmergencyContact()` `updateOrCreate`s an `EmergencyContact`.

`POST /api/check-user` → `checkUser` returns `userExists`, `hasCheckIn` (active check-in via
`check_out IS NULL`), `userId`, `userName`, `userEmail`, `userAddress`.

### ⚠ Discrepancies

- `state` is present and `required` in the Blade form and persisted by `CheckInUserService`,
  but is **omitted** from `DEFAULT_CHECKIN_STRUCTURE.user.info`, from
  `FormUpdater.updateOwnerInfoForm()`'s field list, and from both `CheckInTransformer`
  methods — so `state` never round-trips through edit mode / pre-fill.
- `submitUserInfo` only validates `phone/name/email`; `address/city/state/zip` are marked
  `required` in the UI but can reach the backend empty.
- `ValidationManager.updateUserInfo()` spreads the raw form object into `user.info`, so
  `emergencyContactName`/`emergencyContactPhone` also leak into `user.info` (duplicated by
  `user.emergencyContact`).
- `checkUser` returns `userAddress` but not `city/state/zip`.

---

## Step 2 — Pet Information

### Fields

`petName` (required) · `petColor` (required) · `petType` (Species `<select>`, required) ·
`petBreed` (required) · `petAge` (Birth Date, readonly, via `datePicker()`) ·
`petWeight` (number, pounds) · `petGender` (male/female radio) · `petSpayed` (yes/no radio).

### Cookie shape

`pets[]` items built from `DEFAULT_PET_STRUCTURE`:

```js
{
  id: "pet_<timestamp>_<rand>",
  info:    { petName, petColor, petType, petBreed, petAge, petWeight, petGender, petSpayed },
  health:  { unusualHealthBehavior, healthBehaviors, warnings },
  feeding: [], medication: []
}
```

### Frontend

- `PetManager.addPetToCheckin()` (new pet, generates `id`), `updatePetInCheckin()`,
  `removePetFromCheckin()`, `cleanEmptyPets()` (drops pets whose info is all-empty),
  `updatePetHealthInfo()`, `updateFeedingMedicationItem()`, `removeFeedingMedicationItem()`.
- `PetPillManager.addPetPillsToContainer()` renders the "Your pets" pills.
- **Fast check-in** — `Process.blade.php` emits existing pets into
  `#fastCheckinPillsSection[data-db-pets]`; clicking a pill pre-fills the form and carries a
  `petId`, so the backend updates the existing pet instead of creating a new one.
- `ValidationManager.handleFormStep(1)` → `addPetToCheckin()` (no index) or
  `updatePetInCheckin()` (when a pet is selected).

### Backend

`POST /api/checkin/step2/pet-info` → `submitPetInfo`: validates `user_id` (exists) and
`pet_info.petName/petType/petBreed/petColor`, then `CheckInPetService::processPetInfo()`
(updates the existing pet when `pet_info.petId` is present). Gender/kind/castrated are
resolved via `firstOrCreate`.

### ⚠ Discrepancies

- Species `<select>` maps **both** "Pig" and "Other" to `value="other"` — they are
  indistinguishable downstream.
- `petAge/petWeight/petGender/petSpayed` are `required` in the UI but are **not** validated
  by `submitPetInfo` (only name/type/breed/color are).

---

## Step 3 — Feeding & Medication

### UI

`food-medication.blade.php` renders **morning / afternoon / night** containers
(`.container-day[data-time-slot]`), each with a Food list and a Medication list, a
"Same feeding for all" checkbox (`sameFeedingForAll`), and add buttons. The
`feeding-medication.blade.php` popup captures:

- `day_time[]` checkboxes — morning / afternoon / night (multi-select),
- `type` radio — `food` | `medication`,
- `feeding_med_details` text ("1 cup dry food").

### Cookie shape

Per pet: `feeding[]` and `medication[]` entries of `{ day_time, feeding_med_details }`.

### Frontend

- Popup submit → `PetManager.updateFeedingMedicationItem()` / `removeFeedingMedicationItem()`.
- `FormUpdater.populateFeedingMedicationPopup()` re-populates the popup in edit mode.
- `ValidationManager.handleFormStep(2)` (FEEDING_MEDICATION) is a **no-op** — this step has
  no gating and is managed entirely through the popup.

### Backend

There is no standalone sequential endpoint for feeding; it is persisted by
`submitPetHealth` (step 3) and the one-shot `submitCheckIn`. `CheckInPetService::
processFeedingAndMedication()` creates `Food` / `Medicine` rows, resolving `MomentOfDay`
via `firstOrCreate(['name' => $day_time])`. Default "Food" / "None" entries are created when
no data is supplied.

### ⚠ Discrepancy

- `config.js` `TIME_SLOTS` defines **5** slots (morning, noon, afternoon, evening, night) but
  the UI and popup only expose **3** (morning, afternoon, night).

---

## Step 4 — Health

### Fields

- `unusualHealthBehavior` radio (yes / no) — question: *"Did you notice unusual health
  behavior such as Vomiting, Diarrhea, Heart Conditions, Physical Condition, Seizure, others?"*
- `healthBehaviorDetails` ("Which?", shown only when "yes"),
- `warnings` textarea (Health Matters, Special Care, Behavioral …).

### Cookie shape

Per pet: `health: { unusualHealthBehavior: bool, healthBehaviors: string, warnings: string }`.

### Frontend

- `HealthFormManager` — conditional reveal of "Which?" (and clears it on "no"),
  `saveCurrentPetHealth()`, `saveAllPetsHealth()` (bulk), `loadPetHealth()`.
- `ValidationManager.handleFormStep(3)` (HEALTH_INFO) **bulk-applies** health to every pet and
  snapshots grooming selections (preserving existing inventory).

### Backend

`POST /api/checkin/step3/pet-health` → `submitPetHealth`: validates `pet_id` (exists),
`health_data` (array), `feeding_data`/`medication_data` (arrays). It updates the pet's
`health_conditions` (= `unusualHealthBehavior`) and `warnings`, creates a temporary `CheckIn`
(`CHECKED_IN`), and calls `processFeedingAndMedication()`.

### ⚠ Discrepancy

- The "Which?" details (`healthBehaviors`) live in the cookie and summary but are **not**
  persisted to a dedicated DB column by `submitPetHealth` — `health_conditions` holds the
  `unusualHealthBehavior` boolean instead (see also `processPet()`).

---

## Step 5 — Inventory Items

### Fields

`itemName` text input · `#inventoryItemsList` (rendered list) · `#addInventoryItem` button ·
`inventoryComplete` checkbox ("I'm not leaving anything in the inventory").

### Cookie shape

`inventory: [ "<item name>", … ]` (array of strings) · `inventoryComplete: bool`.

### Frontend

- `InventoryFormManager` — add via button or the **Enter** key (form submit is prevented);
  `inventoryComplete` change handler.
- `InventoryManager` — `addInventoryItem()`, `removeInventoryItem()`, `updateInventoryItem()`,
  `setInventoryComplete()`, `updateInventoryUI()` (hides the "complete" checkbox whenever
  items exist).
- `ValidationManager.handleFormStep(4)` (INVENTORY) gates progression: grooming popup first,
  then terms popup (`NavigationManager` mirrors this for the Next button).

### Backend

`POST /api/checkin/step5/extra-info` → `submitExtraInfo`: validates `checkin_id` (exists) and
`extra_data` (array), then `CheckInService::processInventory()` +
`processExtraServices()`. Items are stored as `Item` rows (`name`, `pet_id`, `check_in_id`).

---

## Steps 6–7 (context only)

- **Step 6 — Grooming** (optional) and **Step 7 — Review / Submit** (summary + terms + one-shot
  `POST /api/checkin/submit`) are summarized in [check-in.md](check-in.md); grooming service
  details live in [services-and-inventory.md](services-and-inventory.md).

## Key files

- Views: `resources/views/Process.blade.php` (composition root),
  `resources/views/components/steps/{owner-info,pet-info,feeding-medication,health-info,inventory,grooming,review-submit}.blade.php`,
  `resources/views/components/forms/{owner-info,pet-info,food-medication,health-info,inventory}.blade.php`,
  `resources/views/components/ui/{input,select,radio,checkbox,date-picker,field,card}.blade.php`,
  `resources/views/components/wizard/header.blade.php`,
  `resources/views/components/pop-ups/feeding-medication.blade.php`.
- JS: `resources/js/cookies-and-form/config.js`,
  `resources/js/cookies-and-form/managers/{CoreDataManager,ValidationManager,PetManager,PetPillManager,HealthFormManager,InventoryManager,InventoryFormManager,FormHandler,NavigationManager}.js`,
  `resources/js/cookies-and-form/reactivitySystem/{UIManager,FormUpdater}.js`,
  `resources/js/components/CheckInHandler.js`.
- Backend: `app/Http/Controllers/CheckInApiController.php`,
  `app/Services/{CheckInUserService,CheckInPetService,CheckInTransformer}.php`,
  `app/Models/{User,Pet,EmergencyContact,Food,Medicine,Item,MomentOfDay}.php`.
