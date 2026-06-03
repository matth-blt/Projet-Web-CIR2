# ==============================================================================
# irve_insert.py — Initialisation de la base de données IRVE
# ==============================================================================
#
# Ordre d'insertion (contraintes FK) :
#   Niveau 0 : horaire, paiement, prise, condition_acces, implantation, enseigne
#   Niveau 1 : departement
#   Niveau 2 : commune       (FK → departement)
#   Niveau 3 : acteur
#   Niveau 4 : station       (FK → commune, acteur, implantation, enseigne)
#   Niveau 5 : point_de_charge (FK → condition_acces)
#   Niveau 6 : a_une, possede_des, est_payer_avec, a_des
#
# Choix de déduplication acteur :
#   - Aménageurs : par SIREN  → une ligne par entité légale (132 lignes, 84 noms distincts)
#   - Opérateurs : par nom    → pas de SIREN dans le CSV source
# ==============================================================================

import pandas as pd
import numpy as np
from sqlalchemy import create_engine, text

# ==============================================================================
# 0. CONFIGURATION
# ==============================================================================
DB_USER     = 'root'
DB_PASSWORD = ''
DB_HOST     = 'localhost'
DB_NAME     = 'irve_bdd_projet_v2'

CSV_IRVE     = 'bdd/irve_init.csv'
CSV_COMMUNES = 'bdd/communes-france-2024-limite.csv'

engine = create_engine(
    f'mysql+mysqlconnector://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME}',
    echo=False
)

# ── Helpers ───────────────────────────────────────────────────────────────────

def to_bool(series: pd.Series) -> pd.Series:
    """Normalise les valeurs hétérogènes ('true', 'TRUE', '1', True…) en 0/1.
    Les NaN/vides/inconnus deviennent 0.
    """
    return (
        series.astype(str).str.strip().str.lower()
        .map({'true': 1, '1': 1, 'false': 0, '0': 0, 'nan': 0, 'none': 0, '': 0})
        .fillna(0).astype(int)
    )

def trunc(series: pd.Series, length: int, label: str = '') -> pd.Series:
    """Tronque à `length` caractères et affiche un avertissement si nécessaire."""
    s = series.astype(str)
    n_over = (s.str.len() > length).sum()
    if n_over > 0 and label:
        print(f"    ⚠  {label} : {n_over} valeur(s) tronquée(s) à {length} cars")
    return s.str[:length]

def nullable_str(series: pd.Series, length: int, label: str = '') -> pd.Series:
    """Convertit en str tronqué, mais conserve les NaN comme None (SQL NULL)."""
    def convert(x):
        if pd.isna(x):
            return None
        return str(x)[:length]
    result = series.apply(convert)
    n_over = series.dropna().astype(str).str.len().gt(length).sum()
    if n_over > 0 and label:
        print(f"    ⚠  {label} : {n_over} valeur(s) tronquée(s) à {length} cars")
    return result

# ==============================================================================
# 1. CHARGEMENT DES CSV
# ==============================================================================
print("⏳ Chargement des fichiers CSV...")
df     = pd.read_csv(CSV_IRVE)
df_ref = pd.read_csv(CSV_COMMUNES, sep=';')
print(f"   IRVE     : {len(df):>5} lignes  |  "
      f"{df['id_station_itinerance'].nunique()} stations  |  {df['id'].nunique()} PDC")
print(f"   Communes : {len(df_ref):>5} lignes")

# ── Normalisation globale des booléens ──────────────────────────────────────
BOOL_COLS = [
    'gratuit', 'cable_t2_attache',
    'paiement_acte', 'paiement_cb', 'paiement_autre',
    'prise_type_ef', 'prise_type_2', 'prise_type_combo_ccs',
    'prise_type_chademo', 'prise_type_autre',
]
for col in BOOL_COLS:
    df[col] = to_bool(df[col])

# raccordement : chaîne 'Direct'/'Indirect' → 1/0, NaN → None (SQL NULL)
df['raccordement'] = df['raccordement'].map({'Direct': 1, 'Indirect': 0})  # NaN reste NaN

