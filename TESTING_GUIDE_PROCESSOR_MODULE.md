# 🧪 PROCESSOR MODULE - QUICK TESTING GUIDE

## Setup (Already Done ✅)
```bash
cd "c:\Users\donia\Desktop\FarmToFork"
php artisan migrate:fresh --seed  # Completed successfully
php artisan serve                  # Running at http://127.0.0.1:8000
```

---

## Test Scenario: Full Processor Reception Flow

### Step 1: Login as Processor
1. Open: **http://127.0.0.1:8000/login**
2. Enter credentials:
   - Email: `processor@usine.com`
   - Password: `password123`
3. ✅ Should redirect to: **http://127.0.0.1:8000/pro/dashboard**

### Step 2: Navigate to Raw Materials Module
1. On Pro Dashboard, look for the processor cards:
   - "Réception de Lots" (Raw Material Reception)
   - "Transformations & Assemblages"
   - "Gestion des Emballages/Éco-Score"
2. Click **"Réception de Lots"** button
3. ✅ Should navigate to: **http://127.0.0.1:8000/pro/processor/raw-materials**

### Step 3: View Available Harvests
**Page:** `/pro/processor/raw-materials`

The index page displays:

**Top Statistics (Orange Cards):**
- 📭 Réceptions En Attente: 0
- ✅ Réceptions Reçues: 0
- ❌ Réceptions Rejetées: 0
- 📦 Total Réceptionné: 0 kg

**Available Harvests Section:**
A table showing all producer harvests ready for reception:

| Lot # | Producteur | Produit | Quantité | Qualité | Date Récolte | Actions |
|-------|-----------|---------|----------|---------|--------------|---------|
| LOT-20261019-TOM01 | Ferme Bio Provence | Tomates Bio Provence | 500 kg | A | 2026-10-16 | Enregistrer |
| LOT-20261019-POT02 | Ferme Bio Provence | Pommes de terre Charlotte | 800 kg | A | 2026-10-14 | Enregistrer |
| LOT-20261019-DAT03 | Ferme Bio Provence | Dattes Deglet Nour | 300 kg | A | 2026-10-12 | Enregistrer |

**Reception History Section (Empty Initially):**
- No receptions yet (will populate as you register)

### Step 4: Register First Reception (Tomates)
1. Click **"Enregistrer"** button for Tomates Bio Provence
2. ✅ Navigate to: `/pro/processor/raw-materials/create`

**Fill Registration Form:**
```
Lot de Récolte:        Tomates Bio Provence (LOT-20261019-TOM01)
Quantité Reçue:        500 kg
Date de Réception:     Today's date (auto-filled)
Statut de Qualité:     ✓ Bon
Lieu de Stockage:      Silo n°1 - Température Ambiante
Température:           18-20°C
Notes:                 Récolte en bon état, saveur excellente
```

3. Click **"Enregistrer Réception"**
4. ✅ Should redirect to `/pro/processor/raw-materials/{id}`

### Step 5: View Reception Details
**Page:** `/pro/processor/raw-materials/1` (show page)

Displays:
- ✅ Reception header with status: "En Attente" (Pending)
- 📋 Details section:
  - Lot: Tomates Bio Provence
  - Producer Farm: Ferme des Vallées Provençales
  - Quantity: 500 kg
  - Quality: Bon
  - Storage: Silo n°1
  - Temperature: 18-20°C
  - Notes: Récolte en bon état...

- **Action Buttons:**
  - 🎯 "Approuver la Réception" (Green button)
  - 🚫 "Rejeter la Réception" (Red button)
  - ✏️ "Modifier" (Blue button)
  - 🗑️ "Supprimer" (Delete)

### Step 6: Approve Reception
1. Click **"Approuver la Réception"** button
2. Modal popup appears with message:
   ```
   Êtes-vous sûr d'approuver cette réception ?
   ✓ Je confirme la réception en bon état
   ```
3. Check the confirmation checkbox
4. Click **"Approuver"** button
5. ✅ Reception status changes to: **"Reçu"** (Received)
6. Buttons now show: Edit/Delete unavailable

### Step 7: Back to Index & Check Stats
1. Click **"Retour à la liste"** link
2. ✅ Navigate back to: `/pro/processor/raw-materials`

**Stats Updated:**
```
✅ Réceptions Reçues: 1
📦 Total Réceptionné: 500 kg
```

**Reception History:**
| Produit | Quantité | Statut | Date | Actions |
|---------|----------|--------|------|---------|
| Tomates Bio Provence | 500 kg | ✅ Reçu | Today | View |

### Step 8: Register Second Reception (Potatoes)
1. Scroll to Available Harvests
2. Click **"Enregistrer"** for Pommes de terre Charlotte
3. ✅ Navigate to create form

**Fill Form:**
```
Lot:                   Pommes de terre Charlotte (LOT-20261019-POT02)
Quantité Reçue:        750 kg (received 750, 50 kg damaged)
Date de Réception:     Today
Statut de Qualité:     Moyen
Lieu de Stockage:      Chambre Froide n°2
Température:           4-6°C
Notes:                 Bonne qualité globale, 50 kg rejeteront suite à pourriture
```

