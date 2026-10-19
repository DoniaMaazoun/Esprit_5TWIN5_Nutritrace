# 🧪 QUICK TEST GUIDE - After Bug Fix

**Status:** 🟢 Ready to test
**Date:** October 19, 2026
**Server:** Running at http://127.0.0.1:8000

---

## ⚡ Quick Test (2 minutes)

### Step 1: Open Login Page
```
URL: http://127.0.0.1:8000/login
```

### Step 2: Login as Processor
```
Email:    processor@usine.com
Password: password123

Expected: Auto-redirect to /pro/dashboard
```

### Step 3: Navigate to Raw Materials
```
On Pro Dashboard, find and click:
📥 "Réception de Lots" (orange card)

OR direct URL: http://127.0.0.1:8000/pro/processor/raw-materials
```

### Step 4: Verify Harvests Display
✅ **You should see:**

**Statistics:**
```
📭 En attente: 0
✅ Reçues: 0
❌ Rejetées: 0
📦 Total reçu: 0 kg
```

**Available Lots Tab (active by default):**
```
Lots Disponibles pour Réception

┌─────────────────────────────────────────────────┐
│ Tomates Bio Provence                            │
│ Ferme: Ferme des Vallées Provençales           │
│ Producteur: Ferme Bio Provence                 │
│ Date: [Today's date]                           │
│ Quantité: 500 kg                               │
│ Catégorie: Tomates                             │
│ [ENREGISTRER LA RÉCEPTION]                     │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Pommes de terre Charlotte                       │
│ ...                                             │
│ Quantité: 800 kg                               │
│ [ENREGISTRER LA RÉCEPTION]                     │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Dattes Deglet Nour                              │
│ ...                                             │
│ Quantité: 300 kg                               │
│ [ENREGISTRER LA RÉCEPTION]                     │
└─────────────────────────────────────────────────┘
```

### Step 5: IF You See Empty State
❌ **You should NOT see:**
```
Aucun lot disponible
Les producteurs n'ont pas encore créé de lots validés
```

If you do see this message:
1. Hard refresh browser: `Ctrl+Shift+R` (Chrome) or `Ctrl+F5` (Firefox)
2. Wait 2 seconds for page to load
3. Check browser console (F12) for errors
4. If still empty, re-seed database:
   ```bash
   php artisan migrate:fresh --seed --force
   ```

---

## 🎯 Extended Test (5 minutes)

### Test: Register Reception for Tomates
1. Click **"ENREGISTRER LA RÉCEPTION"** button on Tomates card
2. Form should display with:
   - Prévisualisation du lot: **Tomates Bio Provence**
   - Quantité Produite: **500 kg** (calculated from 500 kg)
   - Form fields to fill

3. Fill the form:
   ```
   Quantité Reçue: 450 kg
   Date de Réception: [Today] (auto-filled)
   Qualité: Bon ✅
   Lieu de Stockage: Silo n°1
   Température: 18
   Notes: Récolte en excellent état
   ```

4. Click **"ENREGISTRER RÉCEPTION"**

5. ✅ Expected: Redirect to reception details page

### Test: Approve Reception
1. On details page, find button: **"APPROUVER LA RÉCEPTION"** (green)
2. Click it → Modal appears
3. Check confirmation checkbox
4. Click **"APPROUVER"** in modal
5. ✅ Expected:
   - Status changes to: **Reçu ✅**
   - Message: "Réception marquée comme reçue! ✅"
   - Buttons (Approve/Reject) disappear

### Test: Check Stats Updated
1. Click **"Retour à la liste"** link
2. ✅ Expected stats:
   ```
   📭 En attente: 0
   ✅ Reçues: 1 ← Updated!
   ❌ Rejetées: 0
   📦 Total reçu: 450 kg ← Updated!
   ```

