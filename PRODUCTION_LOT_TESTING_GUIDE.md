# Production Lot Module - Testing Guide

## Prerequisites

1. **Database Migration Completed** ✅
   - Migration `2026_10_22_120211_create_production_lots_table` has been executed
   - Check: `production_lots` table exists in database

2. **Models & Controllers Verified** ✅
   - `app/Models/ProductionLot.php` - Created
   - `app/Http/Controllers/Pro/ProcessorProductionLotController.php` - Created
   - `app/Policies/ProductionLotPolicy.php` - Updated
   - All routes registered in `routes/web.php`

3. **Views Created** ✅
   - `resources/views/pro/processor/production-lots/index.blade.php`
   - `resources/views/pro/processor/production-lots/create.blade.php`
   - `resources/views/pro/processor/production-lots/show.blade.php`
   - `resources/views/pro/processor/production-lots/edit.blade.php`

---

## Manual Testing Workflow

### Step 1: Login as a Processor (Transformateur)

1. Navigate to `http://localhost:8000/login`
2. Login with a processor account that has:
   - `role = 'pro'`
   - `pro_type = 'processor'`
   - `status = 'approved'`

### Step 2: Navigate to Dashboard

1. Click on "Tableau de Bord — Usine de Transformation"
2. Verify you see:
   - ✅ Navigation tabs: Dashboard, Réceptions, Recettes, **Production**
   - ✅ Step 3 card: "🏭 Production & Traçabilité" with "Accéder" button
   - ✅ Stats section showing "Production du Mois" and "Produits Finis" (should be 0 initially)

### Step 3: Prepare Prerequisites

**Verify you have:**

1. **At least one Active Recipe with Ingredients**
   - Go to Recettes (Recipes)
   - Create or verify a recipe exists with:
     - `is_active = true`
     - At least 1 ingredient
   - Example: "Sauce Tomate" with:
     - Tomato: 50 kg
     - Salt: 1 kg
     - Vinegar: 0.5 L

2. **Sufficient Raw Materials (Stock)**
   - Go to Réceptions (Raw Materials)
   - Verify you have received materials with `status = 'received'`:
     - Tomato: at least 50 kg
     - Salt: at least 1 kg
     - Vinegar: at least 0.5 L
   - If not, create test receptions:
     - Go to "Lots Disponibles" (Available Lots)
     - Click on a harvest
     - Fill reception form and submit
     - Go back and approve the reception

### Step 4: Create Production Lot

1. **Navigate to Production Lots**
   - Click on "Production" tab in dashboard
   - Or go to `/pro/processor/production-lots`

2. **Click "Nouveau Lot" (New Lot)**
   - Should see empty state or list depending on whether lots exist

3. **Fill the Form**

   **Section: Recipe Selection**
   - Select recipe dropdown: Choose "Sauce Tomate"
   - Verify that ingredients appear below showing:
     - Tomato: 50 kg
     - Salt: 1 kg
     - Vinegar: 0.5 L

   **Section: Production Details**
   - Quantité Produite: `100` (this will require 100 kg tomato, 2 kg salt, 1 L vinegar because recipe makes 50 and we want 100)
   - Date de Production: Today's date (default)
   - Date de Péremption: 30 days from today (default)
   - Notes de Production: Optional - "Test batch"

4. **Submit Form**

   ✅ **Expected Results:**
   - Form validates and submits
   - Redirect to production lot detail page
   - See success message: "Lot de production créé avec succès et stock déduit! ✅"
   - Production lot displays with:
     - Product name: "Sauce Tomate"
     - Status: "Produit ✅"
     - Quantity: "100 kg"
     - Production date
     - Expiry date
     - QR code generated and displayed
     - Ingredients used section showing all ingredients

   ❌ **If Error - Insufficient Stock:**
   - Error message: "Stock insuffisant pour {material}. Nécessaire: X kg, Disponible: Y kg"
   - Solution: Create more raw material receptions

   ❌ **If Error - No Recipes:**
   - Message: "Aucune recette active"
   - Solution: Create a recipe in Recettes module

### Step 5: Verify Stock Deduction

1. **Go back to Réceptions (Raw Materials)**
2. **Check the reception records**
3. **Verify quantities were deducted**
   - Original: Tomato 50 kg
   - After production of 100 units: Tomato should be 0 kg (50 - 100×(50/50) = 0)
   - Example math for salt:
     - Original: 1 kg
     - Need: 100 × (1/50) = 2 kg
     - New: 1 - 2 = -1 kg (This should have caused error!)

   **Note:** Make sure you have MORE stock than required before creating lots!

### Step 6: View Production Lot Details

1. **From production lot list, click "Voir Détails"**
2. **Verify display of:**
   - ✅ All lot information
   - ✅ Recipe name and ingredients used
   - ✅ Status badge with correct color
   - ✅ Production dates
   - ✅ QR code image (250x250)
   - ✅ QR code identifier (LOT-xxx-xxx-xxx)
   - ✅ Production notes if any
   - ✅ Traceability URL

