{{-- Styles communs aux pages admin du Module 1 (exploitations & produits) --}}
<style>
  :root { --m1-green: #2d5a27; --m1-dark: #1e3a2b; --m1-mint: #e8f5ec; --m1-muted: #6c757d; --m1-border: #e9eef0; }

  /* En-tête de page */
  .m1-head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
  .m1-crumb { font-size: .78rem; color: var(--m1-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
  .m1-crumb a { color: var(--m1-muted); }
  .m1-head h3 { margin: 0; font-weight: 700; color: var(--m1-dark); }
  .m1-head p { margin: 4px 0 0; color: var(--m1-muted); }
  .m1-head__actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

  /* Indicateurs */
  .m1-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 18px; }
  .m1-kpi { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(16,24,40,.06); border: 1px solid var(--m1-border); display: flex; gap: 12px; align-items: center; color: inherit; text-decoration: none !important; transition: box-shadow .2s, transform .2s; }
  a.m1-kpi:hover { box-shadow: 0 6px 16px rgba(16,24,40,.1); transform: translateY(-2px); }
  .m1-kpi__icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
  .m1-kpi strong { display: block; font-size: 1.35rem; line-height: 1.1; color: var(--m1-dark); }
  .m1-kpi span { font-size: .8rem; color: var(--m1-muted); }
  .ic-green { background: #e3f4e8; color: #2d7a3e; } .ic-blue { background: #e0efff; color: #1d6fd1; }
  .ic-amber { background: #fff3d6; color: #b7791f; } .ic-purple { background: #efe7ff; color: #6b46c1; }
  .ic-red { background: #fde8e8; color: #c53030; } .ic-teal { background: #defaf5; color: #1a8f7a; }
  /* compat. ancienne grille de stats (page exploitations) */
  .m1-stats { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; }
  .m1-stat { flex: 1 1 180px; background: #fff; border-radius: 8px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); border-left: 4px solid var(--m1-green); }
  .m1-stat strong { display: block; font-size: 1.4rem; color: var(--m1-dark); }
  .m1-stat span { color: var(--m1-muted); font-size: .85rem; }

  /* Onglets de statut */
  .m1-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
  .m1-tab { padding: 7px 14px; border-radius: 999px; background: #fff; border: 1px solid var(--m1-border); color: #495057; font-size: .85rem; font-weight: 600; text-decoration: none !important; }
  .m1-tab:hover { border-color: var(--m1-green); color: var(--m1-green); }
  .m1-tab.active { background: var(--m1-green); border-color: var(--m1-green); color: #fff; }
  .m1-tab .count { display: inline-block; margin-left: 6px; padding: 0 7px; border-radius: 999px; background: rgba(0,0,0,.08); font-size: .75rem; }
  .m1-tab.active .count { background: rgba(255,255,255,.25); }

  /* Barre de filtres */
  .m1-toolbar { background: #fff; border-radius: 12px; border: 1px solid var(--m1-border); padding: 14px; margin-bottom: 16px; }
  .m1-toolbar .form-control { height: 38px; font-size: .85rem; }
  .m1-toolbar label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--m1-muted); margin-bottom: 3px; }
  .m1-toolbar__grid { display: grid; grid-template-columns: 2fr 1.3fr 1.3fr 1.2fr 0.9fr 0.9fr 1.1fr auto; gap: 10px; align-items: end; }
  .m1-risk { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: .76rem; padding: 3px 9px; border-radius: 999px; white-space: nowrap; }
  .m1-risk--low { background: #e6f6ec; color: #22663a; } .m1-risk--medium { background: #fff5dc; color: #8a5a00; } .m1-risk--high { background: #fdecec; color: #8f2424; }
  .m1-risk__bar { width: 46px; height: 5px; border-radius: 999px; background: rgba(0,0,0,.08); overflow: hidden; display: inline-block; }
  .m1-risk__bar span { display: block; height: 100%; background: currentColor; }
  .m1-analysis { border: 1px solid var(--m1-border); border-radius: 10px; padding: 12px 14px; background: #fcfdfc; font-size: .84rem; }
  .m1-analysis__head { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
  .m1-analysis__list { list-style: none; padding: 0; margin: 0 0 8px; }
  .m1-analysis__list li { padding: 5px 0; border-bottom: 1px dashed var(--m1-border); display: flex; gap: 8px; }
  .m1-analysis__list li span { font-weight: 800; color: #c53030; min-width: 30px; }
  .m1-analysis__meta { display: flex; gap: 14px; flex-wrap: wrap; color: var(--m1-muted); }
  .m1-analysis__ai { margin-top: 10px; background: #eef4ff; border-left: 3px solid #1d6fd1; border-radius: 6px; padding: 10px 12px; color: #1d3f72; white-space: pre-line; }
  .m1-toolbar__btns { display: flex; gap: 6px; }
  @media (max-width: 1200px) { .m1-toolbar__grid { grid-template-columns: 1fr 1fr 1fr; } }
  @media (max-width: 576px) { .m1-toolbar__grid { grid-template-columns: 1fr; } }
  .m1-search { position: relative; }
  .m1-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--m1-muted); }
  .m1-search input { padding-left: 32px; }
  .m1-filters .form-control { height: 38px; }

  /* Bascule de vue */
  .m1-switch { display: inline-flex; border: 1px solid var(--m1-border); border-radius: 8px; overflow: hidden; background: #fff; }
  .m1-switch a { padding: 7px 12px; color: #495057; font-size: .85rem; text-decoration: none !important; }
  .m1-switch a.active { background: var(--m1-green); color: #fff; }

  /* Tableau */
  .m1-panel { background: #fff; border-radius: 12px; border: 1px solid var(--m1-border); overflow: hidden; }
  .m1-panel__foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-top: 1px solid var(--m1-border); font-size: .85rem; color: var(--m1-muted); }
  .m1-panel__foot .pagination { margin: 0; }
  .m1-table { margin: 0; }
  .m1-table td, .m1-table th { vertical-align: middle !important; padding: .7rem .6rem !important; font-size: .85rem; border-color: var(--m1-border) !important; }
  .m1-table thead th { white-space: nowrap; background: #f8faf9; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--m1-muted); border-bottom: 1px solid var(--m1-border) !important; }
  .m1-table tbody tr:hover { background: #fafdfb; }
  .m1-table code { color: var(--m1-dark); background: var(--m1-mint); padding: 2px 6px; border-radius: 4px; font-size: .75rem; }
  .m1-product { display: flex; align-items: center; gap: 12px; }
  .m1-thumb { width: 52px; height: 52px; border-radius: 10px; object-fit: cover; flex-shrink: 0; border: 1px solid var(--m1-border); }
  .m1-thumb--empty { display: flex; align-items: center; justify-content: center; background: #f1f5f3; color: #a0b4a8; font-size: 1.4rem; }
  .m1-product strong { color: var(--m1-dark); }
  .m1-sub, .m1-owner small { color: var(--m1-muted); display: block; font-size: .78rem; }
  .m1-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--m1-green); color: #fff; font-weight: 700; font-size: .78rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .m1-producer { display: flex; align-items: center; gap: 9px; }
  .m1-status { min-width: 112px; font-size: .8rem !important; height: 32px !important; border-radius: 999px !important; font-weight: 600; }
  .m1-status.st-in_stock { background: #e6f6ec; color: #22663a; border-color: #bfe3cb; }
  .m1-status.st-shipped { background: #fff5dc; color: #8a5a00; border-color: #f2dda2; }
  .m1-status.st-processed { background: #e9ecff; color: #3b45a8; border-color: #c9cff5; }
  .m1-status.st-sold { background: #f1f3f5; color: #495057; border-color: #dfe3e7; }
  .m1-actions { white-space: nowrap; }
  .m1-actions .btn { padding: .32rem .55rem; border-radius: 7px; }
  .m1-grade { display: inline-block; min-width: 26px; text-align: center; border-radius: 6px; font-weight: 700; padding: 2px 6px; font-size: .78rem; }
  .m1-grade-A { background: #d1fae5; color: #065f46; }
  .m1-grade-B { background: #fef3c7; color: #92400e; }
  .m1-grade-C { background: #fee2e2; color: #991b1b; }
  .m1-chip { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: .72rem; font-weight: 600; background: #eef2f0; color: #4a5a50; }
  .m1-chip--photo { background: #fde8e8; color: #c53030; }

  /* Grille */
  .m1-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 16px; padding: 16px; }
  .m1-card { border: 1px solid var(--m1-border); border-radius: 12px; overflow: hidden; background: #fff; display: flex; flex-direction: column; transition: box-shadow .2s, transform .2s; }
  .m1-card:hover { box-shadow: 0 8px 20px rgba(16,24,40,.1); transform: translateY(-3px); }
  .m1-card__img { height: 150px; position: relative; background: #f1f5f3; display: flex; align-items: center; justify-content: center; color: #a0b4a8; font-size: 2.4rem; }
  .m1-card__img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
  .m1-card__img .m1-grade { position: absolute; top: 8px; right: 8px; }
  .m1-card__body { padding: 12px 14px; flex: 1; font-size: .83rem; }
  .m1-card__body strong { color: var(--m1-dark); font-size: .95rem; }
  .m1-card__foot { padding: 10px 14px; border-top: 1px solid var(--m1-border); display: flex; justify-content: space-between; align-items: center; gap: 6px; }

  /* Formulaire d'édition */
  .m1-edit-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .m1-edit-summary div { background: #f8faf9; border: 1px solid var(--m1-border); border-radius: 8px; padding: 10px 12px; }
  .m1-edit-summary span { display: block; font-size: .72rem; text-transform: uppercase; color: var(--m1-muted); }
  .m1-edit-summary strong { color: var(--m1-dark); }
  .m1-empty { text-align: center; padding: 50px 20px; color: var(--m1-muted); }
  .m1-empty i { font-size: 3rem; color: #b7d3c0; display: block; margin-bottom: 10px; }

  .btn-success { background-color: #28a745; border-color: #28a745; color: #fff; }
  .btn-danger { background-color: #dc3545; border-color: #dc3545; color: #fff; }
  .btn-info { background-color: #17a2b8; border-color: #17a2b8; color: #fff; }
  .btn-outline-secondary { border-color: #ced4da; }
  .invalid-feedback { font-size: .82rem; }
  .req { color: #dc3545; }
</style>