# ==============================================================================
# 2. REMISE À ZÉRO (idempotence)
# ==============================================================================
TABLES_TO_TRUNCATE = [
    'possede_des', 'a_une', 'a_des', 'est_payer_avec',
    'point_de_charge', 'station',
    'acteur', 'commune', 'departement',
    'enseigne', 'implantation', 'condition_acces', 'prise', 'paiement', 'horaire',
]
print("\n🧹 Remise à zéro des tables...")
with engine.begin() as conn:
    conn.execute(text("SET FOREIGN_KEY_CHECKS = 0"))
    for t in TABLES_TO_TRUNCATE:
        conn.execute(text(f"TRUNCATE TABLE {t}"))
    conn.execute(text("SET FOREIGN_KEY_CHECKS = 1"))
print("   ✓ Tables vidées.\n")

# ==============================================================================
# 3. TABLES DE RÉFÉRENCE (Niveau 0)
# ==============================================================================
print("── Tables de référence ─────────────────────────────────────────────────")

# horaire -----------------------------------------------------------------------
# ⚠ Certaines valeurs dépassent 50 chars (max=100) → tronquées (PK)
# La table a_une utilisera les mêmes valeurs tronquées → cohérence FK garantie
df_hor = (df[['horaires']].dropna()
          .assign(heure=lambda x: trunc(x['horaires'], 50, 'horaire.heure'))
          [['heure']].drop_duplicates())
df_hor.to_sql('horaire', engine, if_exists='append', index=False)
print(f"   horaire        : {len(df_hor):>4} lignes")

# implantation ------------------------------------------------------------------
df_impl = (df[['implantation_station']].dropna().drop_duplicates())
df_impl['implantation_station'] = trunc(df_impl['implantation_station'], 50)
df_impl.to_sql('implantation', engine, if_exists='append', index=False)
print(f"   implantation   : {len(df_impl):>4} lignes")

# enseigne ----------------------------------------------------------------------
# Déduplication APRÈS troncature pour éviter les doublons de PK
df_ens = (df[['nom_enseigne']].dropna()
          .assign(nom_enseigne=lambda x: trunc(x['nom_enseigne'], 50, 'enseigne.nom_enseigne'))
          .drop_duplicates())
df_ens.to_sql('enseigne', engine, if_exists='append', index=False)
print(f"   enseigne       : {len(df_ens):>4} lignes")

# condition_acces ---------------------------------------------------------------
df_cond = (df[['condition_acces']].dropna()
           .rename(columns={'condition_acces': 'pdc_condition'})
           .drop_duplicates())
df_cond['pdc_condition'] = trunc(df_cond['pdc_condition'], 50)
df_cond.to_sql('condition_acces', engine, if_exists='append', index=False)
print(f"   condition_acces: {len(df_cond):>4} lignes")

# paiement & prise (catalogues fixes) ------------------------------------------
pd.DataFrame({'type_paiement': ['Acte', 'CB', 'Autre']}).to_sql(
    'paiement', engine, if_exists='append', index=False)
pd.DataFrame({'type_prise': ['EF', 'Type 2', 'Combo CCS', 'CHAdeMO', 'Autre']}).to_sql(
    'prise', engine, if_exists='append', index=False)
print("   paiement       :    3 lignes  (Acte / CB / Autre)")
print("   prise          :    5 lignes  (EF / Type 2 / Combo CCS / CHAdeMO / Autre)")

# ==============================================================================
# 4. GÉOGRAPHIE (Niveau 1 & 2)
# ==============================================================================
print("\n── Géographie ──────────────────────────────────────────────────────────")

df_ref['c_insee']  = pd.to_numeric(df_ref['code_insee'],  errors='coerce')
df_ref['c_dep']    = pd.to_numeric(df_ref['dep_code'],    errors='coerce')
df_ref['c_postal'] = pd.to_numeric(df_ref['code_postal'], errors='coerce')

insee_map  = {int(r.c_insee):  (r.nom_standard, int(r.c_dep))
              for r in df_ref.dropna(subset=['c_insee', 'c_dep']).drop_duplicates('c_insee').itertuples()}
postal_map = {int(r.c_postal): (r.nom_standard, int(r.c_dep))
              for r in df_ref.dropna(subset=['c_postal', 'c_dep']).drop_duplicates('c_postal').itertuples()}
dep_map    = {int(r.c_dep): r.dep_nom
              for r in df_ref.dropna(subset=['c_dep']).drop_duplicates('c_dep').itertuples()}

