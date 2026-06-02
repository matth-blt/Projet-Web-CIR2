import pandas as pd
import numpy as np
from sqlalchemy import create_engine, text

# ==============================================================================
# 1. CONFIGURATION DE LA CONNEXION MYSQL
# ==============================================================================
DB_USER = 'root'
DB_PASSWORD = ''  # Renseigne ton mot de passe si nécessaire
DB_HOST = 'localhost'
DB_NAME = 'irve_bdd_projet'

connection_string = f'mysql+mysqlconnector://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME}'
engine = create_engine(connection_string)

try:
    # --------------------------------------------------------------------------
    # NETTOYAGE AUTOMATIQUE AVANT IMPORTATION (Idempotence)
    # --------------------------------------------------------------------------
    print("🧹 Nettoyage et remise à zéro complète des tables...")
    with engine.connect() as connection:
        with connection.begin():
            connection.execute(text("SET FOREIGN_KEY_CHECKS = 0;"))
            tables = [
                'possede_des', 'a_une', 'a_des', 'est_payer_avec', 'prise', 
                'point_de_charge', 'station', 'acteur', 'commune', 'departement', 
                'enseigne', 'condition_acces', 'paiement', 'horaire', 'implantation'
            ]
            for table in tables:
                connection.execute(text(f"TRUNCATE TABLE {table};"))
            connection.execute(text("SET FOREIGN_KEY_CHECKS = 1;"))
    print("✨ Tables vidées, prêtes pour un import tout propre.")

    print("⏳ Chargement des fichiers CSV...")
    df = pd.read_csv('bdd/irve_init.csv')
    df_ref_communes = pd.read_csv('bdd/communes-france-2024-limite.csv', sep=';')

    # Nettoyage global des booléens du CSV (textes 'true'/'false' -> 1/0)
    for col in ['gratuit', 'raccordement', 'cable_t2_attache']:
        if col in df.columns:
            df[col] = df[col].astype(str).str.lower().map({'true': 1, 'false': 0}).fillna(0).astype(int)

    # ==============================================================================
    # 2. TABLES DE CONFIGURATION INDÉPENDANTES (NIVEAU 0)
    # ==============================================================================
    
    print("🚀 Insertion de la table 'implantation'...")
    df_impl = df[['implantation_station']].drop_duplicates().dropna()
    df_impl['implantation_station'] = df_impl['implantation_station'].astype(str).str[:50]
    df_impl.to_sql('implantation', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table 'horaire'...")
    df_hor = df[['horaires']].drop_duplicates().dropna()
    df_hor.columns = ['heure']
    df_hor['heure'] = df_hor['heure'].astype(str).str[:50]
    df_hor.to_sql('horaire', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table 'enseigne'...")
    df_ens = df[['nom_enseigne']].drop_duplicates().dropna()
    df_ens['nom_enseigne'] = df_ens['nom_enseigne'].astype(str).str[:50]
    df_ens.to_sql('enseigne', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table 'condition_acces'...")
    df_cond = df[['condition_acces']].drop_duplicates().dropna()
    df_cond.columns = ['pdc_condition']
    df_cond['pdc_condition'] = df_cond['pdc_condition'].astype(str).str[:50]
    df_cond.to_sql('condition_acces', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table 'paiement'...")
    df_paiement = pd.DataFrame({'type_paiement': ['Acte', 'CB', 'Autre']})
    df_paiement.to_sql('paiement', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table 'prise'...")
    # On alimente le catalogue des types de prises disponibles
    df_prise_cat = pd.DataFrame({'type_prise': ['EF', 'Type 2', 'Combo CCS', 'CHAdeMO', 'Autre']})
    df_prise_cat.to_sql('prise', con=engine, if_exists='append', index=False)

    # ==============================================================================
    # 3. GEOGRAPHIE (DEPARTEMENT & COMMUNE)
    # ==============================================================================
    print("🧹 Filtrage de la géographie (Résolution des anomalies INSEE / Code Postal)...")
    all_irve_codes = pd.to_numeric(df['code_insee_commune'], errors='coerce').dropna().astype(int).unique()
    
    df_ref_communes['code_insee_int'] = pd.to_numeric(df_ref_communes['code_insee'], errors='coerce')
    df_ref_communes['dep_code_int'] = pd.to_numeric(df_ref_communes['dep_code'], errors='coerce')
    df_ref_communes['code_postal_int'] = pd.to_numeric(df_ref_communes['code_postal'], errors='coerce')
    
    insee_lookup = df_ref_communes.dropna(subset=['code_insee_int', 'dep_code_int']).drop_duplicates(subset=['code_insee_int'])
    insee_map = dict(zip(insee_lookup['code_insee_int'].astype(int), zip(insee_lookup['nom_standard'], insee_lookup['dep_code_int'].astype(int))))
    
    postal_lookup = df_ref_communes.dropna(subset=['code_postal_int', 'dep_code_int']).drop_duplicates(subset=['code_postal_int'])
    postal_map = dict(zip(postal_lookup['code_postal_int'].astype(int), zip(postal_lookup['nom_standard'], postal_lookup['dep_code_int'].astype(int))))
    
    dep_names_lookup = df_ref_communes.dropna(subset=['dep_code_int']).drop_duplicates(subset=['dep_code_int'])
    dep_names_map = dict(zip(dep_names_lookup['dep_code_int'].astype(int), dep_names_lookup['dep_nom']))
    
    communes_rows = []
    deps_set = set()
    
    for code in all_irve_codes:
        if code in insee_map:
            name, dep = insee_map[code]
            communes_rows.append({'code_insee_commune': code, 'nom_commune': name, 'code_dep': dep})
            deps_set.add(dep)
        elif code in postal_map:
            name, dep = postal_map[code]
            communes_rows.append({'code_insee_commune': code, 'nom_commune': name, 'code_dep': dep})
            deps_set.add(dep)
        else:
            dep_guess = int(str(code)[:2]) if len(str(code)) >= 2 else 0
            communes_rows.append({'code_insee_commune': code, 'nom_commune': f"Commune {code}", 'code_dep': dep_guess})
            deps_set.add(dep_guess)

    print("🚀 Insertion de la table 'departement'...")
    deps_rows = [{'code_dep': d, 'nom_departement': dep_names_map.get(d, f"Département {d}")} for d in deps_set]
    df_dep = pd.DataFrame(deps_rows)
    df_dep['nom_departement'] = df_dep['nom_departement'].astype(str).str[:50]
    df_dep.to_sql('departement', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table 'commune'...")
    df_com = pd.DataFrame(communes_rows)
    df_com['nom_commune'] = df_com['nom_commune'].astype(str).str[:50]
    df_com.to_sql('commune', con=engine, if_exists='append', index=False)

    # ==============================================================================
    # 4. ENTIÉ UNIQUE : ACTEUR (AMÉNAGEURS & OPÉRATEURS)
    # ==============================================================================
    print("🧹 Extraction et restructuration des Acteurs...")
    df_am = df[['siren_amenageur', 'nom_amenageur', 'contact_amenageur']].dropna(subset=['siren_amenageur']).drop_duplicates(subset=['siren_amenageur']).copy()
    df_am.columns = ['siren_acteur', 'nom_acteur', 'contact_acteur']
    df_am['role_acteur'] = 'Aménageur'
    df_am['telephone_acteur'] = None
    
    df_op = df[['nom_operateur', 'contact_operateur', 'telephone_operateur']].dropna(subset=['nom_operateur']).drop_duplicates(subset=['nom_operateur']).copy()
    df_op.columns = ['nom_acteur', 'contact_acteur', 'telephone_acteur']
    df_op['role_acteur'] = 'Opérateur'
    
    siren_mapping = dict(zip(df_am['nom_acteur'].astype(str), df_am['siren_acteur'].astype(int)))
    fake_siren_counter = 900000000
    op_sirens = []
    for name in df_op['nom_acteur']:
        name_str = str(name)
        if name_str in siren_mapping:
            op_sirens.append(siren_mapping[name_str])
        else:
            siren_mapping[name_str] = fake_siren_counter
            op_sirens.append(fake_siren_counter)
            fake_siren_counter += 1
    df_op['siren_acteur'] = op_sirens

    df_acteurs_all = pd.concat([df_am, df_op]).drop_duplicates(subset=['siren_acteur', 'role_acteur'])
    df_acteurs_all['siren_acteur'] = df_acteurs_all['siren_acteur'].astype(int)
    df_acteurs_all['nom_acteur'] = df_acteurs_all['nom_acteur'].fillna('Inconnu').astype(str).str[:50]
    df_acteurs_all['contact_acteur'] = df_acteurs_all['contact_acteur'].fillna('Non renseigné').astype(str).str[:255]
    df_acteurs_all['telephone_acteur'] = df_acteurs_all['telephone_acteur'].fillna('Non renseigné').astype(str).str[:50]
    df_acteurs_all['role_acteur'] = df_acteurs_all['role_acteur'].astype(str).str[:50]
    
    print("🚀 Insertion de la table 'acteur'...")
    df_acteurs_all.to_sql('acteur', con=engine, if_exists='append', index=False)

    db_acteurs = pd.read_sql('SELECT id_acteur, siren_acteur, nom_acteur, role_acteur FROM acteur', con=engine)

    # ==============================================================================
    # 5. TABLE CORE : STATION
    # ==============================================================================
    print("🧹 Préparation des données de la table 'station'...")
    df_stat = df[['id_station_itinerance', 'nom_station', 'adresse_station', 'nbre_pdc', 
                  'date_mise_en_service', 'raccordement', 'id_station_local', 
                  'code_insee_commune', 'siren_amenageur', 'nom_operateur', 
                  'implantation_station', 'nom_enseigne']].drop_duplicates(subset=['id_station_itinerance']).copy()
    
    act_am = db_acteurs[db_acteurs['role_acteur'] == 'Aménageur']
    df_stat = pd.merge(df_stat, act_am[['id_acteur', 'siren_acteur']], left_on='siren_amenageur', right_on='siren_acteur', how='left')
    df_stat = df_stat.rename(columns={'id_acteur': 'id_acteur_am'}).drop(columns=['siren_acteur'])
    
    act_op = db_acteurs[db_acteurs['role_acteur'] == 'Opérateur']
    df_stat = pd.merge(df_stat, act_op[['id_acteur', 'nom_acteur']], left_on='nom_operateur', right_on='nom_acteur', how='left')
    df_stat = df_stat.rename(columns={'id_acteur': 'id_acteur_op'}).drop(columns=['nom_acteur'])

    df_stat['code_insee_commune'] = pd.to_numeric(df_stat['code_insee_commune'], errors='coerce').fillna(np.nan).astype('Int64')
    df_stat['date_mise_en_service'] = pd.to_datetime(df_stat['date_mise_en_service'], errors='coerce').fillna(pd.Timestamp('1970-01-01')).dt.date
    
    df_station_final = pd.DataFrame({
        'id_station_itinerance': df_stat['id_station_itinerance'].astype(str).str[:50],
        'nom_station': df_stat['nom_station'].fillna('Inconnu').astype(str).str[:50],
        'adresse_station': df_stat['adresse_station'].fillna('Non renseignée').astype(str).str[:50],
        'nbr_pdc': df_stat['nbre_pdc'].fillna(0).astype(int),
        'date_mise_en_service': df_stat['date_mise_en_service'],
        'racordement': df_stat['raccordement'],
        'id_station_local': df_stat['id_station_local'].astype(str).str[:50],
        'code_insee_commune': df_stat['code_insee_commune'],
        'id_acteur': df_stat['id_acteur_am'].astype('Int64'),
        'implantation_station': df_stat['implantation_station'].astype(str).str[:50],
        'nom_enseigne': df_stat['nom_enseigne'].astype(str).str[:50],
        'id_acteur_est_utiliser_par': df_stat['id_acteur_op'].astype('Int64')
    }).dropna(subset=['id_station_itinerance'])

    print("🚀 Insertion de la table 'station'...")
    df_station_final.to_sql('station', con=engine, if_exists='append', index=False)

    # ==============================================================================
    # 6. TABLE : POINT_DE_CHARGE
    # ==============================================================================
    print("🚀 Insertion de la table 'point_de_charge'...")
    df_pdc = df[['id', 'consolidated_longitude', 'consolidated_latitude', 'puissance_nominale', 'cable_t2_attache', 'gratuit', 'condition_acces']].copy()
    df_pdc.columns = ['id_pdc', 'lon', 'lat', 'puissance', 'cable_t2_attache', 'gratuit', 'pdc_condition']
    
    df_pdc = df_pdc.drop_duplicates(subset=['id_pdc']).dropna(subset=['id_pdc'])
    df_pdc['id_pdc'] = df_pdc['id_pdc'].astype(int)
    df_pdc['lon'] = df_pdc['lon'].fillna(0.0).astype(float)
    df_pdc['lat'] = df_pdc['lat'].fillna(0.0).astype(float)
    df_pdc['puissance'] = df_pdc['puissance'].fillna(0.0).astype(float)
    df_pdc['pdc_condition'] = df_pdc['pdc_condition'].astype(str).str[:50]
    
    df_pdc.to_sql('point_de_charge', con=engine, if_exists='append', index=False)

    # ==============================================================================
    # 7. TABLES DE RELATIONS ET ASSOCIATIONS (NIVEAU SUPÉRIEUR)
    # ==============================================================================
    print("🚀 Insertion de la table de relation 'a_une' (Station <-> Horaire)...")
    df_a_une = df[['horaires', 'id_station_itinerance']].dropna().drop_duplicates()
    df_a_une.columns = ['heure', 'id_station_itinerance']
    df_a_une['heure'] = df_a_une['heure'].astype(str).str[:50]
    df_a_une['id_station_itinerance'] = df_a_une['id_station_itinerance'].astype(str).str[:50]
    df_a_une.to_sql('a_une', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table de relation 'possede_des' (Station <-> PDC)...")
    df_possede_des = df[['id', 'id_station_itinerance']].dropna().drop_duplicates()
    df_possede_des.columns = ['id_pdc', 'id_station_itinerance']
    df_possede_des['id_pdc'] = df_possede_des['id_pdc'].astype(int)
    df_possede_des['id_station_itinerance'] = df_possede_des['id_station_itinerance'].astype(str).str[:50]
    df_possede_des.to_sql('possede_des', con=engine, if_exists='append', index=False)

    print("🚀 Insertion de la table de relation 'est_payer_avec'...")
    pay_rows = []
    for _, row in df[['id', 'paiement_acte', 'paiement_cb', 'paiement_autre']].dropna(subset=['id']).drop_duplicates(subset=['id']).iterrows():
        if str(row['paiement_acte']).lower() == 'true': pay_rows.append({'type_paiement': 'Acte', 'id_pdc': int(row['id'])})
        if str(row['paiement_cb']).lower() == 'true':  pay_rows.append({'type_paiement': 'CB', 'id_pdc': int(row['id'])})
        if str(row['paiement_autre']).lower() == 'true': pay_rows.append({'type_paiement': 'Autre', 'id_pdc': int(row['id'])})
    
    if pay_rows:
        df_pay_rel = pd.DataFrame(pay_rows).drop_duplicates()
        df_pay_rel.to_sql('est_payer_avec', con=engine, if_exists='append', index=False)

    # NOUVELLE LOGIQUE POUR LA RELATION ASSOCIEE AU PRISES (a_des)
    print("🚀 Insertion de la table de relation 'a_des' (PDC <-> Prise)...")
    prise_rows = []
    for _, row in df[['id', 'prise_type_ef', 'prise_type_2', 'prise_type_combo_ccs', 'prise_type_chademo', 'prise_type_autre']].dropna(subset=['id']).drop_duplicates(subset=['id']).iterrows():
        pid = int(row['id'])
        if str(row['prise_type_ef']).lower() == 'true':        prise_rows.append({'id_pdc': pid, 'type_prise': 'EF'})
        if str(row['prise_type_2']).lower() == 'true':         prise_rows.append({'id_pdc': pid, 'type_prise': 'Type 2'})
        if str(row['prise_type_combo_ccs']).lower() == 'true': prise_rows.append({'id_pdc': pid, 'type_prise': 'Combo CCS'})
        if str(row['prise_type_chademo']).lower() == 'true':   prise_rows.append({'id_pdc': pid, 'type_prise': 'CHAdeMO'})
        if str(row['prise_type_autre']).lower() == 'true':     prise_rows.append({'id_pdc': pid, 'type_prise': 'Autre'})
        
    if prise_rows:
        df_a_des_prise = pd.DataFrame(prise_rows).drop_duplicates()
        df_a_des_prise['type_prise'] = df_a_des_prise['type_prise'].astype(str).str[:50]
        df_a_des_prise.to_sql('a_des', con=engine, if_exists='append', index=False)

    print("🏆 Base de données entièrement alimentée avec succès !")

except Exception as e:
    print(f"❌ Une erreur globale est survenue : {e}")