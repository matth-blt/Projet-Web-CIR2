<?php 
    mb_internal_encoding('UTF-8');

    $DB_USER = 'irveuser';
    $DB_PASSWORD = 'irvepwd';
    $DB_HOST = 'localhost';
    $DB_NAME = 'irve';


    /** Vrai si la valeur correspond à un « NaN » pandas (null ou chaîne vide). */
    function is_na($v): bool {
        return $v === null || $v === '';
    }

    /** Reproduit pandas .astype(str) : un NaN devient la chaîne littérale 'nan'. */
    function pstr($v): string {
        return is_na($v) ? 'nan' : (string)$v;
    }

    /** Reproduit pandas.to_numeric(errors='coerce') : float ou null. */
    function to_num($v): ?float {
        if (is_na($v)) return null;
        $s = trim((string)$v);
        return is_numeric($s) ? (float)$s : null;
    }

    /** to_numeric puis astype(int) tolérant : int ou null. */
    function to_int_or_null($v): ?int {
        $n = to_num($v);
        return $n === null ? null : (int)$n;
    }

    /** Troncage type str[:n] (par caractères). */
    function trunc(?string $s, int $n): string {
        return mb_substr((string)$s, 0, $n);
    }

    /**
     * Lecture CSV -> tableau de lignes associatives (clé = entête).
     * Les cellules vides sont converties en null (équivalent NaN pandas).
    */
    function read_csv(string $path, string $sep = ','): array {
        if (!file_exists($path)) {
            throw new RuntimeException("Fichier introuvable : $path");
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Impossible d'ouvrir : $path");
        }
        $rows   = [];
        $header = fgetcsv($handle, 0, $sep);
        if ($header === false) {
            fclose($handle);
            return $rows;
        }
        // Suppression d'un éventuel BOM UTF-8 sur la première colonne
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        while (($data = fgetcsv($handle, 0, $sep)) !== false) {
            if ($data === [null]) {
                continue; // ligne vide
            }
            $assoc = [];
            foreach ($header as $i => $col) {
                $val = $data[$i] ?? null;
                if ($val === '') {
                    $val = null;
                }
                $assoc[$col] = $val;
            }
            $rows[] = $assoc;
        }
        fclose($handle);
        return $rows;
    }

    /** Valeurs distinctes non nulles d'une colonne, dans l'ordre d'apparition. */
    function distinct_non_null(array $rows, string $col): array {
        $seen = [];
        $out  = [];
        foreach ($rows as $r) {
            $v = $r[$col] ?? null;
            if (is_na($v)) continue;
            $k = (string)$v;
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $v;
        }
        return $out;
    }

    /** drop_duplicates() sur un sous-ensemble de colonnes (garde la 1re occurrence). */
    function dedupe_rows(array $rows, array $cols): array {
        $seen = [];
        $out  = [];
        foreach ($rows as $r) {
            $parts = [];
            foreach ($cols as $c) {
                $parts[] = (string)($r[$c] ?? '␀');
            }
            $k = implode('|', $parts);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Insertion type to_sql(if_exists='append') : INSERT multi-lignes par lots,
     * dans une transaction. Chaque ligne est un tableau associatif clé=colonne.
    */
    function insert_rows(PDO $pdo, string $table, array $columns, array $rows, int $chunk = 1000): void {
        if (empty($rows)) return;

        $colList = implode(', ', $columns);
        $placeholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $pdo->beginTransaction();
        try {
            foreach (array_chunk($rows, $chunk) as $batch) {
                $sql  = "INSERT INTO `$table` ($colList) VALUES " . implode(', ', array_fill(0, count($batch), $placeholder));
                $stmt = $pdo->prepare($sql);
                $params = [];
                foreach ($batch as $r) {
                    foreach ($columns as $c) {
                        $params[] = $r[$c] ?? null;
                    }
                }
                $stmt->execute($params);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // ------------------------------------------------------------------------------

    try {
        $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // --------------------------------------------------------------------------
        // NETTOYAGE AUTOMATIQUE AVANT IMPORTATION (Idempotence)
        // --------------------------------------------------------------------------
        echo "🧹 Nettoyage et remise à zéro complète des tables..." . PHP_EOL;
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tables = [
            'possede_des', 'a_une', 'a_des', 'est_payer_avec', 'prise',
            'point_de_charge', 'station', 'acteur', 'commune', 'departement',
            'enseigne', 'condition_acces', 'paiement', 'horaire', 'implantation',
        ];
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `$table`;");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "✨ Tables vidées, prêtes pour un import tout propre." . PHP_EOL;

        echo "⏳ Chargement des fichiers CSV..." . PHP_EOL;
        $df             = read_csv('irve_init.csv');
        $df_ref_communes = read_csv('communes-france-2024-limite.csv', ';');

        // Nettoyage global des booléens du CSV (textes 'true'/'false' -> 1/0).
        // NB : comme dans le Python, toute valeur autre que 'true' (y compris 'false',
        // vide, ou '1') est ramenée à 0/1 via le map puis fillna(0).
        foreach ($df as &$row) {
            foreach (['gratuit', 'raccordement', 'cable_t2_attache'] as $col) {
                if (array_key_exists($col, $row)) {
                    $lv = is_na($row[$col]) ? null : strtolower(trim((string)$row[$col]));
                    $row[$col] = ($lv === 'true' || $lv === '1') ? 1 : 0;
                }
            }
        }
        unset($row);

        // Résolution des identifiants d'itinérance en doublon (ex: "Non concerné")
        foreach ($df as $index => &$row) {
            $sid = $row['id_station_itinerance'] ?? null;
            if (is_na($sid) || strtolower(trim((string)$sid)) === 'non concerné' || strtolower(trim((string)$sid)) === 'non concerne') {
                $localId = $row['id_station_local'] ?? null;
                if (!is_na($localId) && strtolower(trim((string)$localId)) !== 'nan' && strtolower(trim((string)$localId)) !== 'non concerné' && strtolower(trim((string)$localId)) !== 'non concerne') {
                    $cleanLocal = trim((string)$localId);
                    $row['id_station_itinerance'] = strlen($cleanLocal) <= 50 ? $cleanLocal : substr($cleanLocal, 0, 50);
                } else {
                    $name = trim((string)($row['nom_station'] ?? ''));
                    $addr = trim((string)($row['adresse_station'] ?? ''));
                    if ($name !== '' || $addr !== '') {
                        $row['id_station_itinerance'] = 'STA_SYN_' . md5($name . '|' . $addr);
                    } else {
                        $row['id_station_itinerance'] = 'STA_ROW_' . ($row['id'] ?? $index);
                    }
                }
            }
        }
        unset($row);

        // ==========================================================================
        //  TABLES DE CONFIGURATION INDÉPENDANTES (NIVEAU 0)
        // ==========================================================================

        echo "🚀 Insertion de la table 'implantation'..." . PHP_EOL;
        $rows = [];
        foreach (distinct_non_null($df, 'implantation_station') as $v) {
            $rows[] = ['implantation_station' => trunc((string)$v, 50)];
        }
        insert_rows($pdo, 'implantation', ['implantation_station'], $rows);

        echo "🚀 Insertion de la table 'horaire'..." . PHP_EOL;
        $rows = [];
        foreach (distinct_non_null($df, 'horaires') as $v) {
            $rows[] = ['heure' => trunc((string)$v, 50)];
        }
        insert_rows($pdo, 'horaire', ['heure'], $rows);

        echo "🚀 Insertion de la table 'enseigne'..." . PHP_EOL;
        $rows = [];
        foreach (distinct_non_null($df, 'nom_enseigne') as $v) {
            $rows[] = ['nom_enseigne' => trunc((string)$v, 50)];
        }
        insert_rows($pdo, 'enseigne', ['nom_enseigne'], $rows);

        echo "🚀 Insertion de la table 'condition_acces'..." . PHP_EOL;
        $rows = [];
        foreach (distinct_non_null($df, 'condition_acces') as $v) {
            $rows[] = ['pdc_condition' => trunc((string)$v, 50)];
        }
        insert_rows($pdo, 'condition_acces', ['pdc_condition'], $rows);

        echo "🚀 Insertion de la table 'paiement'..." . PHP_EOL;
        $rows = [];
        foreach (['Acte', 'CB', 'Autre'] as $tp) {
            $rows[] = ['type_paiement' => $tp];
        }
        insert_rows($pdo, 'paiement', ['type_paiement'], $rows);

        echo "🚀 Insertion de la table 'prise'..." . PHP_EOL;
        // On alimente le catalogue des types de prises disponibles
        $rows = [];
        foreach (['EF', 'Type 2', 'Combo CCS', 'CHAdeMO', 'Autre'] as $tp) {
            $rows[] = ['type_prise' => $tp];
        }
        insert_rows($pdo, 'prise', ['type_prise'], $rows);

        // ==========================================================================
        //  GEOGRAPHIE (DEPARTEMENT & COMMUNE)
        // ==========================================================================
        echo "🧹 Filtrage de la géographie (Résolution des anomalies INSEE / Code Postal)..." . PHP_EOL;

        // all_irve_codes : to_numeric(coerce) -> dropna -> int -> unique (ordre d'apparition)
        $all_irve_codes = [];
        $seen_codes = [];
        foreach ($df as $r) {
            $c = to_int_or_null($r['code_insee_commune'] ?? null);
            if ($c === null) 
                continue;
            if (isset($seen_codes[$c]))
                continue;
            $seen_codes[$c] = true;
            $all_irve_codes[] = $c;
        }

        // Construction des tables de correspondance à partir du référentiel communes.
        // (équivalent des drop_duplicates(keep='first') sur insee / postal / dep)
        $insee_map = []; // code_insee_int  => [nom_standard, dep_code_int]
        $postal_map = []; // code_postal_int => [nom_standard, dep_code_int]
        $dep_names_map = []; // dep_code_int => dep_nom (peut être null)
        foreach ($df_ref_communes as $r) {
            $insee  = to_int_or_null($r['code_insee']  ?? null);
            $dep = to_int_or_null($r['dep_code'] ?? null);
            $postal = to_int_or_null($r['code_postal'] ?? null);
            $nom = $r['nom_standard'] ?? null;
            $depnom = $r['dep_nom'] ?? null;

            if ($insee !== null && $dep !== null && !isset($insee_map[$insee])) {
                $insee_map[$insee] = [$nom, $dep];
            }
            if ($postal !== null && $dep !== null && !isset($postal_map[$postal])) {
                $postal_map[$postal] = [$nom, $dep];
            }
            if ($dep !== null && !array_key_exists($dep, $dep_names_map)) {
                $dep_names_map[$dep] = $depnom;
            }
        }

        $communes_rows = [];
        $deps_set      = []; // utilisé comme set : clé = code_dep

        foreach ($all_irve_codes as $code) {
            if (isset($insee_map[$code])) {
                [$name, $dep] = $insee_map[$code];
                $communes_rows[] = ['code_insee_commune' => $code, 'nom_commune' => $name, 'code_dep' => $dep];
                $deps_set[$dep] = true;
            } elseif (isset($postal_map[$code])) {
                [$name, $dep] = $postal_map[$code];
                $communes_rows[] = ['code_insee_commune' => $code, 'nom_commune' => $name, 'code_dep' => $dep];
                $deps_set[$dep] = true;
            } else {
                $s = (string)$code;
                $dep_guess = (strlen($s) >= 2) ? (int)substr($s, 0, 2) : 0;
                $communes_rows[] = ['code_insee_commune' => $code, 'nom_commune' => "Commune $code", 'code_dep' => $dep_guess];
                $deps_set[$dep_guess] = true;
            }
        }

        echo "🚀 Insertion de la table 'departement'..." . PHP_EOL;
        $deps_rows = [];
        foreach (array_keys($deps_set) as $d) {
            // .get(d, default) : si la clé existe avec une valeur null, on garde null
            // (qui deviendra 'nan' via astype(str)), sinon on prend le défaut.
            $nom = array_key_exists($d, $dep_names_map) ? $dep_names_map[$d] : "Département $d";
            $deps_rows[] = ['code_dep' => $d, 'nom_departement' => trunc(pstr($nom), 50)];
        }
        insert_rows($pdo, 'departement', ['code_dep', 'nom_departement'], $deps_rows);

        echo "🚀 Insertion de la table 'commune'..." . PHP_EOL;
        foreach ($communes_rows as &$cr) {
            $cr['nom_commune'] = trunc(pstr($cr['nom_commune']), 50);
        }
        unset($cr);
        insert_rows($pdo, 'commune', ['code_insee_commune', 'nom_commune', 'code_dep'], $communes_rows);

        // ==========================================================================
        //  ENTITÉ UNIQUE : ACTEUR (AMÉNAGEURS & OPÉRATEURS)
        // ==========================================================================
        echo "🧹 Extraction et restructuration des Acteurs..." . PHP_EOL;

        // df_am : dropna(siren) + drop_duplicates(siren) -> Aménageur
        $df_am = [];
        $seen_siren = [];
        foreach ($df as $r) {
            $siren_raw = $r['siren_amenageur'] ?? null;

            if (is_na($siren_raw)) 
                continue;
            $key = (string)$siren_raw;

            if (isset($seen_siren[$key])) 
                continue;
            $seen_siren[$key] = true;

            $df_am[] = [
                'siren_acteur' => to_int_or_null($siren_raw),
                'nom_acteur' => $r['nom_amenageur'] ?? null,
                'contact_acteur' => $r['contact_amenageur'] ?? null,
                'role_acteur' => 'Aménageur',
                'telephone_acteur' => null
            ];
        }

        // df_op : dropna(nom_operateur) + drop_duplicates(nom_operateur) -> Opérateur
        $df_op = [];
        $seen_op = [];
        foreach ($df as $r) {
            $nom = $r['nom_operateur'] ?? null;

            if (is_na($nom)) 
                continue;
            $key = (string)$nom;

            if (isset($seen_op[$key])) 
                continue;
            $seen_op[$key] = true;

            $df_op[] = [
                'nom_acteur' => $nom,
                'contact_acteur' => $r['contact_operateur'] ?? null,
                'telephone_acteur' => $r['telephone_operateur'] ?? null,
                'role_acteur' => 'Opérateur'
            ];
        }

        // siren_mapping : nom (str) -> siren (int), la dernière occurrence l'emporte
        $siren_mapping = [];
        foreach ($df_am as $a) {
            $siren_mapping[pstr($a['nom_acteur'])] = $a['siren_acteur'];
        }

        // Attribution des SIREN aux opérateurs (réutilisation ou faux SIREN incrémental)
        $fake_siren_counter = 900000000;
        foreach ($df_op as &$o) {
            $ns = pstr($o['nom_acteur']);
            if (array_key_exists($ns, $siren_mapping)) {
                $o['siren_acteur'] = $siren_mapping[$ns];
            } else {
                $siren_mapping[$ns] = $fake_siren_counter;
                $o['siren_acteur'] = $fake_siren_counter;
                $fake_siren_counter += 1;
            }
        }
        unset($o);

        // concat(am, op) + drop_duplicates(siren_acteur, role_acteur) + nettoyage final
        $merged = array_merge($df_am, $df_op);
        $seen_pair = [];
        $df_acteurs_all = [];
        foreach ($merged as $a) {
            $k = $a['siren_acteur'] . '|' . $a['role_acteur'];
            if (isset($seen_pair[$k])) 
                continue;
            $seen_pair[$k] = true;

            $df_acteurs_all[] = [
                'siren_acteur' => (int)$a['siren_acteur'],
                'nom_acteur' => trunc(is_na($a['nom_acteur']) ? 'Inconnu' : (string)$a['nom_acteur'], 50),
                'contact_acteur' => trunc(is_na($a['contact_acteur']) ? 'Non renseigné' : (string)$a['contact_acteur'], 255),
                'telephone_acteur' => trunc(is_na($a['telephone_acteur']) ? 'Non renseigné' : (string)$a['telephone_acteur'], 50),
                'role_acteur' => trunc((string)$a['role_acteur'], 50)
            ];
        }

        echo "🚀 Insertion de la table 'acteur'..." . PHP_EOL;
        insert_rows($pdo, 'acteur', [
            'siren_acteur', 'nom_acteur', 
            'contact_acteur', 'role_acteur', 
            'telephone_acteur'
        ], $df_acteurs_all);

        // Relecture pour récupérer les id_acteur générés
        $db_acteurs = $pdo->query('SELECT id_acteur, siren_acteur, nom_acteur, role_acteur FROM acteur')->fetchAll();

        // Cartes de correspondance pour les "merge" de la table station
        $am_map = []; // siren (int) -> id_acteur (Aménageurs)
        $op_map = []; // nom -> id_acteur (Opérateurs)
        foreach ($db_acteurs as $a) {
            if ($a['role_acteur'] === 'Aménageur') {
                $am_map[(int)$a['siren_acteur']] = (int)$a['id_acteur'];
            } elseif ($a['role_acteur'] === 'Opérateur') {
                $op_map[$a['nom_acteur']] = (int)$a['id_acteur'];
            }
        }

        // ==========================================================================
        //  TABLE CORE : STATION
        // ==========================================================================
        echo "🧹 Préparation des données de la table 'station'..." . PHP_EOL;

        // drop_duplicates(id_station_itinerance) : on garde la 1re ligne par station
        $df_stat = [];
        $seen_station = [];
        foreach ($df as $r) {
            $sid = $r['id_station_itinerance'] ?? null;
            $key = (string)$sid;
            if (isset($seen_station[$key])) continue;
            $seen_station[$key] = true;
            $df_stat[] = $r;
        }

        $station_rows = [];
        foreach ($df_stat as $s) {
            $sid = $s['id_station_itinerance'] ?? null;
            if (is_na($sid)) continue; // dropna(subset=['id_station_itinerance'])

            // merge Aménageur : sur siren_amenageur == siren_acteur
            $siren_int = to_int_or_null($s['siren_amenageur'] ?? null);
            $id_am = ($siren_int !== null && isset($am_map[$siren_int])) ? $am_map[$siren_int] : null;

            // merge Opérateur : sur nom_operateur == nom_acteur (nom tronqué côté BDD)
            $nom_op = $s['nom_operateur'] ?? null;
            $id_op = (!is_na($nom_op) && array_key_exists((string)$nom_op, $op_map)) ? $op_map[(string)$nom_op] : null;

            $insee = to_int_or_null($s['code_insee_commune'] ?? null);

            // date_mise_en_service : parse, fillna(null), .dt.date
            $dt = $s['date_mise_en_service'] ?? null;
            $date = null;
            if (!is_na($dt) && (string)$dt !== '0000-00-00') {
                $ts = strtotime((string)$dt);
                if ($ts !== false) {
                    $formatted = date('Y-m-d', $ts);
                    if ((int)$formatted >= 1000) {
                        $date = $formatted;
                    }
                }
            }

            $nbre = $s['nbre_pdc'] ?? null;
            $nbr_pdc = (is_na($nbre) || !is_numeric($nbre)) ? 0 : (int)(float)$nbre;

            $station_rows[] = [
                'id_station_itinerance' => trunc((string)$sid, 50),
                'nom_station' => trunc(is_na($s['nom_station'] ?? null) ? 'Inconnu' : (string)$s['nom_station'], 50),
                'adresse_station' => trunc(is_na($s['adresse_station'] ?? null) ? 'Non renseignée' : (string)$s['adresse_station'], 50),
                'nbr_pdc' => $nbr_pdc,
                'date_mise_en_service' => $date,
                'racordement' => $s['raccordement'] ?? 0,
                'id_station_local' => trunc(pstr($s['id_station_local'] ?? null), 50),
                'code_insee_commune' => $insee,
                'id_acteur' => $id_am,
                'implantation_station' => trunc(pstr($s['implantation_station'] ?? null), 50),
                'nom_enseigne' => trunc(pstr($s['nom_enseigne'] ?? null), 50),
                'id_acteur_est_utiliser_par'  => $id_op
            ];
        }

        echo "🚀 Insertion de la table 'station'..." . PHP_EOL;
        insert_rows($pdo, 'station', [
            'id_station_itinerance', 'nom_station', 'adresse_station', 'nbr_pdc',
            'date_mise_en_service', 'racordement', 'id_station_local',
            'code_insee_commune', 'id_acteur', 'implantation_station',
            'nom_enseigne', 'id_acteur_est_utiliser_par',
        ], $station_rows);

        // ==========================================================================
        //  TABLE : POINT_DE_CHARGE
        // ==========================================================================
        echo "🚀 Insertion de la table 'point_de_charge'..." . PHP_EOL;
        $pdc_rows = [];
        $seen_pdc = [];
        foreach ($df as $r) {
            $id = $r['id'] ?? null;
            if (is_na($id)) continue;
            $key = (string)$id;
            if (isset($seen_pdc[$key])) continue;
            $seen_pdc[$key] = true;

            $lon = is_na($r['consolidated_longitude'] ?? null) ? 0.0 : (float)$r['consolidated_longitude'];
            $lat = is_na($r['consolidated_latitude']  ?? null) ? 0.0 : (float)$r['consolidated_latitude'];
            $pui = is_na($r['puissance_nominale'] ?? null) ? 0.0 : (float)$r['puissance_nominale'];

            $pdc_rows[] = [
                'id_pdc' => (int)(float)$id,
                'lon' => $lon,
                'lat' => $lat,
                'puissance' => $pui,
                'cable_t2_attache' => $r['cable_t2_attache'] ?? 0,
                'gratuit' => $r['gratuit'] ?? 0,
                'pdc_condition' => trunc(pstr($r['condition_acces'] ?? null), 50),
                'tarification' => trunc(is_na($r['tarification'] ?? null) ? 'Non spécifiée' : (string)$r['tarification'], 255)
            ];
        }
        insert_rows($pdo, 'point_de_charge', [
            'id_pdc', 'lon', 'lat', 'puissance', 'cable_t2_attache',
            'gratuit', 'pdc_condition', 'tarification',
        ], $pdc_rows);

        // ==========================================================================
        //  TABLES DE RELATIONS ET ASSOCIATIONS (NIVEAU SUPÉRIEUR)
        // ==========================================================================
        echo "🚀 Insertion de la table de relation 'a_une' (Station <-> Horaire)..." . PHP_EOL;
        $a_une = [];
        $seen = [];
        foreach ($df as $r) {
            $h = $r['horaires'] ?? null;
            $sid = $r['id_station_itinerance'] ?? null;

            if (is_na($h) || is_na($sid)) 
                continue;
            $k = $h . '|' . $sid;

            if (isset($seen[$k])) 
                continue;
            $seen[$k] = true;

            $a_une[] = [
                'heure' => trunc((string)$h, 50),
                'id_station_itinerance' => trunc((string)$sid, 50),
            ];
        }
        insert_rows($pdo, 'a_une', ['heure', 'id_station_itinerance'], $a_une);

        echo "🚀 Insertion de la table de relation 'possede_des' (Station <-> PDC)..." . PHP_EOL;
        $possede_des = [];
        $seen = [];
        foreach ($df as $r) {
            $id  = $r['id'] ?? null;
            $sid = $r['id_station_itinerance'] ?? null;

            if (is_na($id) || is_na($sid)) 
                continue;
            $k = $id . '|' . $sid;

            if (isset($seen[$k])) 
                continue;
            $seen[$k] = true;

            $possede_des[] = [
                'id_pdc' => (int)(float)$id,
                'id_station_itinerance' => trunc((string)$sid, 50),
            ];
        }
        insert_rows($pdo, 'possede_des', ['id_pdc', 'id_station_itinerance'], $possede_des);

        echo "🚀 Insertion de la table de relation 'est_payer_avec'..." . PHP_EOL;
        $pay_rows = [];
        $seen_id  = [];
        foreach ($df as $r) {
            $id = $r['id'] ?? null;

            if (is_na($id)) 
                continue;
            $k = (string)$id;

            if (isset($seen_id[$k])) 
                continue;
            $seen_id[$k] = true;

            $pid = (int)(float)$id;
            $acte = strtolower(trim((string)($r['paiement_acte'] ?? '')));
            $cb = strtolower(trim((string)($r['paiement_cb'] ?? '')));
            $autre = strtolower(trim((string)($r['paiement_autre'] ?? '')));

            if ($acte === 'true' || $acte === '1') {
                $pay_rows[] = ['type_paiement' => 'Acte', 'id_pdc' => $pid];
            } 
            if ($cb === 'true' || $cb === '1') {
                $pay_rows[] = ['type_paiement' => 'CB', 'id_pdc' => $pid];
            }   
            if ($autre === 'true' || $autre === '1') {
                $pay_rows[] = ['type_paiement' => 'Autre', 'id_pdc' => $pid];
            }
        }
        if (!empty($pay_rows)) {
            $pay_rows = dedupe_rows($pay_rows, ['type_paiement', 'id_pdc']);
            insert_rows($pdo, 'est_payer_avec', ['type_paiement', 'id_pdc'], $pay_rows);
        }

        // NOUVELLE LOGIQUE POUR LA RELATION ASSOCIEE AUX PRISES (a_des)
        echo "🚀 Insertion de la table de relation 'a_des' (PDC <-> Prise)..." . PHP_EOL;
        $prise_rows = [];
        $seen_id = [];
        foreach ($df as $r) {
            $id = $r['id'] ?? null;

            if (is_na($id)) 
                continue; 
            $k = (string)$id;

            if (isset($seen_id[$k])) 
                continue;
            $seen_id[$k] = true;

            $pid = (int)(float)$id;
            $ef = strtolower(trim((string)($r['prise_type_ef'] ?? '')));
            $t2 = strtolower(trim((string)($r['prise_type_2'] ?? '')));
            $ccs = strtolower(trim((string)($r['prise_type_combo_ccs'] ?? '')));
            $chademo = strtolower(trim((string)($r['prise_type_chademo'] ?? '')));
            $autre_p = strtolower(trim((string)($r['prise_type_autre'] ?? '')));

            if ($ef === 'true' || $ef === '1') {
                $prise_rows[] = ['id_pdc' => $pid, 'type_prise' => 'EF'];
            }
            if ($t2 === 'true' || $t2 === '1') {
                $prise_rows[] = ['id_pdc' => $pid, 'type_prise' => 'Type 2'];
            }
            if ($ccs === 'true' || $ccs === '1') {
                $prise_rows[] = ['id_pdc' => $pid, 'type_prise' => 'Combo CCS'];
            }
            if ($chademo === 'true' || $chademo === '1') {
                $prise_rows[] = ['id_pdc' => $pid, 'type_prise' => 'CHAdeMO'];
            }
            if ($autre_p === 'true' || $autre_p === '1') {
                $prise_rows[] = ['id_pdc' => $pid, 'type_prise' => 'Autre'];
            }
               
        }

        if (!empty($prise_rows)) {
            foreach ($prise_rows as &$pr) {
                $pr['type_prise'] = trunc($pr['type_prise'], 50);
            }
            unset($pr);
            $prise_rows = dedupe_rows($prise_rows, ['id_pdc', 'type_prise']);
            insert_rows($pdo, 'a_des', ['id_pdc', 'type_prise'], $prise_rows);
        }

        echo "🏆 Base de données entièrement alimentée avec succès !" . PHP_EOL;
    } catch (Throwable $e) {
        echo "❌ Une erreur globale est survenue : " . $e->getMessage() . PHP_EOL;
    }
?>