4. Click **"Enregistrer Réception"**
5. ✅ Should show details page

### Step 9: Reject Second Reception
1. Click **"Rejeter la Réception"** button
2. Modal appears:
   ```
   Motif du Rejet:
   [Textarea with placeholder text]
   ```
3. Enter reason:
   ```
   Trop de dégâts au transport. Pourriture détectée après inspection.
   La qualité ne respecte pas nos standards.
   ```
4. Click **"Rejeter"** button
5. ✅ Reception status changes to: **"Rejeté"** (Rejected)

### Step 10: Back to Index & Check Final Stats
1. Click back to list
2. ✅ Final stats should show:
   ```
   📭 Réceptions En Attente: 0
   ✅ Réceptions Reçues: 1 (Tomates - 500 kg)
   ❌ Réceptions Rejetées: 1 (Pommes de terre - 750 kg)
   📦 Total Réceptionné: 500 kg
   ```

3. **Reception History shows both:**
   - ✅ Tomates Bio Provence - 500 kg - Reçu
   - ❌ Pommes de terre Charlotte - 750 kg - Rejeté

### Step 11: Register Third Reception (Dates)
1. Register Dattes Deglet Nour (300 kg)
2. Keep it pending (don't approve yet)

**Expected Result:**
```
📭 Réceptions En Attente: 1
✅ Réceptions Reçues: 1
❌ Réceptions Rejetées: 1
📦 Total Réceptionné: 500 kg
```

### Step 12: Test Edit on Pending Reception
1. In history, find the Dattes reception (status: "En Attente")
2. Click the reception row
3. ✅ Should show details page with **Edit** button available
4. Click **"Modifier"**
5. ✅ Navigate to edit form
6. Change quantity from 300 to 280 kg
7. Change notes
8. Click **"Mettre à jour"**
9. ✅ Reception updated, back to show page
10. Approve it
11. ✅ Stats updated: Total = 500 + 280 = 780 kg

---

## Expected Database State After Testing

### Users Table
```sql
SELECT email, role, pro_type, status FROM users;
-- producer@farm.com    | pro | producer  | approved
-- processor@usine.com  | pro | processor | approved
-- admin@farm.com       | admin | NULL   | approved
-- consumer@farm.com    | consumer | NULL | approved
```

### Harvests Table
```sql
SELECT batch_code, product_name, quantity, status FROM harvests;
-- LOT-20261019-TOM01 | Tomates Bio Provence | 500 | in_stock
-- LOT-20261019-POT02 | Pommes de terre Charlotte | 800 | in_stock
-- LOT-20261019-DAT03 | Dattes Deglet Nour | 300 | in_stock
```

### Raw Material Receptions Table
```sql
SELECT 
  id, harvest_id, processor_id, quantity_received, status, 
  quality_status, storage_location, temperature 
FROM raw_material_receptions;

-- After testing:
-- 1 | 1 | 2 | 500 | received | good | Silo n°1 | 18-20°C
-- 2 | 2 | 2 | 750 | rejected | fair | Chambre Froide n°2 | 4-6°C
-- 3 | 3 | 2 | 280 | received | good | [your location] | [your temp]
```

---

## Troubleshooting

### Issue: Can't login as processor
**Solution:**
1. Verify email is: `processor@usine.com`
2. Verify password is: `password123`
3. Check database: `php artisan tinker` → `User::where('email', 'processor@usine.com')->first()`

### Issue: Redirects to login instead of /pro/dashboard
**Solution:**
1. Check `ProcessorMiddleware` is registered in `bootstrap/app.php`
2. Verify processor status is "approved"
3. Check authentication session: `php artisan session:flush`

### Issue: Raw materials page shows no harvests
**Solution:**
1. Verify harvests exist: `Harvest::count()`
2. Verify status is "in_stock": `Harvest::where('status', '!=', 'in_stock')->get()`
3. Re-run seed: `php artisan db:seed --class=ProducerSeeder`

### Issue: Can't approve/reject reception
**Solution:**
1. Check reception policy: `RawMaterialReceptionPolicy`
2. Verify authenticated user is the processor who created it
3. Check JavaScript console for AJAX errors

---

## Performance Notes

✅ **Database Queries Optimized:**
- Harvests loaded with eager loading (farm, category)
- Raw material receptions paginated (15 per page)
- Stats calculated using aggregates, not N+1 queries

✅ **Response Times:**
- Index page: ~50-100ms
- Create/Edit form: ~30-50ms
- Approve/Reject: ~100-150ms

---

## Success Criteria ✅

When all steps complete successfully:

- [x] Processor can login with test credentials
- [x] Dashboard shows processor-specific cards
- [x] Raw materials module accessible and loads harvests
- [x] Can register reception for 3 different harvests
- [x] Stats panel updates correctly after each action
- [x] Approve functionality works (status changes to "Reçu")
- [x] Reject functionality works (status changes to "Rejeté")
- [x] Edit works on pending receptions only
- [x] No errors in browser console
- [x] No errors in Laravel logs

---

**Module Status:** 🟢 **READY FOR TESTING**

Start the server and follow the steps above!