codes_irve = pd.to_numeric(df['code_insee_commune'], errors='coerce').dropna().astype(int).unique()
communes_rows, deps_set, n_unresolved = [], set(), 0

for code in codes_irve:
    if code in insee_map:
        nom, dep = insee_map[code]
    elif code in postal_map:
        nom, dep = postal_map[code]
    else:
        dep = int(str(code)[:2]) if len(str(code)) >= 2 else 0
        nom = f"Commune inconnue {code}"
        n_unresolved += 1
    communes_rows.append({'code_insee_commune': code, 'nom_commune': nom[:50], 'code_dep': dep})
    deps_set.add(dep)

if n_unresolved:
    print(f"   ⚠  {n_unresolved} code(s) INSEE non résolus — département déduit des 2 premiers chiffres")

df_dep = pd.DataFrame([
    {'code_dep': d, 'nom_departement': dep_map.get(d, f"Département {d}")[:50]}
    for d in deps_set
])
df_dep.to_sql('departement', engine, if_exists='append', index=False)
print(f"   departement    : {len(df_dep):>4} lignes")

df_com = pd.DataFrame(communes_rows)
df_com.to_sql('commune', engine, if_exists='append', index=False)
print(f"   commune        : {len(df_com):>4} lignes")

# ==============================================================================
# 5. ACTEURS (Niveau 3)
# ==============================================================================
print("\n── Acteurs ─────────────────────────────────────────────────────────────")

# ── Aménageurs : dédup par SIREN ────────────────────────────────────────────
# Une ligne par entité légale. Résultat : 132 lignes, 84 noms distincts.
# (TotalEnergies possède 42 SIREN distincts → 42 lignes dans acteur)
df_am = (df[['siren_amenageur', 'nom_amenageur', 'contact_amenageur']]
         .dropna(subset=['siren_amenageur'])
         .drop_duplicates(subset=['siren_amenageur'])
         .copy()
         .rename(columns={'siren_amenageur': 'siren_acteur',
                           'nom_amenageur':   'nom_acteur',
                           'contact_amenageur': 'contact_acteur'}))
df_am['role_acteur']      = 'Aménageur'
df_am['telephone_acteur'] = 'Non renseigné'
df_am['siren_acteur']     = df_am['siren_acteur'].astype(int)

# ── Opérateurs : dédup par nom ──────────────────────────────────────────────
# Pas de SIREN dans le CSV. Si l'opérateur est aussi aménageur, on réutilise
# son SIREN réel ; sinon on attribue un identifiant fictif ≥ 900 000 000.
siren_by_name = dict(zip(df_am['nom_acteur'].astype(str), df_am['siren_acteur']))
fake_counter  = 900_000_000

df_op = (df[['nom_operateur', 'contact_operateur', 'telephone_operateur']]
         .dropna(subset=['nom_operateur'])
         .drop_duplicates(subset=['nom_operateur'])
         .copy()
         .rename(columns={'nom_operateur':      'nom_acteur',
                           'contact_operateur':  'contact_acteur',
                           'telephone_operateur': 'telephone_acteur'}))
df_op['role_acteur'] = 'Opérateur'

op_sirens = []
for name in df_op['nom_acteur'].astype(str):
    if name not in siren_by_name:
        siren_by_name[name] = fake_counter
        fake_counter += 1
    op_sirens.append(siren_by_name[name])
df_op['siren_acteur'] = op_sirens

# Concat + nettoyage
df_acteurs = (pd.concat([df_am, df_op], ignore_index=True)
              .drop_duplicates(subset=['siren_acteur', 'role_acteur']))
df_acteurs['siren_acteur']     = df_acteurs['siren_acteur'].astype(int)
df_acteurs['nom_acteur']       = trunc(df_acteurs['nom_acteur'].fillna('Inconnu'), 50, 'acteur.nom_acteur')
df_acteurs['contact_acteur']   = trunc(df_acteurs['contact_acteur'].fillna('Non renseigné'), 255)
df_acteurs['telephone_acteur'] = trunc(df_acteurs['telephone_acteur'].fillna('Non renseigné'), 50)