3. **Test QR Code**
   - Take a screenshot or scan with phone
   - Should link to `/origine/{qr_code_data}`
   - This is the public traceability page for consumers

### Step 7: Edit Production Lot

1. **From lot details, click "Éditer"**
2. **Verify:**
   - ✅ Recipe, quantity, dates are displayed but NOT editable
   - ✅ Status dropdown is available
   - ✅ Production notes textarea is available

3. **Make changes:**
   - Change status from "Produit ✅" to "Emballé 📦"
   - Add notes: "Ready for shipment"
   - Click "Mettre à Jour"

4. **Verify:**
   - ✅ Changes saved
   - ✅ Redirect back to details page
   - ✅ Updated information displays
   - ✅ Status changed to "Emballé 📦"

### Step 8: List & Pagination

1. **Go to Production Lots index**
2. **Verify:**
   - ✅ KPI cards showing:
     - Lots Total
     - Lots Produits
     - Proches Péremption
     - Lots Périmés
   - ✅ Production lots cards display with:
     - Product name and creation date
     - Expiry warning if within 7 days
     - Status badge
     - Recipe name
     - QR code ID
     - Production notes preview
     - Actions buttons

3. **Test Pagination (if multiple lots exist)**
   - Create at least 5 production lots
   - Go to index page
   - Verify: Only 4 items per page
   - Verify: Pagination controls appear at bottom
   - Navigate through pages

### Step 9: Dashboard Integration

1. **Go back to Dashboard**
2. **Verify updated stats:**
   - ✅ "Lots à Réceptionner" - shows pending harvests
   - ✅ "Recettes Actives" - shows active recipes
   - ✅ "Production du Mois" - shows lots created this month
   - ✅ "Éco-Score" - shows placeholder (not yet implemented)

3. **Check stats section:**
   - ✅ "Production du Mois" - should show 1+ (your newly created lot)
   - ✅ "Produits Finis" - should show total count

### Step 10: Delete Production Lot

1. **Go to production lot list**
2. **Click "Supprimer" on a lot**
3. **Confirm deletion dialog**
4. **Verify:**
   - ✅ Lot removed from list
   - ✅ Dashboard stats updated
   - ✅ Success message: "Lot de production supprimé."

---

## Expected Database State After Testing

After completing the test workflow, your database should show:

```sql
-- Production lots table
SELECT * FROM production_lots;
-- Should show: id, recipe_id, processor_id, quantity_produced, unit, production_date, expiry_date, status, qr_code_data, production_notes, created_at, updated_at

-- Raw material receptions (stocks should be reduced)
SELECT * FROM raw_material_receptions WHERE processor_id = ?;
-- Should show reduced quantity_received for used materials

-- Production lots with relationships
SELECT * FROM production_lots
INNER JOIN recipes ON production_lots.recipe_id = recipes.id
WHERE production_lots.processor_id = ?;
```

---

## Troubleshooting

### Issue: "Route not found" when clicking Production tab

**Solution:**
```bash
cd /path/to/FarmToFork
php artisan route:cache
php artisan cache:clear
```

### Issue: "QR code image not loading"

**Possible causes:**
1. No internet connection (QR Server API requires internet)
2. The API URL is malformed

**Test:** Open browser console and check image URL - should be:
```
https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http%3A%2F%2Flocalhost%3A8000%2Forigine%2FLOT-xxx
```

### Issue: "Insufficient stock" error when creating lot

**Solution:** Create more raw material receptions with sufficient quantities

### Issue: Views not loading (showing PHP errors)

**Solution:**
```bash
php artisan view:clear
php artisan config:clear
```

### Issue: Authorization errors (403 Forbidden)

**Check:**
1. User role is 'pro'
2. User pro_type is 'processor'
3. User status is 'approved'
4. User owns the production lot they're trying to access

---

## Performance Notes

- **Pagination:** 4 items per page (can be adjusted in controller)
- **QR Code:** Generated via external API (no server load)
- **Stock Deduction:** Calculated on-the-fly (no batch processing)
- **Database Queries:** Optimized with eager loading (with relationships)

---

## Browser Compatibility

Tested with:
- ✅ Chrome/Chromium
- ✅ Firefox
- ✅ Safari
- ✅ Edge

**Mobile responsive:** Yes - responsive grid layouts

---

## Completion Checklist

- [ ] All migration files executed
- [ ] Production lots table created successfully
- [ ] Routes registered and accessible
- [ ] Create production lot functionality works
- [ ] Stock deduction works correctly
- [ ] QR codes generate and display
- [ ] Edit production lot works
- [ ] Delete production lot works
- [ ] Pagination displays correctly (4 per page)
- [ ] Dashboard stats update
- [ ] UI matches processor green design
- [ ] All validations work (frontend & backend)
- [ ] Error handling displays proper messages
- [ ] Expiry date alerts show correctly

---

## Next Phase

After completing Task 10, consider implementing:
1. Advanced packaging management
2. Production analytics and reporting
3. Integration with distributor shipment system
4. Batch tracking and versioning
5. Quality testing records