3. Switch to **"Historique des Réceptions"** tab
4. ✅ Expected: See 1 row in table with:
   - Produit: Tomates Bio Provence
   - Producteur: Ferme Bio Provence
   - Quantité Reçue: 450.00 kg
   - Date Réception: [Today]
   - Qualité: Bon ✅
   - Statut: Reçu ✅

---

## ✅ Success Criteria

All of the following must be true:

- [ ] 3 available harvests display on index page
- [ ] Each harvest shows correct product name (not "name")
- [ ] Each harvest shows correct quantity in kg (500, 800, 300)
- [ ] "Enregistrer la Réception" buttons are clickable
- [ ] Create form shows harvest preview with correct data
- [ ] Stats update after approval
- [ ] Reception appears in history after registration
- [ ] No errors in browser console
- [ ] No 404 errors
- [ ] Page loads in < 1 second

---

## 🔧 If Something's Wrong

### Problem: Still seeing "Aucun lot disponible"
```bash
# 1. Hard refresh browser
Ctrl+Shift+R or Ctrl+F5

# 2. If still empty, check logs
php artisan tail

# 3. If needed, re-seed:
php artisan db:seed --class=ProducerSeeder

# 4. If still broken, full reset:
php artisan migrate:fresh --seed --force
```

### Problem: Field names showing as errors
```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Restart server
# Stop current process (Ctrl+C)
# php artisan serve
```

### Problem: Harvest data showing as "N/A"
```bash
# Verify in database
php artisan tinker
> Harvest::all()
> Harvest::find(1)->product_name
> Harvest::find(1)->quantityInKg()
```

---

## 📊 What Was Fixed

| Issue | Before | After |
|-------|--------|-------|
| Query status | 'validated' (doesn't exist) | 'in_stock' ✅ |
| Harvest name display | $harvest->name (empty) | $harvest->product_name ✅ |
| Quantity display | $harvest->quantity_kg (empty) | $harvest->quantityInKg() ✅ |
| Result | "Aucun lot disponible" ❌ | 3 harvests showing ✅ |

---

## 🎬 Test Checklist

Run through this before declaring success:

### Display Test
- [ ] Open `/pro/processor/raw-materials`
- [ ] See 3 harvest cards in "Lots Disponibles" tab
- [ ] Each card shows correct product name
- [ ] Each card shows correct quantity in kg
- [ ] Each card has "Enregistrer la Réception" button

### Registration Test
- [ ] Click register button on first harvest
- [ ] Form displays correctly
- [ ] Harvest preview shows correct name
- [ ] Quantity field has correct max value
- [ ] Fill form and submit successfully
- [ ] Redirect to details page (not error page)

### Approval Test
- [ ] See "APPROUVER LA RÉCEPTION" button on details
- [ ] Click button → Modal appears
- [ ] Modal has checkbox to confirm
- [ ] Click approve in modal → Modal closes
- [ ] Status badge changes to "Reçu ✅"
- [ ] Approve button disappears (after approval)

### Stats Test
- [ ] Return to index page
- [ ] "Reçues" stat shows 1
- [ ] "Total reçu" shows 450 kg (or whatever was approved)
- [ ] Reception appears in history tab with correct data

### Error-Free Test
- [ ] F12 → Console tab → No red errors
- [ ] F12 → Network tab → All requests 200-399 status
- [ ] No 404 errors
- [ ] No JavaScript errors

---

## 🚀 After Testing

If all tests pass:
1. Document any issues you find
2. Test the other workflows (reject, edit)
3. Report success back
4. Begin Processor Phase 2 development

---

## 📞 Contact

If you encounter any issues during testing:
1. Check `BUGFIX_RAW_MATERIALS_DISPLAY.md` for technical details
2. Review the fixes in the 5 modified files
3. Check Laravel logs: `storage/logs/laravel.log`
4. Re-seed if needed: `php artisan db:seed --force`

---

**Ready to test?** Let's go! 🚀

**URL:** http://127.0.0.1:8000/login
**Credentials:** processor@usine.com / password123