df_acteurs.to_sql('acteur', engine, if_exists='append', index=False)
n_am = (df_acteurs['role_acteur'] == 'Aménageur').sum()
n_op = (df_acteurs['role_acteur'] == 'Opérateur').sum()
print(f"   acteur         : {len(df_acteurs):>4} lignes  "
      f"({n_am} aménageurs · {n_op} opérateurs)")

# Récupération des IDs auto-incrémentés pour les jointures suivantes
db_acteurs = pd.read_sql(
    "SELECT id_acteur, siren_acteur, nom_acteur, role_acteur FROM acteur", engine)
id_am_db = db_acteurs[db_acteurs['role_acteur'] == 'Aménageur']
id_op_db = db_acteurs[db_acteurs['role_acteur'] == 'Opérateur']

# ==============================================================================
# 6. STATION (Niveau 4)
# ==============================================================================
print("\n── Stations & PDC ──────────────────────────────────────────────────────")

df_stat = (df[['id_station_itinerance', 'nom_station', 'adresse_station', 'nbre_pdc',
               'date_mise_en_service', 'raccordement', 'id_station_local',
               'code_insee_commune', 'siren_amenageur', 'nom_operateur',
               'implantation_station', 'nom_enseigne']]
           .drop_duplicates(subset=['id_station_itinerance'])
           .copy())

# Jointure SIREN → id_acteur (aménageur)
df_stat = df_stat.merge(
    id_am_db[['id_acteur', 'siren_acteur']].rename(columns={'id_acteur': 'id_acteur_am'}),
    left_on='siren_amenageur', right_on='siren_acteur', how='left'
).drop(columns=['siren_acteur'])

# Jointure nom → id_acteur (opérateur)
df_stat = df_stat.merge(
    id_op_db[['id_acteur', 'nom_acteur']].rename(columns={'id_acteur': 'id_acteur_op'}),
    left_on='nom_operateur', right_on='nom_acteur', how='left'
).drop(columns=['nom_acteur'])

df_stat['code_insee_commune'] = (pd.to_numeric(df_stat['code_insee_commune'], errors='coerce')
                                 .astype('Int64'))
df_stat['date_mise_en_service'] = (pd.to_datetime(df_stat['date_mise_en_service'], errors='coerce')
                                   .fillna(pd.Timestamp('1970-01-01'))
                                   .dt.date)

# ⚠ nom_station (max=81) et adresse_station (max=90) dépassent VARCHAR(50)
station_final = pd.DataFrame({
    'id_station_itinerance': trunc(df_stat['id_station_itinerance'], 50),
    'nom_station':           trunc(df_stat['nom_station'].fillna('Inconnu'), 50, 'station.nom_station'),
    'adresse_station':       trunc(df_stat['adresse_station'].fillna('Non renseignée'), 50, 'station.adresse_station'),
    'nbr_pdc':               df_stat['nbre_pdc'].fillna(0).astype(int),
    'date_mise_en_service':  df_stat['date_mise_en_service'],
    'racordement':           df_stat['raccordement'],          # None → SQL NULL
    'id_station_local':      nullable_str(df_stat['id_station_local'], 50),  # 1577 NaN → NULL
    'code_insee_commune':    df_stat['code_insee_commune'],
    'id_acteur':             df_stat['id_acteur_am'].astype('Int64'),
    'implantation_station':  trunc(df_stat['implantation_station'].astype(str), 50),
    'nom_enseigne':          trunc(df_stat['nom_enseigne'].astype(str), 50, 'station.nom_enseigne'),
    'id_acteur_est_utiliser_par': df_stat['id_acteur_op'].astype('Int64'),
}).dropna(subset=['id_station_itinerance'])

station_final.to_sql('station', engine, if_exists='append', index=False)
print(f"   station        : {len(station_final):>4} lignes")

# Ensemble des stations effectivement insérées (pour filtrer les FK)
inserted_stations = set(station_final['id_station_itinerance'])

# ==============================================================================
# 7. POINT DE CHARGE (Niveau 5)
# ==============================================================================
df_pdc = (df[['id', 'consolidated_longitude', 'consolidated_latitude',
              'puissance_nominale', 'cable_t2_attache', 'gratuit',
              'condition_acces', 'tarification']]
          .drop_duplicates(subset=['id'])
          .dropna(subset=['id'])
          .copy()
          .rename(columns={
              'id': 'id_pdc', 'consolidated_longitude': 'lon',
              'consolidated_latitude': 'lat', 'puissance_nominale': 'puissance',
              'condition_acces': 'pdc_condition',
          }))

df_pdc['id_pdc']    = df_pdc['id_pdc'].astype(int)
df_pdc['lon']       = df_pdc['lon'].fillna(0.0).astype(float)
df_pdc['lat']       = df_pdc['lat'].fillna(0.0).astype(float)
df_pdc['puissance'] = df_pdc['puissance'].fillna(0.0).astype(float)
df_pdc['pdc_condition'] = nullable_str(df_pdc['pdc_condition'], 50)
df_pdc['tarification']  = trunc(df_pdc['tarification'].fillna('Non spécifiée'), 255)

df_pdc.to_sql('point_de_charge', engine, if_exists='append', index=False)
print(f"   point_de_charge: {len(df_pdc):>4} lignes")

# ==============================================================================
# 8. TABLES D'ASSOCIATION (Niveau 6)
# ==============================================================================
print("\n── Tables d'association ────────────────────────────────────────────────")

# a_une : Station ↔ Horaire ────────────────────────────────────────────────────
df_a_une = (df[['horaires', 'id_station_itinerance']]
            .dropna()
            .drop_duplicates()
            .rename(columns={'horaires': 'heure'}))
df_a_une['heure']                 = trunc(df_a_une['heure'], 50)
df_a_une['id_station_itinerance'] = trunc(df_a_une['id_station_itinerance'], 50)
# Filtrage défensif : ne garder que les stations réellement insérées
df_a_une = df_a_une[df_a_une['id_station_itinerance'].isin(inserted_stations)]
df_a_une.to_sql('a_une', engine, if_exists='append', index=False)
print(f"   a_une          : {len(df_a_une):>4} lignes")

# possede_des : Station ↔ PDC ──────────────────────────────────────────────────
df_pos = (df[['id', 'id_station_itinerance']]
          .dropna()
          .drop_duplicates()
          .rename(columns={'id': 'id_pdc'}))
df_pos['id_pdc']                  = df_pos['id_pdc'].astype(int)
df_pos['id_station_itinerance']   = trunc(df_pos['id_station_itinerance'].astype(str), 50)
df_pos = df_pos[df_pos['id_station_itinerance'].isin(inserted_stations)]
df_pos.to_sql('possede_des', engine, if_exists='append', index=False)
print(f"   possede_des    : {len(df_pos):>4} lignes")

# est_payer_avec : PDC ↔ Paiement ─────────────────────────────────────────────
# Approche vectorisée : pas de boucle ligne par ligne
paiement_map = {'paiement_acte': 'Acte', 'paiement_cb': 'CB', 'paiement_autre': 'Autre'}
pay_parts = []
for col, label in paiement_map.items():
    ids = df.loc[df[col] == 1, 'id'].dropna().drop_duplicates()
    pay_parts.append(pd.DataFrame({'type_paiement': label, 'id_pdc': ids.astype(int)}))
df_pay = pd.concat(pay_parts, ignore_index=True).drop_duplicates() if pay_parts else pd.DataFrame()
if not df_pay.empty:
    df_pay.to_sql('est_payer_avec', engine, if_exists='append', index=False)
print(f"   est_payer_avec : {len(df_pay):>4} lignes")

# a_des : PDC ↔ Prise ─────────────────────────────────────────────────────────
prise_map = {
    'prise_type_ef':         'EF',
    'prise_type_2':          'Type 2',
    'prise_type_combo_ccs':  'Combo CCS',
    'prise_type_chademo':    'CHAdeMO',
    'prise_type_autre':      'Autre',
}
prise_parts = []
for col, label in prise_map.items():
    ids = df.loc[df[col] == 1, 'id'].dropna().drop_duplicates()
    prise_parts.append(pd.DataFrame({'id_pdc': ids.astype(int), 'type_prise': label}))
df_prise = pd.concat(prise_parts, ignore_index=True).drop_duplicates() if prise_parts else pd.DataFrame()
if not df_prise.empty:
    df_prise.to_sql('a_des', engine, if_exists='append', index=False)
print(f"   a_des          : {len(df_prise):>4} lignes")

# ==============================================================================
print("\n🏆 Import terminé avec succès !")